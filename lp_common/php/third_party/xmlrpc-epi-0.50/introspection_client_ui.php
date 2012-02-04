<?php
include_once("utils/utils.php");
include_once("utils/server_lists.php");

function create_params_from_url($values, $types) {
   $params = array();
   foreach($values as $key => $value) {
      $type = $types[$key];

      switch($type) {
      case "array":
         $value = explode(",", $value);
         break;
      case "struct":
         echo "<h3>sorry, structs are not currently supported by this client</h3>";
         return false;
      case "int":
      case "i4":
         $value = (int)$value;
         break;
      case "boolean":
         $value = (bool)$value;
         break;
      case "double":
         $value = (double)$value;
         break;
      case "string":
         $value = (string)$value;
         break;
      case "datetime":
         xmlrpc_set_type(&$value, "datetime");
         break;
      case "base64":
         xmlrpc_set_type(&$value, "base64");
         break;
      }
      $params[] = $value;
   }
   return $params;
}

function call_server($server_obj) {
   extract($GLOBALS[HTTP_POST_VARS]);
   extract($GLOBALS[HTTP_GET_VARS]);
   extract($server_obj);
   
   if($method) {
      $arg = null;

      if(is_array($type) && is_array($value)) {
         $params = create_params_from_url($value, $type);
      }

		$result = xu_rpc_http_concise(array(method => $method, 
														args => $params, 
														host => $host, 
														uri => $uri, 
														port => $port, 
														debug => $GLOBALS[debug],
														output => array(version => $GLOBALS[version])));

      if(isset($result)) {
         echo "<h3>PHP representation</h3>";
         echo "<xmp>";
         $GLOBALS[ofunc] == 1 ? var_dump($result) : print_r($result);
         echo "</xmp>";
      }
      else {
         echo "error: got no result!";
      }
   }
   else {
      echo "error: Missing parameter";
   }
}

function print_args($method_name, $sig) {
   foreach($sig as $key => $type) {
      // first one is return value.
      if($key > 0) {
         $comma = $sig[$key+1] ? ", " : "";

         if($type === "array") {
            $note = "&nbsp;&nbsp; (separate values with a comma)";
         }

         echo "<br> <i>$type</i>\n";
         echo " <input type='hidden' name='type[]' value='$type'>\n";
         echo "<input type='text' name='value[]'>$comma$note<br>\n";
      }
   }
}

function print_verbosity() {
   $checked1 = $GLOBALS[debug] == 0 ? "checked" : "";
   $checked2 = $GLOBALS[debug] == 1 ? "checked" : "";
   $checked3 = $GLOBALS[debug] == 2 ? "checked" : "";

   echo "Verbosity:";
   echo "<input type='radio' name='debug' value='0' $checked1>none &nbsp;&nbsp; ";
   echo "<input type='radio' name='debug' value='1' $checked2>some &nbsp;&nbsp; ";
   echo "<input type='radio' name='debug' value='2' $checked3>more &nbsp;&nbsp; ";
}


function print_sig($method_name, $sig) {
   extract($GLOBALS);

   echo "<form action='' method='get'>";

   $vars = server_vars();
   foreach($vars as $name => $value) {
      echo "<input type='hidden' name='$name' value='$value'>";
   }
   echo "<input type='hidden' name='method' value='$method_name'>";

   echo "$method_name (";
   print_args($method_name, $sig);
   echo ")";

   echo "<br>output routine: <input type='radio' name='ofunc' value='0' checked> print_r &nbsp;&nbsp; <input type='radio' name='ofunc' value='1'> var_dump<br>";
   print_verbosity();

   echo "<br><input type='submit' value='call method'>";


   echo "</form>";

}

function print_method($method_name, $signature) {

   if(is_array($signature)) {
      foreach($signature as $sig) {
         print_sig($method_name, $sig);
         echo "<br>";
      }
   }
}

// a method to display an html form for invoking the script
function print_html_form($server) {
   extract($server);

   echo "Introspecting $host...<hr>\n";
   flush();

   $result = xu_rpc_http_concise(array(method => "system.listMethods", 
													args => null, 
													host => $host,
													uri => $uri, 
													port => $port, 
													debug => $GLOBALS[debug],
													output => array(version => $GLOBALS[version])));

   if($result) {
      foreach($result as $method) {
			$sig_result = xu_rpc_http_concise(array(method => "system.methodSignature", 
   															args => array($method), 
   															host => $host,
   															uri => $uri, 
   															port => $port, 
   															debug => $GLOBALS[debug],
   															output => array(version => $GLOBALS[version])));

         if($sig_result) {
            print_method($method, $sig_result);
            echo "<hr>";
         }
         else {
            echo "<h3>system.methodSignature($method) query failed</h3>";
            break;
         }
         flush();
      }
   }
   else {
      echo "<h3>system.listMethods query failed</h3>";
   }
}



$server_list = get_intro_useful_servers();
$server = get_server_from_user($server_list);
if(!is_array($server)) {
   $spec_url = "http://xmlrpc.usefulinc.com/doc/reserved.html";
   echo "<h2>XML-RPC Server to Introspect and Query via Useful, Inc. <a href='$spec_url'>spec</a></h2>";

   print_servers_form($server_list);

   echo <<< END
<hr>
<h3>What is this?</h3>
<p>
This page is an xml-rpc introspection client. Some xml-rpc servers are now capable of describing
themselves. That information is used to auto-generate a form that represents the server's API. The form
values can then be filled in and the query executed live against the server.
</p>

<p>
There are a couple of different introspection protocols, and thus a couple of clients. This client
uses the <a href='http://www.usefulinc.com'>Useful, Inc.</a> 
<a href='$spec_url'>introspection protocol</a>, which requires one call to system.listMethods() to
retrieve all the methods, and then one call to system.methodSignature() for each one. It also
returns only the top level parameters and return values, which means that the generated form cannot
be accurate if the parameters are nested. If you require nested functionality, or parameter help,
you might wish to consider using the <a href='introspection_client.php'>other client</a> instead.
</p>

<p>
When you click 'submit' above:
</p>
<ol>
<li>your browser will submit your choices to this web server
<li>the webserver will make an introspection request to the xml-rpc server you've chosen to list the methods
<li>for each returned method, the webserver will again call the xml-rpc server to obtain the method signature
<li>the webserver will format the results into a set of html forms and return it to your browser
<li>you choose an API method, fill in the params (if any) and submit the form
<li>the webserver matches your input with the method signature and creates an xml-rpc request
<li>the webserver sends the request to the xml-rpc server and receives a response
<li>the webserver formats the response in html and sends it to your browser.
</ol>

<p>
Optionally, you may view the xml-rpc requests and responses by adjusting the "verbosity" level.
</p>

END;


}


// some code which determines if we are in form display or response mode.
else if($method) {
   call_server($server);
}
else {
   print_html_form($server);
}





?>
