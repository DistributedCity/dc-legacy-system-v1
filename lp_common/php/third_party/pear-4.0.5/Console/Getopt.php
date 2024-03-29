<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001 The PHP Group             |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Andrei Zmievski <andrei@ispi.net>                           |
// +----------------------------------------------------------------------+
//
// $Id: Getopt.php,v 1.1.1.1 2002/06/19 00:15:30 gente_libre Exp $

require_once 'PEAR.php';

/**
 * Command-line options parsing class.
 *
 * @author Andrei Zmievski <andrei@ispi.net>
 *
 */
class Console_Getopt {
    /**
     * Parses the command-line options.
     *
     * The first parameter to this function should be the list of command-line
     * arguments without the leading reference to the running program.
     *
     * The second parameter is a string of allowed short options. Each of the
     * option letters can be followed by a colon ':' to specify that the option
     * requires an argument, or a double colon '::' to specify that the option
     * takes an optional argument.
     *
     * The third argument is an optional array of allowed long options. The
     * leading '--' should not be included in the option name. Options that
     * require an argument should be followed by '=', and options that take an
     * option argument should be followed by '=='.
     *
     * The return value is an array of two elements: the list of parsed
     * options and the list of non-option command-line arguments. Each entry in
     * the list of parsed options is a pair of elements - the first one
     * specifies the option, and the second one specifies the option argument,
     * if there was one.
     *
     * Long and short options can be mixed.
     *
     * Most of the semantics of this function are based on GNU getopt_long().
     * 
     * @param $args array an array of command-line arguments
     * @param $short_options string specifies the list of allowed short options
     * @param $long_options array specifies the list of allowed long options
     *
     * @return array two-element array containing the list of parsed options and
     * the non-option arguments
     *
     * @access public
     *
     */
    function getopt($args, $short_options, $long_options = null)
    {
        $opts     = array();
        $non_opts = array();

        settype($args, 'array');

        if ($long_options)
            sort($long_options);

        reset($args);
        while (list(, $arg) = each($args)) {

            /* The special element '--' means explicit end of options. Treat the
               rest of the arguments as non-options and end the loop. */
            if ($arg == '--') {
                $non_opts = array_merge($non_opts, array_slice($args, $i + 1));
                break;
            }

            if ($arg{0} != '-' || ($arg{1} == '-' && !$long_options)) {
                $non_opts[] = $arg;
            } else if ($arg{1} == '-') {
                $error = Console_Getopt::_parseLongOption(substr($arg, 2), $long_options, $opts, $args);
                if (PEAR::isError($error))
                    return $error;
            } else {
                $error = Console_Getopt::_parseShortOption(substr($arg, 1), $short_options, $opts, $args);
                if (PEAR::isError($error))
                    return $error;
            }
        }

        return array($opts, $non_opts);
    }

    /**
     * @access private
     *
     */
    function _parseShortOption($arg, $short_options, &$opts, &$args)
    {
        for ($i = 0; $i < strlen($arg); $i++) {
            $opt = $arg{$i};
            $opt_arg = null;

            /* Try to find the short option in the specifier string. */
            if (($spec = strstr($short_options, $opt)) === false || $arg{$i} == ':')
            {
                return new Getopt_Error("unrecognized option -- $opt\n");
            }

            if ($spec{1} == ':') {
                if ($spec{2} == ':') {
                    if ($i + 1 < strlen($arg)) {
                        /* Option takes an optional argument. Use the remainder of
                           the arg string if there is anything left. */
                        $opts[] = array($opt, substr($arg, $i + 1));
                        break;
                    }
                } else {
                    /* Option requires an argument. Use the remainder of the arg
                       string if there is anything left. */
                    if ($i + 1 < strlen($arg)) {
                        $opts[] = array($opt,  substr($arg, $i + 1));
                        break;
                    } else if (list(, $opt_arg) = each($args))
                        /* Else use the next argument. */;
                    else
                        return new Getopt_Error("option requires an argument -- $opt\n");
                }
            }

            $opts[] = array($opt, $opt_arg);
        }
    }

    /**
     * @access private
     *
     */
    function _parseLongOption($arg, $long_options, &$opts, &$args)
    {
        list($opt, $opt_arg) = explode('=', $arg);
        $opt_len = strlen($opt);

        for ($i = 0; $i < count($long_options); $i++) {
            $long_opt  = $long_options[$i];
            $opt_start = substr($long_opt, 0, $opt_len);
            
            /* Option doesn't match. Go on to the next one. */
            if ($opt_start != $opt)
                continue;

            $opt_rest  = substr($long_opt, $opt_len);

            /* Check that the options uniquely matches one of the allowed
               options. */
            if ($opt_rest != '' && $opt{0} != '=' &&
                $i + 1 < count($long_options) &&
                $opt == substr($long_options[$i+1], 0, $opt_len)) {
                return new Getopt_Error("option --$opt is ambiguous\n");
            }

            if (substr($long_opt, -1) == '=') {
                if (substr($long_opt, -2) != '==') {
                    /* Long option requires an argument.
                       Take the next argument if one wasn't specified. */;
                    if (!$opt_arg && !(list(, $opt_arg) = each($args))) {
                        return new Getopt_Error("option --$opt requires an argument\n");
                    }
                }
            } else if ($opt_arg) {
                return new Getopt_Error("option --$opt doesn't allow an argument\n");
            }

            $opts[] = array('--' . substr($long_opt, 0, strpos($long_opt, '=')), $opt_arg);
            return;
        }

        return new Getopt_Error("unrecognized option --$opt\n");
    }
}


class Getopt_Error extends PEAR_Error {
    var $classname             = 'Getopt';
    var $error_message_prepend = 'Error in Getopt';

    function Getopt_Error($message, $code = 0, $mode = PEAR_ERROR_RETURN, $level = E_USER_NOTICE)
    {
        $this->PEAR_Error($message, $code, $mode, $level);
    }
}

?>
