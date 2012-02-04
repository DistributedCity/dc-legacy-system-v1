<?php

$pages = array(
   array(title => "Introspection and Debug Client - system.describeMethods",
         desc => "Queries remote xmlrpc servers and presents a form suitable for calling public methods. " .
                 "This tool uses the system.describeMethods() protocol",
         uri => "introspection_client.php",
         exec => true
         ),
   array(title => "Introspection and Debug Client - Useful, Inc",
         desc => "Queries remote xmlrpc servers and presents a form suitable for calling public methods. " .
                 "This tool uses the original useful,inc introspection protocol",
         uri => "introspection_client_ui.php",
         exec => true
         ),
   array(title => "List methods client",
         desc => "An older tool that simply queries the list of available methods",
         uri => "get-methods.php",
         exec => true
         ),
   array(title => "Local Server Method Introspection",
         desc => "Demonstration of proposed system.describeMethods() API, which provides " .
                 "very detailed information about each method.",
         uri => "introspection.php",
         exec => true
         ),
   array(title => "Test Server",
         desc => "A simple example of how to create a server. Basically 'Hello World'",
         uri => "server.php",
         exec => true
         ),
   array(title => "Validating Server GUI",
         desc => "An html test harness gui for the validating server code</a>",
         uri => "validate-form.php",
         exec => true,
         ),
   array(title => "Validating Server Code",
         desc => "A sligtly more complex server that implements each of the tests in the validation test suite. " .
                 "This is suitable for usage with the <a href='http://validator.xmlrpc.com'>online validation app</a>" .
                 " at <a href='http://www.xmlrpc.org'>xmlrpc.org</a>.  This is also a good example of how to " .
                 "use the various output options.",
         uri => "validate.php"
         ),
   array(title => "Interop Client",
         desc => "A tool for demonstrating interoperability with other xmlrpc implementations",
         uri => "interop-client.php",
         exec => true
         ),
   array(title => "Interop Server",
         desc => "Server component of interop tool",
         uri => "interop-server.php"
         ),
   array(title => "XMLRPC Utilities",
         desc => "A small set of utilities for things such as network requests, and loading the xmlrpc C extension",
         uri => "xmlrpc_utils.php"
         )
   );

   $file = $GLOBALS[HTTP_GET_VARS][view];
   if($file) {
      if(is_file($file)) {
         highlight_file($file);
      }
      else {
         echo "<h3>'$file' is not a file</h3>";
      }
   }
   else {
      echo "<body bgcolor='white'><center><h2>xmlrpc-epi-php examples</h2></center>" .
           "<table border='0' align='center' cellspacing='0' cellpadding='5'>";

      foreach($pages as $count => $page) {
         $color = ($count % 2 == 0) ? "#cccccc" : "#aaaaaa";
         $title = $page[title];
         $uri = $page[uri];
         $desc = $page[desc];
         $exec = $page[exec];
         $try = $exec ? "<a href='$uri'>Try it!</a>" : "&nbsp;";
         echo "<tr bgcolor='$color'><td><a href='index.php?view=$uri'>$uri</a></td><td>$title</td><td>$desc</td><td>$try</td></tr>";
      }

      echo "</table>";
   }


?>
