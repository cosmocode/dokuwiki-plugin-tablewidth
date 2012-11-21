<?php

/**
 * Plugin TableWidth
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

/* Must be run within Dokuwiki */
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'action.php');

class action_plugin_tablewidth extends DokuWiki_Action_Plugin {

    /**
     * Register callbacks
     */
    function register(Doku_Event_Handler &$controller) {
        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, 'replaceComments');
        $controller->register_hook('PLUGINODT_CONTENT_ADDZIP', 'BEFORE', $this, 'processODT');
    }

    /**
     * Replace table-width comments by HTML
     */
    function replaceComments(Doku_Event &$event, $param) {
        if ($event->data[0] !== 'xhtml') {
            return;
        }
        $pattern = '/(<!-- table-width [^\n]+? -->\n)([^\n]*<table.*?>)(\s*<t)/';

        $flags = PREG_SET_ORDER | PREG_OFFSET_CAPTURE;
        if (preg_match_all($pattern, $event->data[1], $match, $flags) > 0) {

            $start = 0;
            $html = '';
            foreach ($match as $data) {
                $html .= substr($event->data[1], $start, $data[0][1] - $start);
                $html .= $this->_processTable($data);
                $start = $data[0][1] + strlen($data[0][0]);
            }
            $event->data[1] = $html . substr($event->data[1], $start);
        }
    }

    /**
     * Convert table-width comments and table mark-up into final HTML
     */
    function _processTable($data) {
        preg_match('/<!-- table-width ([^\n]+?) -->/', $data[1][0], $match);
        $width = preg_split('/\s+/', $match[1]);
        $tableWidth = array_shift($width);
        if ($tableWidth != '-') {
            $table = $this->_styleTable($data[2][0], $tableWidth);
        } else {
            $table = $data[2][0];
        }
        return $table . $this->_renderColumns($width) . $data[3][0];
    }

    /**
     * Add width style to the table
     */
    function _styleTable($html, $width) {
        preg_match('/^([^\n]*<table)(.*?)(>)$/', $html, $match);
        $entry = $match[1];
        $attributes = $match[2];
        $exit = $match[3];
        if (preg_match('/(.*?style\s*=\s*(["\']).*?)(\2.*)/', $attributes, $match) == 1) {
            $attributes = $match[1] . ';width: ' . $width . ';' . $match[3];
        }
        else {
            $attributes .= ' style="width: ' . $width . ';"';
        }
        return $entry . $attributes . $exit;
    }

    /**
     * Render column tags
     */
    function _renderColumns($width) {
        $html = DOKU_LF;
        foreach ($width as $w) {
            if ($w != '-') {
                $html .= '<col style="width: ' . $w . '" />';
            }
            else {
                $html .= '<col />';
            }
        }
        return $html;
    }

    function processODT(Doku_Event &$event, $params) {
        dbglog('start odt processing');

        $tables = $this->getODTTables($event->data);
        if (!empty($tables)) {
            $start = 0;
            $odt = '';
            dbglog('found ' . count($tables) . 'tables');
            foreach ($tables as $table) {
                $odt .= substr($event->data, $start, $table->getStartPosition() - $start);
                $odt .= $this->_processODTTable($table);
                $odt .= '<table:table-row>';
                $start = $table->getStartPosition() + $table->getTextLength();
            }
            $event->data = $odt . substr($event->data, $start);
        }
        dbglog('odt processed');
    }

    function getODTTables($string) {
        $pattern = '/(<!-- table-width [^>]+? -->)\s*(<table:table.*?>)(.*?)<table:table-row>/';

        $flags = PREG_SET_ORDER | PREG_OFFSET_CAPTURE;
        $resultCount = preg_match_all($pattern, $string, $matches, $flags);

        $results = array();
        if ($resultCount === 0) return $results;
        foreach ($matches as $match) {
            $results[] = new TableWidthODTTable($match);
        }
        return $results;
    }

    function _processODTTable(TableWidthODTTable $odtTable) {
        list($tableWidth, $width) = $this->getTableWidth($odtTable->getComment());
        $columnCount = $this->getColumnCount($odtTable->getColumnSpec());

        if ($tableWidth != '-') {
            $table = $this->_styleODTTable($odtTable->getTag(), $tableWidth);
        } else {
            $table = $odtTable->getTag();
        }
        return $table . $this->_renderODTColumns($tableWidth, $width, $columnCount);
    }

    function getTableWidth($inString) {
        preg_match('/<!-- table-width ([^\n]+?) -->/', $inString, $match);
        dbglog($match[0]);
        $width = preg_split('/\s+/', $match[1]);
        $tableWidth = array_shift($width);
        return array($tableWidth, $width);
    }

    function getColumnCount($odtString) {
        return preg_match_all('/<table:table-column.*?/', $odtString, $ignored);
    }

    function _styleODTTable($odt, $tableWidth) {
        preg_match('/<table:table(.*?)>/', $odt, $match);
        $attributes = $match[1];

        if (preg_match('/table:style-name/', $attributes)) {
            return $odt; // style already set
        }
        return "<table:table$attributes" . ' table:style-name="plugintablewidth_'.htmlspecialchars($tableWidth).'">';
    }

    function _renderODTColumns($tableWidth, $width, $columnCount) {
        $result = '';
        $tableWidth = 'plugintablewidth_'.htmlspecialchars($tableWidth);
        for ($i = 0; $i < $columnCount; $i++) {
            if (isset($width[$i]) && $width[$i] != '-') {
                $result .= '<table:table-column table:style-name="'."{$tableWidth}_$i".'" />';
            } else {
                $result .= '<table:table-column />';
            }
        }
        return $result;
    }
}

class TableWidthODTTable {
    private $comment;
    private $tag;
    private $columnSpec;
    private $startPosition;

    public function __construct($match) {
        $this->comment = $match[1][0];
        $this->tag = $match[2][0];
        $this->columnSpec = $match[3][0];
        $this->startPosition = $match[0][1];
        $this->textLength = strlen($match[0][0]);
    }

    public function getColumnSpec() {
        return $this->columnSpec;
    }

    public function getComment() {
        return $this->comment;
    }

    public function getTag() {
        return $this->tag;
    }

    public function getStartPosition() {
        return $this->startPosition;
    }

    public function getTextLength() {
        return $this->textLength;
    }
}