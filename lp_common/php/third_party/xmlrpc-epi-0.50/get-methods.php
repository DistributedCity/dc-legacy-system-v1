<?php

/*
  This file is part of, or distributed with, libXMLRPC - a C library for 
  xml-encoded function calls.

  Author: Dan Libby (dan@libby.com)
  Epinions.com may be contacted at feedback@epinions-inc.com
*/

/*  
  Copyright 2001 Epinions, Inc. 

  Subject to the following 3 conditions, Epinions, Inc.  permits you, free 
  of charge, to (a) use, copy, distribute, modify, perform and display this 
  software and associated documentation files (the "Software"), and (b) 
  permit others to whom the Software is furnished to do so as well.  

  1) The above copyright notice and this permission notice shall be included 
  without modification in all copies or substantial portions of the 
  Software.  

  2) THE SOFTWARE IS PROVIDED "AS IS", WITHOUT ANY WARRANTY OR CONDITION OF 
  ANY KIND, EXPRESS, IMPLIED OR STATUTORY, INCLUDING WITHOUT LIMITATION ANY 
  IMPLIED WARRANTIES OF ACCURACY, MERCHANTABILITY, FITNESS FOR A PARTICULAR 
  PURPOSE OR NONINFRINGEMENT.  

  3) IN NO EVENT SHALL EPINIONS, INC. BE LIABLE FOR ANY DIRECT, INDIRECT, 
  SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES OR LOST PROFITS ARISING OUT 
  OF OR IN CONNECTION WITH THE SOFTWARE (HOWEVER ARISING, INCLUDING 
  NEGLIGENCE), EVEN IF EPINIONS, INC.  IS AWARE OF THE POSSIBILITY OF SUCH 
  DAMAGES.    

*/


/* get-methods.php
 *
 * A script to demonstrate usage of xmlrpc-epi-php as a client.  It is
 * also a useful tool for method introspection of servers.
 * 
 * author: Dan Libby (dan@libby.com)
 */

// some general purpose network utils developed while writing this client.
// This file is where the meat is, but it is still small since most
// functionality is in C code.
include_once("utils/utils.php");
include_once("utils/server_lists.php");

// a method to retrieve remote method list and display
function get_methods($server, $output, $debug=0) {
   $result =  xu_rpc_http_concise(array(method => "system.listMethods", 
													 args => $server[args], 
													 host => $server[host], 
													 uri  => $server[uri], 
													 port => $server[port], 
													 debug => $debug,
													 output => $output));

   echo "<h3>PHP Native Results for " . $server[title] . " printed via print_r()</h3>";
   echo "<xmp>";
   print_r($result);
   echo "</xmp>";

}

function print_html_form($server_list) {
   echo"<h1>Choose an xmlrpc server to inspect <i>live!</i></h1>";

   print_servers_form($server_list);

   echo <<< END
	<p>
   <i>if you know of any other servers that support introspection, please send a note to
   <a href='mailto:xmlrpc-epi-devel@lists.sourceforge.net'>xmlrpc-epi-devel@lists.sourceforge.net</a>
   and we'll add it to the list</i>.
END;
}

// some code which determines if we are in form display or response mode.
$server_list = get_intro_useful_servers();
$server = get_server_from_user($server_list);
if($server) {
   get_methods($server, array(version =>$GLOBALS[HTTP_GET_VARS][version]), $GLOBALS[HTTP_GET_VARS][debug]);
}
else {
   print_html_form($server_list);
}

?>
