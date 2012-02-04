<?php
//
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2001 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stig Bakken <ssb@fast.no>                                   |
// |                                                                      |
// +----------------------------------------------------------------------+
//
// Database independent query interface definition for PHP's MySQL
// extension.
//

//
// XXX legend:
//
// XXX ERRORMSG: The error message from the mysql function should
//				 be registered here.
//

require_once "DB/common.php";

class DB_mysql extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();

    // }}}
    // {{{ constructor

    /**
     * DB_mysql constructor.
     *
     * @access public
     */

    function DB_mysql()
    {
        $this->DB_common();
        $this->phptype = "mysql";
        $this->dbsyntax = "mysql";
        $this->features = array(
            "prepare" => false,
            "pconnect" => true,
            "transactions" => false
        );
        $this->errorcode_map = array(
            1004 => DB_ERROR_CANNOT_CREATE,
            1005 => DB_ERROR_CANNOT_CREATE,
            1006 => DB_ERROR_CANNOT_CREATE,
            1007 => DB_ERROR_ALREADY_EXISTS,
            1008 => DB_ERROR_CANNOT_DROP,
            1046 => DB_ERROR_NODBSELECTED,
            1050 => DB_ERROR_ALREADY_EXISTS,
            1051 => DB_ERROR_NOSUCHTABLE,
            1054 => DB_ERROR_NOSUCHFIELD,
            1062 => DB_ERROR_ALREADY_EXISTS,
            1064 => DB_ERROR_SYNTAX,
            1100 => DB_ERROR_NOT_LOCKED,
            1136 => DB_ERROR_VALUE_COUNT_ON_ROW,
            1146 => DB_ERROR_NOSUCHTABLE,
        );
    }

    // }}}

    // {{{ connect()

    /**
     * Connect to a database and log in as the specified user.
     *
     * @param $dsn the data source name (see DB::parseDSN for syntax)
     * @param $persistent (optional) whether the connection should
     *        be persistent
     * @access public
     * @return int DB_OK on success, a DB error on failure
     */
    
    function connect($dsn, $persistent = false)
    {
        if (is_array($dsn)) {
            $dsninfo = &$dsn;
        } else {
            $dsninfo = DB::parseDSN($dsn);
        }
        
        if (!$dsninfo || !$dsninfo["phptype"]) {
            return $this->raiseError(); // XXX ERRORMSG
        }
	
        $this->dsn = $dsninfo;

        $dbhost = $dsninfo["hostspec"] ? $dsninfo["hostspec"] : "localhost";
        $user = $dsninfo["username"];
        $pw = $dsninfo["password"];
        
        DB::assertExtension("mysql");
        $connect_function = $persistent ? "mysql_pconnect" : "mysql_connect";
        
        if ($dbhost && $user && $pw) {
            $conn = @$connect_function($dbhost, $user, $pw);
        } elseif ($dbhost && $user) {
            $conn = @$connect_function($dbhost, $user);
        } elseif ($dbhost) {
            $conn = @$connect_function($dbhost);
        } else {
            $conn = false;
        }
        
        if ($conn == false) {
            return $this->raiseError(); // XXX ERRORMSG
        }
        
        if ($dsninfo["database"]) {
            if (!mysql_select_db($dsninfo["database"], $conn)) {
                return $this->raiseError(); // XXX ERRORMSG
            }
        }
        
        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @access public
     *
     * @return bool TRUE on success, FALSE if not connected.
     */
    function disconnect()
    {
        return mysql_close($this->connection);
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to MySQL and return the results as a MySQL resource
     * identifier.
     *
     * @param the SQL query
     *
     * @access public
     *
     * @return mixed returns a valid MySQL result for successful SELECT
     * queries, DB_OK for other successful queries.  A DB error is
     * returned on failure.
     */
    function simpleQuery($query)
    {
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = mysql_query($query, $this->connection);
        if (!$result) {
            return $this->mysqlRaiseError();
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        return DB::isManip($query) ? DB_OK : $result;
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch a row and return as array.
     *
     * @param $result MySQL result identifier
     * @param $fetchmode how the resulting array should be indexed
     *
     * @access public
     *
     * @return mixed an array on success, a DB error on failure, NULL
     *               if there is no more data
     */
    function &fetchRow($result, $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }

        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
        } else {
            $row = mysql_fetch_row($result);
        }
	
        if (!$row) {
            $errno = mysql_errno($this->connection);

            if (!$errno) {
                return null;
            }
	    
            return $this->mysqlRaiseError($errno);
        }
	
        return $row;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param $result MySQL result identifier
     * @param $arr (reference) array where data from the row is stored
     * @param $fetchmode how the array data should be indexed
     *
     * @access public
     *
     * @return int DB_OK on success, a DB error on failure
     */
    function fetchInto($result, &$arr, $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }

        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = mysql_fetch_array($result, MYSQL_ASSOC);
        } else {
            $arr = mysql_fetch_row($result);
        }

        if (!$arr) {
            $errno = mysql_errno($this->connection);

            if (!$errno) {
                return NULL;
            }

            return $this->mysqlRaiseError($errno);
        }

        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result MySQL result identifier or DB statement identifier
     *
     * @access public
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult($result)
    {
        if (is_resource($result)) {
            return mysql_free_result($result);
        }

        if (!isset($this->prepare_tokens[$result])) {
            return false;
        }

        unset($this->prepare_tokens[$result]);
        unset($this->prepare_types[$result]);

        return true; 
    }

    // }}}
    // {{{ numCols()

    /**
     * Get the number of columns in a result set.
     *
     * @param $result MySQL result identifier
     *
     * @access public
     *
     * @return int the number of columns per row in $result
     */
    function numCols($result)
    {
        $cols = mysql_num_fields($result);

        if (!$cols) {
            return $this->mysqlRaiseError();
        }

        return $cols;
    }

    // }}}
    // {{{ numRows()

    /**
     * Get the number of rows in a result set.
     *
     * @param $result MySQL result identifier
     *
     * @access public
     *
     * @return int the number of rows in $result
     */
    function numRows($result)
    {
        $rows = @mysql_num_rows($result);
        if ($rows === null) {
            return $this->mysqlRaiseError();
        }

        return $rows;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Gets the number of rows affected by the data manipulation
     * query.  For other queries, this function returns 0.
     *
     * @return number of rows affected by the last query
     */

    function affectedRows()
    {
        if (DB::isManip($this->last_query)) {
            $result = mysql_affected_rows($this->connection);
        } else {
            $result = 0;
        }
        return $result;
     }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @access public
     *
     * @return int native MySQL error code
     */

    function errorNative()
    {
        return mysql_errno($this->connection);
    }

    // }}}
    // {{{ nextId()

    /**
     * Get the next value in a sequence.  We emulate sequences
     * for MySQL.  Will create the sequence if it does not exist.
     *
     * @access public
     *
     * @param $seq_name the name of the sequence
     * 
     * @param $ondemand whether to create the sequence table on demand
     * (default is true)
     *
     * @return a sequence integer, or a DB error
     */
    function nextId($seq_name, $ondemand = true)
    {
        $sqn = preg_replace('/[^a-z0-9_]/i', '_', $seq_name);
        $repeat = 0;
        do {
            $result = $this->query("INSERT INTO ${sqn}_seq VALUES(NULL)");
            if ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $result;
                }
            } else {
                $repeat = 0;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $result;
        }
        return mysql_insert_id($this->connection);
    }
		
    // }}}
    // {{{ createSequence()

    function createSequence($seq_name)
    {
        $sqn = preg_replace('/[^a-z0-9_]/i', '_', $seq_name);
        return $this->query("CREATE TABLE ${sqn}_seq ".
                            "(id INTEGER UNSIGNED AUTO_INCREMENT NOT NULL,".
                            " PRIMARY KEY(id))");
    }

    // }}}
    // {{{ dropSequence()

    function dropSequence($seq_name)
    {
        $sqn = preg_replace('/[^a-z0-9_]/i', '_', $seq_name);
        return $this->query("DROP TABLE ${sqn}_seq");
    }

    // }}}
    // {{{ modifyQuery()

    function modifyQuery($query)
    {
        if ($this->options['optimize'] == 'portability') {
            // "DELETE FROM table" gives 0 affected rows in MySQL.
            // This little hack lets you know how many rows were deleted.
            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $query)) {
                $query = preg_replace('/^\s*DELETE\s+FROM\s+(\S+)\s*$/',
                                      'DELETE FROM \1 WHERE 1=1', $query);
            }
        }
        return $query;
    }

    // }}}
    // {{{ mysqlRaiseError()

    function mysqlRaiseError($errno = null)
    {
        if ($errno === null) {
            return $this->raiseError($this->errorCode(mysql_errno($this->connection)));
        }
        return $this->raiseError($this->errorCode($errno));
    }

    // }}}

    // TODO/wishlist:
    // simpleFetch
    // simpleGet
    // longReadlen
    // binmode
}

?>
