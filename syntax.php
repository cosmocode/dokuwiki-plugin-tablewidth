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
require_once(DOKU_PLUGIN . 'syntax.php');

class syntax_plugin_tablewidth extends DokuWiki_Syntax_Plugin {

    var $mode;

    function syntax_plugin_tablewidth() {
        $this->mode = substr(get_class($this), 7);
    }

    function getType() {
        return 'substition';
    }

    function getPType() {
        return 'block';
    }

    function getSort() {
        return 5;
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\n\|<[^\n]+?>\|(?=\s*?\n[|^])', $mode, $this->mode);
    }

    function handle($match, $state, $pos, &$handler) {
        if ($state == DOKU_LEXER_SPECIAL) {
            if (preg_match('/\|<\s*(.+?)\s*>\|/', $match, $match) != 1) {
                return false;
            }
            return array($match[1]);
        }
        return false;
    }

    function render($mode, &$renderer, $data) {
        if ($mode === 'xhtml' || $mode === 'xml') {
            $renderer->doc .= '<!-- table-width ' . $data[0] . ' -->' . DOKU_LF;
            return true;
        }
        if ($mode === 'odt') {
            static $tableCounter = 0;

            $line = preg_split('/\s+/', $data[0]);
            array_shift($line);

            $widths = preg_split('/\s+/', $data[0]);
            $tableWidth = array_shift($widths);

            $tableName = "plugintablewidth_$tableCounter";
            $attr = $this->cssToOdtUnit($tableWidth, true);
            if ($tableWidth !== '-' && $attr !== '') {
                $renderer->autostyles[$tableName] = '
                        <style:style style:name="'.$tableName.'" style:family="table">
                            <style:table-properties '.$attr.' fo:margin-left="0cm" table:align="left" />
                        </style:style>';
            }
            $i = 0;
            foreach ($widths as $width) {
                if ($width !== '-') {
                    $attr = $this->cssToOdtUnit($width);
                    if ($attr === '') {
                        continue;
                    }
                    $renderer->autostyles["{$tableName}_$i"] = '
                            <style:style style:name="'."{$tableName}_$i".'" style:family="table-column">
                                <style:table-column-properties '.$attr.' />
                            </style:style>';
                }
                $i++;
            }

            $renderer->doc .= "<!-- table-width $tableCounter ".implode(' ', $line)." -->" . DOKU_LF;

            $tableCounter++;
            return true;
        }

        return false;
    }

    function escape($str) {
        return htmlspecialchars($str);
    }

    function cssToOdtUnit($input, $table = false) {
        $input = strtolower($input);
        $pre = $table ? '' : 'column-';


        if (substr($input, -2) === 'pt') {
            return "style:{$pre}width=\"" . $this->ptToMM(intval(substr($input, 0, -2))) . 'mm"';
        }
        if (substr($input, -2) === 'px') {
            return "style:{$pre}width=\"" . $this->ptToMM(intval(substr($input, 0, -2))) . 'mm"';
        }
        if (substr($input, -1) === '%') {
            return "style:rel-{$pre}width=\"" . $this->escape($input) . '"';
        }

        return '';
    }

    function ptToMM($input) {
        return round($input * 0.353);
    }

    function pxToMM($input) {
        return round($input/72*25.4);
    }

}
