<?php


class debug_utils {

	function debug_utils() {

	}

	// A handy function to iterate over an array and create 
	// an html list/tree.  recurses if necessary. returns string.
	function get_tree($list, $delimChar="") {
		if (!(is_array($list) || is_object($list))){
			return "'$list' is not an array or object in print_tree<BR>";
		}
		
		$buf = "<UL>\n";
		foreach ($list as $key => $val) {
			$buf .= "<LI>$delimChar$key$delimChar => $delimChar$val$delimChar<br>\n";
			
			$type = gettype($val);
			if($type == "array" || $type == "object") {
				$buf .= debug_utils::get_tree($val, $delimChar);
			}
		}
		$buf .= "</UL>\n";
		return $buf;
	}

	// Prints out result of get_tree
	function print_tree($list, $delimChar="") {
		print debug_utils::get_tree($list, $delimChar);
	}

	// print_r displayable in browser.
	function print_rx($val) {
		echo "<xmp>\n";
		print_r($val);
		echo "\n</xmp>";
	}

	// var_dump displayable in browser.
	function var_dumpx($val) {
		echo "<xmp>\n";
		var_dump($val);
		echo "\n</xmp>";
	}

	function log_error($msg, $file=null, $line=null, $module=null) {

		$fileinfo = $file && $line ? " at $file:$line" : "";
		$module = $module ? " in module '$module'" : "";

		// write to stderr (Apache error_log)
		error_log("php error$module$fileinfo: $msg", 0); 
	}

}

?>
