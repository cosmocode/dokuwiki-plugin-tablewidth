<?php

class RenderODTColumns extends DokuWikiTest {

    function testColumns() {
        $plugin = new action_plugin_tablewidth();

        $expected = '<table:table-column table:style-name="plugintablewidth_1_0" />'
                  . '<table:table-column />'
                  . '<table:table-column table:style-name="plugintablewidth_1_2" />'
                  . '<table:table-column />'
                  . '<table:table-column table:style-name="plugintablewidth_1_4" />';
        $this->assertEquals($expected, $plugin->_renderODTColumns('1', array('3', '-', '3', '-', '3'), 5));
    }

    function testColumns1() {
        $plugin = new action_plugin_tablewidth();

        $expected = '<table:table-column table:style-name="plugintablewidth_1_0" />'
            . '<table:table-column />'
            . '<table:table-column table:style-name="plugintablewidth_1_2" />'
            . '<table:table-column />'
            . '<table:table-column />';
        $this->assertEquals($expected, $plugin->_renderODTColumns('1', array('3px', '-', '3pt'), 5));
    }

    function testStyleODTTable() {
        $plugin = new action_plugin_tablewidth();

        $input = '<table:table>';
        $expected = '<table:table table:style-name="plugintablewidth_1">';
        $this->assertEquals($expected, $plugin->_styleODTTable($input, 1));

    }

    function testStyleODTTableWithAttributes() {
        $plugin = new action_plugin_tablewidth();

        $input = '<table:table attribute="value">';
        $expected = '<table:table attribute="value" table:style-name="plugintablewidth_1">';
        $this->assertEquals($expected, $plugin->_styleODTTable($input, 1));
    }

    function testGetTableWidth() {
        $plugin = new action_plugin_tablewidth();

        $input = '<!-- table-width 1 3 -->';
        $expected = array('1', array('3'));
        $this->assertEquals($expected, $plugin->getTableWidth($input));
    }

    function testGetTableWidth1() {
        $plugin = new action_plugin_tablewidth();

        $input = '<!-- table-width 1 3 - 3 - 5 -->';
        $expected = array('1', array('3', '-', '3', '-', '5'));
        $this->assertEquals($expected, $plugin->getTableWidth($input));
    }

    function testGetColumnCount() {
        $plugin = new action_plugin_tablewidth();

        $input = '<table:table-column/><table:table-column/><table:table-column/><table:table-column/><table:table-column/>';
        $this->assertEquals(5, $plugin->getColumnCount($input));
    }

    function testGetODTTables() {
        $plugin = new action_plugin_tablewidth();
        $input = '<!-- table-width 0 5 -->'
                . '<table:table>'
                . '<table:table-column/>'
                . '<table:table-column/>'
                . '<table:table-column/>'
                . '<table:table-column/>'
                . '<table:table-column/>'
                . '<table:table-row>';

        $expect = array(
            new TableWidthODTTable(
                array(
                    array($input, 0),
                    array('<!-- table-width 0 5 -->'),
                    array('<table:table>'),
                    array('<table:table-column/><table:table-column/><table:table-column/><table:table-column/><table:table-column/>')
                )
            )
        );

        $this->assertEquals($expect, $plugin->getODTTables(($input)));
    }

}