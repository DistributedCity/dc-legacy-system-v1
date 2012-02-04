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

// some utilities
include("xmlrpc_utils.php");

// ensure extension is loaded.
xu_load_extension();


$request_xml = <<< END
<?xml version="1.0"?>
<methodCall>
   <methodName>greeting</methodName>
   <params>
      <param>
         <value><string>Dan</string></value>
      </param>
   </params>
</methodCall>
END;


function greeting_func($method_name, $params, $app_data) {
   $name = $params[0];

   return array("hello $name. How are you today?");
}

// create server
$xmlrpc_server = xmlrpc_server_create();

if($xmlrpc_server) {
    // register methods
    if(!xmlrpc_server_register_method($xmlrpc_server, "greeting", "greeting_func")) {
     die("<h2>method registration failed.</h2>");
    }

    // parse xml and call method
    $foo = xmlrpc_server_call_method($xmlrpc_server, $request_xml, $response, array(output_type => "php"));
    echo "<h1>PHP Value</h1><xmp>\n";
    var_dump($foo);
    echo "\n</xmp>";

    $foo = xmlrpc_server_call_method($xmlrpc_server, $request_xml, $response, array(output_type => "xml"));
    echo "<h1>XML Response</h1><xmp>$foo</xmp>\n";

    // free server resources
    $success = xmlrpc_server_destroy($xmlrpc_server);
}
?>
