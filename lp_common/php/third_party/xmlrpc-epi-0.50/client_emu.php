<html>
<head><title>xmlrpc</title></head>
<body>
<?php
include("utils/xmlrpc_emu.inc");

/**********************************************************
* This is the standard client example from the useful inc *
* xmlrpc php distribution. The only modification is the   *
* above include has been changed to use the xmlrpc-epi    *
* emulation classes.                                      *
**********************************************************/


$stateno = $HTTP_POST_VARS[stateno];
if ($stateno!="") {
  $f=new xmlrpcmsg('examples.getStateName',
				   array(new xmlrpcval($stateno, "int")));
  print "<pre>" . htmlentities($f->serialize()) . "</pre>\n";
  $c=new xmlrpc_client("/dev/xmlrpc_sourceforge/xmlrpc-epi-php/sample/server_emu.php", "localhost", 8000);
  $c->setDebug(1);
  $r=$c->send($f);
  if (!$r) { die("send failed"); }
  $v=$r->value();
  if (!$r->faultCode()) {
	print "State number ". $stateno . " is " .
	  $v->scalarval() . "<BR>";
	// print "<HR>I got this value back<BR><PRE>" .
	//  htmlentities($r->serialize()). "</PRE><HR>\n";
  } else {
	print "Fault: ";
	print "Code: " . $r->faultCode() . 
	  " Reason '" .$r->faultString()."'<BR>";
  }
}
print "<FORM METHOD=\"POST\">
<INPUT NAME=\"stateno\" VALUE=\"${stateno}\"><input type=\"submit\" value=\"go\" name=\"submit\"></FORM><P>
enter a state number to query its name";



?>
</body>
</html>
