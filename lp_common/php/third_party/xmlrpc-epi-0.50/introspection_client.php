<?php
include_once("utils/utils.php");
include_once("utils/server_lists.php");

function method_cmp($a, $b) {
   return strcmp($a[name], $b[name]);
}

function get_descriptions($server, $args=null) {
   $result = xu_rpc_http_concise(array(method => "system.describeMethods", 
													args => $args, 
													host => $server[host], 
													uri => $server[uri], 
													port => $server[port], 
													debug => $GLOBALS[debug],
													output => array(version => $GLOBALS[version])));

   if($result[methodList]) {
      usort($result[methodList], "method_cmp");
   }

   return $result;
}

function create_arg_from_desc($idx, $arg, $parent_type) {
   $name = $arg[name];
   $type = $arg[type];
   $member = $arg[member];
   $opt = $arg[optional];

   $result = null;

   switch($type) {
   case "array":
   case "struct":
      if($member) {
         $result = create_args_from_desc($idx, $member, $type);
      }
      break;
   default:
      $nameval = encode_idx($idx);
      $value = $GLOBALS[HTTP_GET_VARS][$nameval];

      if(isset($value) && $value != null) {
         switch($type) {
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

         $result = $value;
      }

      break;
   }
   return array($name, $result);
}

function create_args_from_desc($idx, $args, $type=null) {
   if($args) {
      if(!$type) {
         $type = "array";
         $params = true;
      }

      if($type === "array" && !$params && count($args) == 1) {
         for($i=0; $i < 5; $i++) {
            $arg = create_arg_from_desc(array($idx, ++$count), $args[0], $type);
            if(isset($arg[1]) && $arg[1] != null) {
               $result[] = $arg[1];
               if(gettype($arg[1]) === "array") {
                  break;
               }
            }
            else {
               break;
            }
         }
      }

      else {
         foreach($args as $arg) {
            $arg = create_arg_from_desc(array($idx, ++$count), $arg, $type);
            if(isset($arg[1])) {
               if($arg[0] && $type !== "array") {
                  $result[$arg[0]] = $arg[1];
               }
               else {
                  $result[] = $arg[1];
               }
            }
         }
      }
   }
   return $result;
}


function create_sig_args_from_desc($idx, $method) {
   extract($method);

   foreach($signatures as $sig) {
      $result = create_args_from_desc(array($idx, ++$count), $sig[params]);
      if($result) {
         return $result;
      }
   }
   return array();
}

// a method to display an html form for invoking the script
function create_params_from_desc($method_name, $description) {
   if($description) {
      foreach($description[methodList] as $method) {
         $count++;
         if($method[name] === $method_name) {
            $args = create_sig_args_from_desc($count, $method);
         }
      }
   }
   return $args;
}

function call_server($server_obj) {
   extract($server_obj);
   extract($GLOBALS[HTTP_GET_VARS]);
   
   if($method) {
      $arg = null;
      if($introspect) {
         $arg = array(array($method));
      }
      $result = get_descriptions($server_obj, $arg);

      if($result && !$introspect) {
         $params = create_params_from_desc($method, $result);
      }

      if(!$introspect) {
         $result = xu_rpc_http($method, $params, $host, $uri, $port, $GLOBALS[debug]);
      }

      if(isset($result)) {
         /* special case for documentation requests */
         if($method === "system.describeMethods" || $introspect) {
            echo xi_format($result);
         }
         else {
            echo "<h3>PHP representation</h3>";
            echo "<xmp>";
            $GLOBALS[ofunc] == 1 ? var_dump($result) : print_r($result);
            echo "</xmp>";
         }
      }
      else {
         echo "error: got no result!";
      }
   }
   else {
      echo "error: Missing parameter";
   }
}


function encode_idx($idx) {
   foreach($idx as $id) {
      if(is_array($id)) {
         $result .= encode_idx($id);
      }
      else {
         $result .= "$id-";
      }
   }
   return $result;
}

function print_arg($idx, $arg) {
   $name = $arg[name];
   $type = $arg[type];
   $desc = $arg[description];
   $member = $arg[member];
   $opt = $arg[optional];
   $def = $arg['default'];
   $type_def = $arg[type_def];

   echo "<i>$type</i> ";
   if($name) {
      echo "$name ";
   }
   if(!($type === "array" || $type === "struct")) {
      $nameval = encode_idx($idx);

      echo "<input type='text' size='15' name='$nameval' value='$def'>";
   }
   if($opt) {
      echo " (optional)";
   }
   if($desc) {
      echo "\n<small>( $desc )</small>";
   }
   if($member) {
      print_args($idx, $member, false);
   }
}

function print_args($idx, $args, $params=true) {
   if($args) {
      $vector_types = array("struct", "array", "mixed");
      echo "<dl>\n";

      /* display extra array entries */
      if(!$params && count($args) == 1 && !in_array($args[0][type], $vector_types)) {
         $args[1] = $args[0];
         $args[2] = $args[0];
         $args[3] = $args[0];
         $args[4] = $args[0];
      }

      foreach($args as $arg) {
         echo "<dt>\n";
         print_arg(array($idx, ++$count), $arg);
         echo "</dt>\n";
      }
      echo "</dl>\n";
   }
}

function print_sig($idx, $method_name, $sig, $server) {
   extract($sig);
   extract($GLOBALS);
   extract($server);

   echo "<form action='' method='get'>";

   $vars = server_vars();
   foreach($vars as $name => $value) {
      echo "<input type='hidden' name='$name' value='$value'>";
   }
   echo "<input type='hidden' name='method' value='$method_name'>";


   $server_uri = server_uri_vars();
   echo "<a href='?introspect=1&method=$method_name&$server_uri'>$method_name</a> (";
   print_args($idx, $params);
   echo ")<br>";

   echo "<br>output routine: <input type='radio' name='ofunc' value='0' checked> print_r &nbsp;&nbsp; <input type='radio' name='ofunc' value='1'> var_dump<br>";
   print_verbosity();

   echo "<br><input type='submit' value='call method'>";


   echo "</form>";

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

function print_method($idx, $method, $server) {
   extract($method);

   foreach($signatures as $sig) {
      print_sig(array($idx, ++$count), $name, $sig, $server);
      echo "<br>";
   }
}

// a method to display an html form for invoking the script
function print_html_form($server) {
   extract($GLOBALS);
   extract($server);

   echo "Introspecting $host...<hr>\n";
   flush();
   $result = get_descriptions($server);

   if($result) {
      extract($result);

      if($result[0][faultCode]) {
         $server_uri = server_uri_vars();
         echo "<p>system.describeMethods() is not supported by this server. " .
              "You may wish to try introspection via " .
              "<a href='introspection_client_ui.php?$server_uri'>another method</a>.";
      }

      else if(is_array($methodList)) {
         foreach($methodList as $method) {
            print_method(++$count, $method, $server);
            echo "<hr>";
         }
      }
      else {
         echo "<h3>no methods!</h3>";
      }
   }
   else {
      echo "<h3>introspection query failed</h3>";
   }
}

$server_list = get_introspection_servers();
$server = get_server_from_user($server_list);
if(!is_array($server)) {
   $spec_url = "http://xmlrpc-epi.sourceforge.net/specs/rfc.system.describeMethods.php";
   echo "<h2>XML-RPC Server to Introspect and Query via <a href='$spec_url'>system.describeMethods</a></h2>";

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
uses the <a href='$spec_url'>system.describeMethods()</a> protocol, which supports a great deal of information about each
method, including descriptions of nested parameters and return values.  If system.describeMethods()
is not available on the xml-rpc server, this client will ask if you'd like to try the 
<a href='introspection_client_ui.php'>other client</a> instead.
</p>

<p>
When you click 'submit' above:
</p>
<ol>
<li>your browser will submit your choices to this web server
<li>the webserver will make an introspection request to the xml-rpc server you've chosen
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
else if($method || $introspect) {
   call_server($server);
}
else {
   print_html_form($server);
}

?>
