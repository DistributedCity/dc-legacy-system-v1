<?php

/*
 * This example demonstrates 2 things:
 *  1) Method documentation via introspection with the xml callback mechanism. Note that this is not
 *     enabled by default.  You must uncomment the call to setup_server();
 *  2) Calling and displaying introspection data.
 *
 */


// some utilities

include("xmlrpc_utils.php");

$server = xmlrpc_server_create();


/* This function is called if/when someone requests introspection data.  why not just call
 * it every time?  Because parsing the xml and creating documentation is expensive, so
 * we only do it if necessary.
 */
function introspection_cb($userData) {
   /* if our xml is not parsed correctly, a php warning will be displayed.
    * however, very little structural validation is done, so we must be careful.
    */
   return <<< END
<?xml version='1.0'?>

<introspection version='1.0'>

 <methodList>
  <methodDescription name='introspection.hello'>

   <author>Dan Libby</author>
   <purpose>greets the caller and demonstrates use of introspection mechanism</purpose>

   <signatures>
    <signature>
     <params>
      <value type='string' name='name'>name of the caller</value>
     </params>
     <returns>
      <value type='string'>a greeting to the caller, or to John Doe.</value>
     </returns>
    </signature>
   </signatures>

   <see><item>system.listMethods</item></see>
   <examples/>
   <errors>
      <item>returns fault code 1 if the caller's name is not specified</item>
   </errors>
   <notes>
    <item>this is a lame example</item>
    <item>example of multiple notes</item>
   </notes>
   <bugs/>
   <todo/>
         
  </methodDescription>
 </methodList>

</introspection>   

END;
}

/* the method must actually exist, or the introspection data will not be accepted by the server */
function hello($method, $params, $userdata) {
   $name = $params[0];
   if(!$name) {
      return xu_fault_code(1, "missing first parameter");
   }

   return "hello $name";
}

function setup_server($server) {
   if(!xmlrpc_server_register_method($server, "introspection.hello", "hello")) {
      die("method registration failed");
   }
   if(!xmlrpc_server_register_introspection_callback($server, "introspection_cb")) {
      die("introspection registration failed");
   }
}

/* uncommenting this will cause the "introspection.hello" method to be registered, and
 * introspection_cb() to be called when system.describeMethods is executed.
 */
setup_server($server);

/* a quick hack to allow a ?method=foo get var such that we request & display docs for only
 * a single method
 */
$params = null;
if($HTTP_GET_VARS[method]) {
   $params = array(array($HTTP_GET_VARS[method]));
}

/* encode the request as xml. sort of back-asswards, but normally we would be receiving
 * xml from a client somewhere, not making a request to ourself!
 */
$request_xml = xmlrpc_encode_request("system.describeMethods", $params);

/* make the call.  This will trigger both of our callbacks. weee! */
$response = xmlrpc_server_call_method($server, $request_xml, null, array(output_type => "php"));

echo "<body bgcolor='white'>";

/* This php function formats the response as html.  
 * It is pretty ugly and could use a re-write.
 * You can always write your own instead. It would
 * be very cool to have a set of function suitable for
 * displaying method docs on multiple pages and
 * linking between them.
 */
echo xi_format($response);


/* The rest is random html nonsense for displaying a form and possibly
 * showing the various serializations of the data. left as an exercise for
 * the reader.
 */
$display = array();
if($HTTP_GET_VARS[verbosity]) {
   $display[verbosity] = $HTTP_GET_VARS[verbosity];
}
if($HTTP_GET_VARS[escaping]) {
   $display[escaping] = $HTTP_GET_VARS[escaping];
}
if($HTTP_GET_VARS[encoding]) {
   $display[encoding] = $HTTP_GET_VARS[encoding];
}

$check_php = $HTTP_GET_VARS[show_php] ? " checked" : "";
$check_simple = $HTTP_GET_VARS[show_simple] ? " checked" : "";
$check_xmlrpc = $HTTP_GET_VARS[show_xmlrpc] ? " checked" : "";


echo <<< END
<form method='get'>
<h2>Additional Output Options (displays below)</h2>
<b>output type</b><br>
<input type='checkbox' name='show_php' value='php'$check_php>php
<input type='checkbox' name='show_xmlrpc' value='xmlrpc'$check_xmlrpc>xmlrpc
<input type='checkbox' name='show_simple' value='simple'$check_simple>simplerpc
<br>
<br>
<i>These options do not apply to php output type.</i><br>

<b>output verbosity</b><br>
<input type='radio' name='verbosity' value='pretty' checked>pretty
<input type='radio' name='verbosity' value='newlines_only'>newlines only
<input type='radio' name='verbosity' value='no_white_space'>no white space
<br>

<b>output escaping</b><br>
<input type='checkbox' name='escaping[]' value='markup' checked>markup
<input type='checkbox' name='escaping[]' value='cdata'>cdata
<input type='checkbox' name='escaping[]' value='non-ascii'>non-ascii
<input type='checkbox' name='escaping[]' value='non-print'>non-print
<br>

<input type='submit' value="Get your fresh hot xmlrpc!">
</form>
END;

if($HTTP_GET_VARS[show_php]) {
   echo "<h2>php data representation, displayed with print_r()</h2>";
   echo "<xmp>\n";
   print_r($response);
   echo "</xmp>";
}

if($HTTP_GET_VARS[show_xmlrpc]) {
   $display[version] = "xmlrpc";
   $xml = xmlrpc_server_call_method($server, $request_xml, false, $display);

   if($xml) {
      echo "<h2>xmlrpc xml representation</h2>";
      echo "<xmp>\n$xml\n</xmp>";
   }
}

if($HTTP_GET_VARS[show_simple]) {
   $display[version] = "simple";
   $xml = xmlrpc_server_call_method($server, $request_xml, false, $display);

   if($xml) {
      echo "<h2>simplerpc xml representation</h2>";
      echo "<xmp>\n$xml\n</xmp>";
   }
}


?>
