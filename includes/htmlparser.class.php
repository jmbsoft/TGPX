<?php

//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Alexander Zhukov <alex@veresk.ru> Original port from Python |
// | Authors: Harry Fuecks <hfuecks@phppatterns.com> Port to PEAR + more  |
// | Authors: Many @ Sitepointforums Advanced PHP Forums                  |
// +----------------------------------------------------------------------+
//
// $Id: htmlparser.class.php,v 1.1.1.1 2007/07/02 20:45:05 jmbsoft Exp $
//




class XML_HTMLSax_StateParser {

    var $htmlsax;

    var $handler_object_element;

    var $handler_method_opening;

    var $handler_method_closing;

    var $handler_object_data;

    var $handler_method_data;

    var $handler_object_pi;

    var $handler_method_pi;

    var $handler_object_jasp;

    var $handler_method_jasp;

    var $handler_object_escape;

    var $handler_method_escape;

    var $handler_default;

    var $parser_options = array();

    var $rawtext;

    var $position;

    var $length;

    var $State = array();

    function XML_HTMLSax_StateParser (& $htmlsax) {
        $this->htmlsax = & $htmlsax;
        $this->State[XML_HTMLSAX_STATE_START] =& new XML_HTMLSax_StartingState();

        $this->State[XML_HTMLSAX_STATE_CLOSING_TAG] =& new XML_HTMLSax_ClosingTagState();
        $this->State[XML_HTMLSAX_STATE_TAG] =& new XML_HTMLSax_TagState();
        $this->State[XML_HTMLSAX_STATE_OPENING_TAG] =& new XML_HTMLSax_OpeningTagState();

        $this->State[XML_HTMLSAX_STATE_PI] =& new XML_HTMLSax_PiState();
        $this->State[XML_HTMLSAX_STATE_JASP] =& new XML_HTMLSax_JaspState();
        $this->State[XML_HTMLSAX_STATE_ESCAPE] =& new XML_HTMLSax_EscapeState();

        $this->parser_options['XML_OPTION_TRIM_DATA_NODES'] = 0;
        $this->parser_options['XML_OPTION_CASE_FOLDING'] = 0;
        $this->parser_options['XML_OPTION_LINEFEED_BREAK'] = 0;
        $this->parser_options['XML_OPTION_TAB_BREAK'] = 0;
        $this->parser_options['XML_OPTION_ENTITIES_PARSED'] = 0;
        $this->parser_options['XML_OPTION_ENTITIES_UNPARSED'] = 0;
        $this->parser_options['XML_OPTION_FULL_ESCAPES'] = 0;
    }

    function Cleanup()
    {
        foreach( $this->State as $index => $state )
        {
            unset($this->State[$index]);
        }

        foreach( $this->parser_options as $index => $state )
        {
            unset($this->parser_options[$index]);
        }

        unset($this->htmlsax);
        unset($this->handler_object_element);
        unset($this->handler_method_opening);
        unset($this->handler_method_closing);
        unset($this->handler_object_data);
        unset($this->handler_method_data);
        unset($this->handler_object_pi);
        unset($this->handler_method_pi);
        unset($this->handler_object_jasp);
        unset($this->handler_method_jasp);
        unset($this->handler_object_escape);
        unset($this->handler_method_escape);
        unset($this->handler_default);
        unset($this->parser_options);
        unset($this->rawtext);
        unset($this->position);
        unset($this->length);
        unset($this->State);
    }

    function unscanCharacter() {
        $this->position -= 1;
    }

    function ignoreCharacter() {
        $this->position += 1;
    }

    function scanCharacter() {
        if ($this->position < $this->length) {
            return $this->rawtext{$this->position++};
        }
    }

    function scanUntilString($string) {
        $start = $this->position;
        $this->position = strpos($this->rawtext, $string, $start);
        if ($this->position === FALSE) {
            $this->position = $this->length;
        }
        return substr($this->rawtext, $start, $this->position - $start);
    }

    function parse($data) {
        if ($this->parser_options['XML_OPTION_TRIM_DATA_NODES']==1) {
            $decorator =& new XML_HTMLSax_Trim(
                $this->handler_object_data,
                $this->handler_method_data);
            $this->handler_object_data =& $decorator;
            $this->handler_method_data = 'trimData';
        }
        if ($this->parser_options['XML_OPTION_CASE_FOLDING']==1) {
            $open_decor =& new XML_HTMLSax_CaseFolding(
                $this->handler_object_element,
                $this->handler_method_opening,
                $this->handler_method_closing);
            $this->handler_object_element =& $open_decor;
            $this->handler_method_opening ='foldOpen';
            $this->handler_method_closing ='foldClose';
        }
        if ($this->parser_options['XML_OPTION_LINEFEED_BREAK']==1) {
            $decorator =& new XML_HTMLSax_Linefeed(
                $this->handler_object_data,
                $this->handler_method_data);
            $this->handler_object_data =& $decorator;
            $this->handler_method_data = 'breakData';
        }
        if ($this->parser_options['XML_OPTION_TAB_BREAK']==1) {
            $decorator =& new XML_HTMLSax_Tab(
                $this->handler_object_data,
                $this->handler_method_data);
            $this->handler_object_data =& $decorator;
            $this->handler_method_data = 'breakData';
        }
        if ($this->parser_options['XML_OPTION_ENTITIES_UNPARSED']==1) {
            $decorator =& new XML_HTMLSax_Entities_Unparsed(
                $this->handler_object_data,
                $this->handler_method_data);
            $this->handler_object_data =& $decorator;
            $this->handler_method_data = 'breakData';
        }
        if ($this->parser_options['XML_OPTION_ENTITIES_PARSED']==1) {
            $decorator =& new XML_HTMLSax_Entities_Parsed(
                $this->handler_object_data,
                $this->handler_method_data);
            $this->handler_object_data =& $decorator;
            $this->handler_method_data = 'breakData';
        }
        $this->rawtext = $data;
        $this->length = strlen($data);
        $this->position = 0;
        $this->_parse();
    }

    function _parse($state = XML_HTMLSAX_STATE_START) {
        do {
            $state = $this->State[$state]->parse($this);
        } while ($state != XML_HTMLSAX_STATE_STOP &&
                    $this->position < $this->length);
    }

    function scanUntilCharacters($string) {
        $startpos = $this->position;
        $length = strcspn($this->rawtext, $string, $startpos);
        $this->position += $length;
        return substr($this->rawtext, $startpos, $length);
    }

    function ignoreWhitespace() {
        $this->position += strspn($this->rawtext, " \n\r\t", $this->position);
    }
}

class XML_HTMLSax_NullHandler {

    function DoNothing() {
    }
}

class XML_HTMLSax /* extends Pear */ {

    var $state_parser;

    function XML_HTMLSax() {
        $this->state_parser =& new XML_HTMLSax_StateParser($this);
        $nullhandler =& new XML_HTMLSax_NullHandler();
        $this->set_object($nullhandler);
        $this->set_element_handler('DoNothing', 'DoNothing');
        $this->set_data_handler('DoNothing');
        $this->set_pi_handler('DoNothing');
        $this->set_jasp_handler('DoNothing');
        $this->set_escape_handler('DoNothing');
    }

    function Cleanup()
    {
        $this->state_parser->Cleanup();
        unset($this->state_parser);
    }

    function set_object(&$object) {
        //if ( is_object($object) ) {
            $this->state_parser->handler_default =& $object;
        //    return true;
        //} else {
        //    return $this->raiseError('XML_HTMLSax::set_object requires '.
        //        'an object instance');
        //}
    }

    function set_option($name, $value=1) {
        //print_r($this->state_parser->parser_options);
        //if ( array_key_exists($name, $this->state_parser->parser_options) ) {
            $this->state_parser->parser_options[$name] = $value;
            return true;
        //} else {

        //}
    }

    function set_data_handler($data_method) {
        $this->state_parser->handler_object_data =& $this->state_parser->handler_default;
        $this->state_parser->handler_method_data = $data_method;
    }

    function set_element_handler($opening_method, $closing_method) {
        $this->state_parser->handler_object_element =& $this->state_parser->handler_default;
        $this->state_parser->handler_method_opening = $opening_method;
        $this->state_parser->handler_method_closing = $closing_method;
    }

    function set_pi_handler($pi_method) {
        $this->state_parser->handler_object_pi =& $this->state_parser->handler_default;
        $this->state_parser->handler_method_pi = $pi_method;
    }

    function set_escape_handler($escape_method) {
        $this->state_parser->handler_object_escape =& $this->state_parser->handler_default;
        $this->state_parser->handler_method_escape = $escape_method;
    }

    function set_jasp_handler ($jasp_method) {
        $this->state_parser->handler_object_jasp =& $this->state_parser->handler_default;
        $this->state_parser->handler_method_jasp = $jasp_method;
    }

    function get_current_position() {
        return $this->state_parser->position;
    }

    function get_length() {
        return $this->state_parser->length;
    }

    function parse($data) {
        $this->state_parser->parse($data);
    }
}


class XML_HTMLSax_Trim {

    var $orig_obj;

    var $orig_method;

    function XML_HTMLSax_Trim(&$orig_obj, $orig_method) {
        $this->orig_obj =& $orig_obj;
        $this->orig_method = $orig_method;
    }

    function trimData(&$parser, $data) {
        $data = trim($data);
        if ($data != '') {
            $this->orig_obj->{$this->orig_method}($parser, $data);
        }
    }
}

class XML_HTMLSax_CaseFolding {

    var $orig_obj;

    var $orig_open_method;

    var $orig_close_method;

    function XML_HTMLSax_CaseFolding(&$orig_obj, $orig_open_method, $orig_close_method) {
        $this->orig_obj =& $orig_obj;
        $this->orig_open_method = $orig_open_method;
        $this->orig_close_method = $orig_close_method;
    }

    function foldOpen(&$parser, $tag, $attrs=array(), $empty = FALSE) {
        $this->orig_obj->{$this->orig_open_method}($parser, strtoupper($tag), $attrs, $empty);
    }

    function foldClose(&$parser, $tag, $empty = FALSE) {
        $this->orig_obj->{$this->orig_close_method}($parser, strtoupper($tag), $empty);
    }
}

class XML_HTMLSax_Linefeed {

    var $orig_obj;

    var $orig_method;

    function XML_HTMLSax_LineFeed(&$orig_obj, $orig_method) {
        $this->orig_obj =& $orig_obj;
        $this->orig_method = $orig_method;
    }

    function breakData(&$parser, $data) {
        $data = explode("\n",$data);
        foreach ( $data as $chunk ) {
            $this->orig_obj->{$this->orig_method}($parser, $chunk);
        }
    }
}

class XML_HTMLSax_Tab {

    var $orig_obj;

    var $orig_method;

    function XML_HTMLSax_Tab(&$orig_obj, $orig_method) {
        $this->orig_obj =& $orig_obj;
        $this->orig_method = $orig_method;
    }

    function breakData(&$parser, $data) {
        $data = explode("\t",$data);
        foreach ( $data as $chunk ) {
            $this->orig_obj->{$this->orig_method}($this, $chunk);
        }
    }
}

class XML_HTMLSax_Entities_Parsed {

    var $orig_obj;

    var $orig_method;

    function XML_HTMLSax_Entities_Parsed(&$orig_obj, $orig_method) {
        $this->orig_obj =& $orig_obj;
        $this->orig_method = $orig_method;
    }

    function breakData(&$parser, $data) {
        $data = preg_split('/(&.+?;)/',$data,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ( $data as $chunk ) {
            $chunk = html_entity_decode($chunk,ENT_NOQUOTES);
            $this->orig_obj->{$this->orig_method}($this, $chunk);
        }
    }
}

class XML_HTMLSax_Entities_Unparsed {

    var $orig_obj;

    var $orig_method;

    function XML_HTMLSax_Entities_Unparsed(&$orig_obj, $orig_method) {
        $this->orig_obj =& $orig_obj;
        $this->orig_method = $orig_method;
    }

    function breakData(&$parser, $data) {
        $data = preg_split('/(&.+?;)/',$data,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ( $data as $chunk ) {
            $this->orig_obj->{$this->orig_method}($this, $chunk);
        }
    }
}


define('XML_HTMLSAX_STATE_STOP', 0);
define('XML_HTMLSAX_STATE_START', 1);
define('XML_HTMLSAX_STATE_TAG', 2);
define('XML_HTMLSAX_STATE_OPENING_TAG', 3);
define('XML_HTMLSAX_STATE_CLOSING_TAG', 4);
define('XML_HTMLSAX_STATE_ESCAPE', 6);
define('XML_HTMLSAX_STATE_JASP', 7);
define('XML_HTMLSAX_STATE_PI', 8);

class XML_HTMLSax_StartingState  {

    function parse(&$context) {
        $data = $context->scanUntilString('<');
        if ($data != '') {
            $context->handler_object_data->
                {$context->handler_method_data}($context->htmlsax, $data);
        }
        $context->IgnoreCharacter();
        return XML_HTMLSAX_STATE_TAG;
    }
}

class XML_HTMLSax_TagState {

    function parse(&$context) {
        switch($context->ScanCharacter()) {
        case '/':
            return XML_HTMLSAX_STATE_CLOSING_TAG;
            break;
        case '?':
            return XML_HTMLSAX_STATE_PI;
            break;
        case '%':
            return XML_HTMLSAX_STATE_JASP;
            break;
        case '!':
            return XML_HTMLSAX_STATE_ESCAPE;
            break;
        default:
            $context->unscanCharacter();
            return XML_HTMLSAX_STATE_OPENING_TAG;
        }
    }
}

class XML_HTMLSax_ClosingTagState {

    function parse(&$context) {
        $tag = strtolower($context->scanUntilCharacters('/>'));
        if ($tag != '') {
            $char = $context->scanCharacter();
            if ($char == '/') {
                $char = $context->scanCharacter();
                if ($char != '>') {
                    $context->unscanCharacter();
                }
            }
            $context->handler_object_element->
                {$context->handler_method_closing}($context->htmlsax, $tag, FALSE);
        }
        return XML_HTMLSAX_STATE_START;
    }
}

class XML_HTMLSax_OpeningTagState {

    function parseAttributes(&$context) {
        $Attributes = array();

        $context->ignoreWhitespace();
        $attributename = $context->scanUntilCharacters("=/> \n\r\t");
        while ($attributename != '') {
            $attributevalue = NULL;
            $context->ignoreWhitespace();
            $char = $context->scanCharacter();
            if ($char == '=') {
                $context->ignoreWhitespace();
                $char = $context->ScanCharacter();
                if ($char == '"') {
                    $attributevalue= $context->scanUntilString('"');
                    $context->IgnoreCharacter();
                } else if ($char == "'") {
                    $attributevalue = $context->scanUntilString("'");
                    $context->IgnoreCharacter();
                } else {
                    $context->unscanCharacter();
                    $attributevalue =
                        $context->scanUntilCharacters("> \n\r\t");
                }
            } else if ($char !== NULL) {
                $attributevalue = true;
                $context->unscanCharacter();
            }
            $Attributes[strtolower($attributename)] = $attributevalue;

            $context->ignoreWhitespace();
            $attributename = $context->scanUntilCharacters("=/> \n\r\t");
        }
        return $Attributes;
    }

    function parse(&$context) {
        $tag = strtolower($context->scanUntilCharacters("/> \n\r\t"));
        if ($tag != '') {
            $this->attrs = array();
            $Attributes = $this->parseAttributes($context);
            $char = $context->scanCharacter();
            if ($char == '/') {
                $char = $context->scanCharacter();
                if ($char != '>') {
                    $context->unscanCharacter();
                }
                $context->handler_object_element->
                    {$context->handler_method_opening}($context->htmlsax, $tag,
                    $Attributes, TRUE);
                $context->handler_object_element->
                    {$context->handler_method_closing}($context->htmlsax, $tag,
                    TRUE);
            } else {
                $context->handler_object_element->
                    {$context->handler_method_opening}($context->htmlsax, $tag,
                    $Attributes, FALSE);
            }
        }
        return XML_HTMLSAX_STATE_START;
    }
}

class XML_HTMLSax_EscapeState {

    function parse(&$context) {
        if ($context->parser_options['XML_OPTION_FULL_ESCAPES']==0) {
            $char = $context->ScanCharacter();
            if ($char == '-') {
                $char = $context->ScanCharacter();
                if ($char == '-') {
                    $text = $context->scanUntilString('-->');
                    $context->IgnoreCharacter();
                    $context->IgnoreCharacter();
                } else {
                    $context->unscanCharacter();
                    $text = $context->scanUntilString('>');
                }
            } else if ( $char == '[') {
                $context->scanUntilString('CDATA[');
                for ( $i=0;$i<6;$i++ ) {
                    $context->IgnoreCharacter();
                }
                $text = $context->scanUntilString(']]>');
                $context->IgnoreCharacter();
                $context->IgnoreCharacter();
            } else {
                $context->unscanCharacter();
                $text = $context->scanUntilString('>');
            }
        } else {
            $text = $context->scanUntilString('>');
        }
        $context->IgnoreCharacter();
        if ($text != '') {
            $context->handler_object_escape->{$context->handler_method_escape}($context->htmlsax, $text);
        }
        return XML_HTMLSAX_STATE_START;
    }
}

class XML_HTMLSax_JaspState {

    function parse(&$context) {
        $text = $context->scanUntilString('%>');
        if ($text != '') {
            $context->handler_object_jasp->
                {$context->handler_method_jasp}($context->htmlsax, $text);
        }
        $context->IgnoreCharacter();
        $context->IgnoreCharacter();
        return XML_HTMLSAX_STATE_START;
    }
}

class XML_HTMLSax_PiState {

    function parse(&$context) {
        $target = $context->scanUntilCharacters(" \n\r\t");
        $data = $context->scanUntilString('?>');
        if ($data != '') {
            $context->handler_object_pi->
            {$context->handler_method_pi}($context->htmlsax, $target, $data);
        }
        $context->IgnoreCharacter();
        $context->IgnoreCharacter();
        return XML_HTMLSAX_STATE_START;
    }
}
?>