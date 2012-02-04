<?php
//
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2001 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stig Bakken <ssb@fast.no>                                   |
// |                                                                      |
// +----------------------------------------------------------------------+
//

require_once "PEAR.php";

/*

Tests that need to be made:
- error class
- mixing character encodings
- a test using all expat handlers
- options (folding, output charset)
- different parsing modes

*/

/**
 * XML Parser class.  This is an XML parser based on PHP's "xml" extension,
 * based on the bundled expat library.
 *
 * @author Stig Bakken <ssb@fast.no>
 *
 */
class XML_Parser extends PEAR {
    // {{{ properties

    var $parser;
    var $fp;
    var $folding = true;
    var $mode;

    // }}}
    // {{{ constructor()

    function XML_Parser($charset = 'UTF-8', $mode = "event") {
	$this->PEAR();
	$xp = @xml_parser_create($charset);
	if (is_resource($xp)) {
	    $this->parser = $xp;
	    $this->setMode($mode);
	    xml_parser_set_option($xp, XML_OPTION_CASE_FOLDING,
				  $this->folding);
	}
    }

    // }}}

    // {{{ setMode()

    function setMode($mode) {
	$this->mode = $mode;
	$xp = $this->parser;
	xml_set_object($xp, $this);
	switch ($mode) {
	    case "func":
		xml_set_element_handler($xp, "funcStartHandler", "funcEndHandler");
		break;
	    case "event":
		if (method_exists($this, "startHandler") ||
		    method_exists($this, "endHandler")) {
		    xml_set_element_handler($xp, "startHandler", "endHandler");
		}
	}
	if (method_exists($this, "cdataHandler")) {
	    xml_set_character_data_handler($xp, "cdataHandler");
	} else {
	    xml_set_character_data_handler($xp, "");
	}
	if (method_exists($this, "defaultHandler")) {
	    xml_set_default_handler($xp, "defaultHandler");
	} else {
	    xml_set_default_handler($xp, "");
	}
	if (method_exists($this, "piHandler")) {
	    xml_set_processing_instruction_handler($xp, "piHandler");
	} else {
	    xml_set_processing_instruction_handler($xp, "");
	}
	if (method_exists($this, "unparsedHandler")) {
	    xml_set_unparsed_entity_decl_handler($xp, "unparsedHandler");
	} else {
	    xml_set_unparsed_entity_decl_handler($xp, "");
	}
	if (method_exists($this, "notationHandler")) {
	    xml_set_notation_decl_handler($xp, "notationHandler");
	} else {
	    xml_set_notation_decl_handler($xp, "");
	}
	if (method_exists($this, "entityrefHandler")) {
	    xml_set_external_entity_ref_handler($xp, "entityrefHandler");
	} else {
	    xml_set_external_entity_ref_handler($xp, "");
	}
    }

    // }}}
    // {{{ setInputFile()

    function setInputFile($file) {
	$fp = @fopen($file, "r");
	if (is_resource($fp)) {
	    $this->fp = $fp;
	    return $fp;
	}
	return new XML_Parser_Error($php_errormsg);
    }

    // }}}
    // {{{ setInput()

    function setInput($fp) {
	if (is_resource($fp)) {
	    $this->fp = $fp;
	    return true;
	}
	return new XML_Parser_Error("not a file resource");
    }

    // }}}
    // {{{ parse()

    function parse() {
	if (!is_resource($this->fp)) {
	    return new XML_Parser_Error("no input");
	}
	if (!is_resource($this->parser)) {
	    return new XML_Parser_Error("no parser");
	}
	while ($data = fread($this->fp, 2048)) {
	    $err = $this->parseString($data, feof($this->fp));
	    if (PEAR::isError($err)) {
		return $err;
	    }
	}
	return true;
    }

    // }}}
    // {{{ parseString()

    function parseString($data, $eof = false) {
	if (!is_resource($this->parser)) {
	    return new XML_Parser_Error("no parser");
	}
	if (!xml_parse($this->parser, $data, $eof)) {
	    $err = new XML_Parser_Error($this->parser);
	    xml_parser_free($this->parser);
	    return $err;
	}
	return true;
    }

    // }}}
    // {{{ funcStartHandler()

    function funcStartHandler($xp, $elem, $attribs) {
	if (method_exists($this, $elem)) {
	    call_user_method($elem, $this, $xp, $elem, &$attribs);
	}
    }

    // }}}
    // {{{ funcEndHandler()

    function funcEndHandler($xp, $elem) {
	$func = $elem . '_';
	if (method_exists($this, $func)) {
	    call_user_method($func, $this, $xp, $elem);
	}
    }

    // }}}
}

class XML_Parser_Error extends PEAR_Error {
    // {{{ properties

    var $error_message_prefix = 'XML_Parser: ';

    // }}}
    // {{{ constructor()

    function XML_Parser_Error($msgorparser = 'unknown error',
			      $code = 0,
			      $mode = PEAR_ERROR_RETURN,
			      $level = E_USER_NOTICE) {
	if (is_resource($msgorparser)) {
	    $code = xml_get_error_code($msgorparser);
	    $msgorparser =
		sprintf("%s at XML input line %d",
			xml_error_string(xml_get_error_code($msgorparser)),
			xml_get_current_line_number($msgorparser));
	}
	$this->PEAR_Error($msgorparser, $code, $mode, $level);
    }

    // }}}
}

?>
