<?php


/**
* Executes a set of test cases and displays results.
* 
* This class enables simple creation of a test harness.
* Simply instantiate the class, then call runTests.
*
* Your test case file should define an init function which
* specifies which functions to call as test cases.  If your
* filename is foo.php, then the test function should be
* foo_init().
*
* Your init function should return an array of test descriptions.
* A test description is an associative array that contains the
* following keys:
*  method  - name of the test function
*  desc    - human title/desc of the test to be performed
*
* This class will call each of your test functions, in order.
* It will pass a single argument: $userData, which is data
* that you give to the class when constructing it.  This data
* can be used for specifying config parameters to the test case,
* eg database connection info.
*
* Thus, your test function signature should look like:
* array my_test_func($userData);
*
* Your test function(s) should return an associate array with
* the following keys:
*  - success    - true or false
*  - error_msg	 - an error message in case the test failed.
*
* @access   public
* @version	$Id: class_test_harness.php,v 1.1.1.1 2002/06/19 00:15:27 gente_libre Exp $
* @author   andre anders
*
* @log
* $Log: class_test_harness.php,v $
* Revision 1.1.1.1  2002/06/19 00:15:27  gente_libre
* initial DC import to SourceForge CVS
*
* Revision 1.3  2002/01/12 01:55:13  andre
* commit before moving repository to lp1, again
*
* Revision 1.2  2002/01/02 02:05:13  andre
* adding a call to cleanup callback
*
* Revision 1.1.1.1  2001/12/22 09:12:41  andre
* initial import
*
* Revision 1.3  2001/12/11 04:17:46  andre
* right align pass/fail column
*
* Revision 1.2  2001/12/04 10:35:57  andre
* ignore .php~ backup files and other such crap
*
* Revision 1.1  2001/12/02 05:59:51  andre
* adding test harness
*
*
*/
class testHarness {
	/**
	* user (eg config) data to be passed to test functions
	*
	* @var	mixed
	*/
	var $userData;

	/**
	* keeps track of test result totals
	*
	* @var	array
	*/
	var $totals;

	/**
	* whether in debug mode or not.
	*
	*/
	var $debug;


	/**
	* constructor
	*
	* @access public
	* @param	 userData	   data to be passed to test methods
	* @param  debug       whether to print verbose data or not
	*/
	function testHarness($userData = null, $debug=false) {
		$this->userData = $userData;
		$this->debug = $debug;
	}

	/**
	* runs all tests in a given directory
	*
	* @access public
	* @param	 directory	directory where tests are located
	* @param  mask        regex file mask
	*/
	function runTests($directory='.', $mask = "", $ext="php") {
		$this->totals = array();

		$list = $this->get_dir_contents($directory, $mask, $ext);
		$test_files = $list[file];

		echo "Running tests in $directory";
		if($mask) {
			echo " matching $mask";
		}
		echo "<br><br>";

		if($test_files) {
			foreach($test_files as $file) {
				$this->runTestFile($file);
			}
			$this->printTotals();
		}
		else {
			echo "No test files found";
		}
	}

	/**
	* runs tests in a particular test file.
	*
	* @access public
	*
	* @param	 filename    filename containing tests
	*/
	function runTestFile($filename) {
		include_once($filename);

      $funcbase = basename($filename);
		$funcbase = substr($funcbase, 0, strrpos($funcbase, '.'));
        $funcbase = str_replace('.', '_', $funcbase);

		$initfunc = $funcbase . "_init";
        $cleanfunc = $funcbase . "_cleanup";
		if(function_exists($initfunc)) {
			$tests = $initfunc($this->userData);

			echo "<table width='100%'>";
			echo "<tr bgcolor='black'><td colspan='2'><font color='#cccccc'>Running tests in file $filename</font></td></tr>";
			foreach($tests as $test) {
				$method = $test[method];
				$desc = $test[desc];
				$this->runTest($method, $desc, $this->debug);
			}
            if(function_exists($cleanfunc)) {
                $cleanfunc($this->userData);
            }
			echo "</table>";
		}
		else {
			echo "<h5>No init func '$initfunc' in $filename. skipping.</h5>";
		}
        flush();
	}

	/**
	* runs an individual test case
	*
	* @access private
	*
	* @param	method      method name
	* @param desc        title/desc of test case
	* @param debug       whether to print debug info or not
	*/
	function runTest($method, $desc, $debug=false) {
      $result = $method($this->userData);

		$success = $result[success] ? "Passed" : "Failed";
		$color = $result[success] ? "green" : "red";
      $bgcolor = $result[success] ? "" : "bgcolor='yellow'";

		echo "<tr $bgcolor><td>$desc</td>";
		echo "<td align='right'>[ <font color='$color'><b>$success</b></font> ]</td>";

	   if($debug && $result[error_msg]) {
			$error_msg = $result[error_msg];
			echo "<tr $bgcolor><td colspan='2'>$error_msg</td></tr>\n";
		}

		$this->totals[$success] ++;
	}

	/**
	* prints totals after tests are run.
	*
	* @access private
	*/
	function printTotals() {
		echo "<hr><h5>Totals</h5>";
		foreach($this->totals as $key => $total) {
			echo "$key: $total<br>";
		}
	}

	/**
	* retrieves a directory listing
	*
	* @access public
	*
	* @param	directory	directory to list
	* @param mask        regex - files to match
	* 
	* @return array[type]<filename> 
	*  where type = fifo, char, dir, block, link, file, and unknown
	*/
	function get_dir_contents($directory = '.', $mask, $ext) {
		$handle=opendir($directory);
                       
		$list = array();
		if($handle) {
			while ($filename = readdir($handle)) {
				if( (!$ext || substr($filename, strrpos($filename, '.')+1) === "$ext") &&
                (!$mask || ereg($mask, $filename)) ) {
					$dir = getcwd();
					chdir($directory);
					$list[filetype($filename)][] = "$directory/$filename";
					chdir($dir);
				}
			}
			closedir($handle);
		}
		return $list;
	}
};


?>
