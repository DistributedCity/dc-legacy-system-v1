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
// | Authors: Rui Hirokawa <louis@cityfujisawa.ne.jp>                     |
// |          Stig Bakken <ssb@fast.no>                                   |
// +----------------------------------------------------------------------+
//
// Database independent query interface definition for PHP's PostgreSQL
// extension.
//

//
// XXX legend:
//
// XXX ERRORMSG: The error message from the pgsql function should
//               be registered here.
//

require_once 'DB/common.php';

class DB_pgsql extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();
    var $transaction_opcount = 0;
    var $numrows;
    var $row;
    var $affected;
    var $autocommit = true;
    var $dsn;

    // }}}
    // {{{ constructor

    function DB_pgsql()
    {
        $this->DB_common();
        $this->phptype = 'pgsql';
        $this->dbsyntax = 'pgsql';
        $this->features = array(
            'prepare' => false,
            'pconnect' => true,
            'transactions' => true
        );
        $this->errorcode_map = array(
        );
        $this->numrows = array();
        $this->row = array();
        $this->affected = 0;
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to a database and log in as the specified user.
     *
     * @param $dsn the data source name (see DB::parseDSN for syntax)
     * @param $persistent (optional) whether the connection should
     *        be persistent
     *
     * @return int DB_OK on success, a DB error code on failure
     */
    function connect($dsn, $persistent = false)
    {
        if (is_array($dsn)) {
            $dsninfo = &$dsn;
        } else {
            $dsninfo = DB::parseDSN($dsn);
        }
        if (!$dsninfo || !$dsninfo['phptype']) {
            return $this->raiseError(); // XXX ERRORMSG
        }
        $this->dsn = $dsninfo;
        $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'unix';
        if ($dbhost == 'unix') {
            $protocol = 'unix';
        } else {
            $protocol = $dsninfo['protocol'] ? $dsninfo['protocol'] : 'tcp';
        }
        $user = $dsninfo['username'];
        $pw = $dsninfo['password'];
        $dbname = $dsninfo['database'];
        $options = (!empty($dsninfo['options'])) ? $dsninfo['options'] : NULL;
        $tty = (!empty($dsninfo['tty'])) ? $dsninfo['tty'] : NULL;
        $port = (!empty($dsninfo['port'])) ? $dsninfo['port'] : '5432';

        $connect_function = $persistent ? 'pg_pconnect' : 'pg_connect';

        if (($protocol == 'unix') && $dbname) {
            $connect_params = "dbname=$dbname";
            if ($user) {
                $connect_params .= " user=$user";
            }
            if ($pw) {
                $connect_params .= " password=$pw";
            }
            $conn = @$connect_function($connect_params);
        } elseif ($dbhost && $user && $pw && $dbname) {
            $conn = @$connect_function(
                "host=$dbhost port=$port dbname=$dbname user=$user password=$pw");
        } elseif ($dbhost && $dbname && $options && $tty) {
            $conn = @$connect_function($dbhost, $port, $options, $tty, $dbname);
        } elseif ($dbhost && $dbname) {
            $conn = @$connect_function($dbhost, $port, $dbname);
        } else {
            $conn = false;
        }
        if ($conn == false) {
            return $this->raiseError(); // XXX ERRORMSG
        }
        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @return bool TRUE on success, FALSE if not connected.
     */
    function disconnect()
    {
        return @pg_close($this->connection); // XXX ERRORMSG
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to PostgreSQL and return the results as a
     * PostgreSQL resource identifier.
     *
     * @param $query the SQL query
     *
     * @return int returns a valid PostgreSQL result for successful SELECT
     * queries, DB_OK for other successful queries.  A DB error code
     * is returned on failure.
     */
    function simpleQuery($query)
    {
        $ismanip = DB::isManip($query);
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        if (!$this->autocommit && $ismanip) {
            if ($this->transaction_opcount == 0) {
                $result = @pg_exec($this->connection, "begin;");
                if (!$result) {
                    return $this->pgsqlRaiseError();
                }
            }
            $this->transaction_opcount++;
        }
        $result = @pg_exec($this->connection, $query);
        if (!$result) {
            return $this->pgsqlRaiseError();
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        if ($ismanip) {
            $this->affected = @pg_cmdtuples($result);
            return DB_OK;
        } elseif (preg_match('/^\s*(SELECT)\s/i', $query) &&
                  !preg_match('/^\s*(SELECT\s+INTO)\s/i', $query)) {
            /* PostgreSQL commands:
               ABORT, ALTER, BEGIN, CLOSE, CLUSTER, COMMIT, COPY,
               CREATE, DECLARE, DELETE, DROP TABLE, EXPLAIN, FETCH,
               GRANT, INSERT, LISTEN, LOAD, LOCK, MOVE, NOTIFY, RESET,
               REVOKE, ROLLBACK, SELECT, SELECT INTO, SET, SHOW,
               UNLISTEN, UPDATE, VACUUM
            */
            $this->row[$result] = 0; // reset the row counter.
            $this->numrows[$result] = @pg_numrows($result);  
            $this->affected = 0;
            return $result;
        } else {
            $this->affected = 0;
            return DB_OK;
        }
    }

    // }}}
    // {{{ errorCode()

    /**
     * Map native error codes to DB's portable ones.  Requires that
     * the DB implementation's constructor fills in the $errorcode_map
     * property.
     *
     * @param $nativecode the native error code, as returned by the backend
     * database extension (string or integer)
     *
     * @return int a portable DB error code, or FALSE if this DB
     * implementation has no mapping for the given error code.
     */

    function errorCode($errormsg)
    {
        static $error_regexps;
        if (empty($error_regexps)) {
            $error_regexps = array(
                '/(Table does not exist\.|Relation \'.*\' does not exist|sequence does not exist)$/' => DB_ERROR_NOSUCHTABLE,
                '/Relation \'.*\' already exists/' => DB_ERROR_ALREADY_EXISTS,
                '/divide by zero$/' => DB_ERROR_DIVZERO,
                '/pg_atoi: error in .*: can\'t parse /' => DB_ERROR_INVALID_NUMBER,
                '/attribute \'.*\' not found$/' => DB_ERROR_NOSUCHFIELD,
                '/parser: parse error at or near \"/' => DB_ERROR_SYNTAX
            );
        }
        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, $errormsg)) {
                return $code;
            }
        }
        //php_error(E_WARNING, get_class($this)."::errorCode: no mapping for $nativecode");
        // Fall back to DB_ERROR if there was no mapping.
        return DB_ERROR;
        //return $errormsg;
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch a row and return as array.
     *
     * @param $result PostgreSQL result identifier
     * @param $fetchmode how the resulting array should be indexed
     *
     * @return int an array on success, a DB error code on failure, NULL
     *             if there is no more data
     */
    function &fetchRow($result, $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        if ($this->row[$result] >= $this->numrows[$result]) {
            return NULL;
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $row = @pg_fetch_array($result, $this->row[$result], PGSQL_ASSOC);
        } else {
            $row = @pg_fetch_row($result, $this->row[$result]);
        }
        if (!$row) {
            $err = $this->pgsqlRaiseError();
            if (!$err) {
                return NULL;
            }
            return $err;
        }
        $this->row[$result]++;
        return $row;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param $result PostgreSQL result identifier
     * @param $arr (reference) array where data from the row is stored
     * @param $fetchmode how the array data should be indexed
     *
     * @return int DB_OK on success, a DB error code on failure
     */
    function fetchInto($result, &$arr, $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        if ($this->row[$result]>=$this->numrows[$result]){
            return NULL;
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = @pg_fetch_array($result, $this->row[$result], PGSQL_ASSOC);
        } else {
            $arr = @pg_fetch_row($result, $this->row[$result]);
        }
        if (!$arr) {
            /* 
             $errno = pg_errormessage($this->connection);
             if (!$errno) {
                return NULL;
             }
             return $errno;
            */
            // the error codes are not supported in pgsql. 
            return $this->raiseError(DB_ERROR_NOT_CAPABLE); // XXX ERRORMSG
        }
        $this->row[$result]++;
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result PostgreSQL result identifier or DB statement identifier
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult($result)
    {
        if (is_resource($result)) {
            return @pg_freeresult($result);
        }
        if (!isset($this->prepare_tokens[$result])) {
            return false;
        }
        unset($this->prepare_tokens[$result]);
        unset($this->prepare_types[$result]);
        unset($this->row[$result]);
        unset($this->numrows[$result]);
        $this->affected = 0;
        return true; 
    }

    // }}}
    // {{{ numCols()

    /**
     * Get the number of columns in a result set.
     *
     * @param $result PostgreSQL result identifier
     *
     * @return int the number of columns per row in $result
     */
    function numCols($result)
    {
        $cols = @pg_numfields($result);
        if (!$cols) {
            return $this->pgsqlRaiseError();
        }
        return $cols;
    }

    // }}}
    // {{{ numRows()

    /**
     * Get the number of rows in a result set.
     *
     * @param $result PostgreSQL result identifier
     *
     * @return int the number of rows in $result
     */
    function numRows($result)
    {
        $rows = @pg_numrows($result);
        if ($rows === null) {
            return $this->pgsqlRaiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @return int native PostgreSQL error code
     */
    function errorNative()
    {
        return pg_errormessage($this->connection);
    }

    // }}}
    // {{{ prepare()

    /**
     * Prepares a query for multiple execution with execute().  With
     * PostgreSQL, this is emulated.
     */
    function prepare($query)
    {
        $tokens = split('[\&\?]', $query);
        $token = 0;
        $types = array();
        for ($i = 0; $i < strlen($query); $i++) {
            switch ($query[$i]) {
                case '?':
                    $types[$token++] = DB_PARAM_SCALAR;
                    break;
                case '&':
                    $types[$token++] = DB_PARAM_OPAQUE;
                    break;
            }
        }
        $this->prepare_tokens[] = &$tokens;
        end($this->prepare_tokens);
        $k = key($this->prepare_tokens);
        $this->prepare_types[$k] = $types;
        return $k;
    }

    // }}}
    // {{{ execute()

    /**
     * @return mixed returns a DB result object for successful SELECT
     *         queries, DB_OK for other successful queries.  A DB
     *         error is returned on failure.
     */
    function execute($stmt, $data = false)
    {
        $realquery = $this->executeEmulateQuery($stmt, $data);
        $result = $this->simpleQuery($realquery);
        if (DB::isError($result) || $result === DB_OK) {
            return $result;
        } else {
            return new DB_result($this, $result);
        }
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Enable/disable automatic commits
     */
    function autoCommit($onoff = false)
    {
        // XXX if $this->transaction_opcount > 0, we should probably
        // issue a warning here.
        $this->autocommit = $onoff ? true : false;
        return DB_OK;
    }

    // }}}
    // {{{ commit()

    /**
     * Commit the current transaction.
     */
    function commit()
    {
        if ($this->transaction_opcount > 0) {
            // (disabled) hack to shut up error messages from libpq.a
            //@fclose(@fopen("php://stderr", "w"));
            $result = @pg_exec($this->connection, "end;");
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->pgsqlRaiseError();
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ rollback()

    /**
     * Roll back (undo) the current transaction.
     */
    function rollback()
    {
        if ($this->transaction_opcount > 0) {
            $result = @pg_exec($this->connection, "abort;");
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->pgsqlRaiseError();
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Gets the number of rows affected by the last query.
     * if the last query was a select, returns 0.
     *
     * @return number of rows affected by the last query or DB_ERROR
     */
    function affectedRows()
    {
        $result = $this->affected;
        if ($result === false) {
            return $this->raiseError();
        }
        return $result;
     }
     // }}}
    // {{{ nextId()

    /**
     * Get the next value in a sequence.  
     *
     * We are using native PostgreSQL sequences. If a sequence does
     * not exist, it will be created, unless $ondemand is false.
     *
     * @access public
     * @param string $seq_name the name of the sequence
     * @param bool $ondemand whether to create the sequence on demand
     * @return a sequence integer, or a DB error
     */
    function nextId($seq_name, $ondemand = true)
    {
        $sqn = preg_replace('/[^a-z0-9_]/i', '_', $seq_name);
        $repeat = 0;
        do {
            $result = $this->query("SELECT NEXTVAL('${sqn}_seq')");
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
        $arr = $result->fetchRow(DB_FETCHMODE_ORDERED);
        $result->free();
        return $arr[0];
    }

    // }}}
    // {{{ createSequence()

    /**
     * Create the sequence
     *
     * @param string $seq_name the name of the sequence
     * @return DB error
     * @access public
     */
    function createSequence($seq_name)
    {
        $sqn = preg_replace('/[^a-z0-9_]/i', '_', $seq_name);
        return $this->query("CREATE SEQUENCE ${sqn}_seq");
    }

    // }}}
    // {{{ dropSequence()

    /**
     * Drop a sequence
     *
     * @param string $seq_name the name of the sequence
     * @return DB error
     * @access public
     */
    function dropSequence($seq_name)
    {
        $sqn = preg_replace('/[^a-z0-9_]/i', '_', $seq_name);
        return $this->query("DROP SEQUENCE ${sqn}_seq");
    }

    // }}}
    // {{{ pgsqlRaiseError()

    function pgsqlRaiseError($errno = null)
    {
        if ($errno === null) {
            return $this->raiseError($this->errorCode(pg_errormessage($this->connection)));
        }
        return $this->raiseError($this->errorCode($errno));
    }

    // }}}
}

// Local variables:
// tab-width: 4
// c-basic-offset: 4
// End:
?>
