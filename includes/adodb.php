<?php

/*
 ************************************************************************
 This file is part of TSM Monitor.

 TSM Monitor is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 TSM Monitor is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with TSM Monitor.  If not, see <http://www.gnu.org/licenses/>.

 ************************************************************************
 */

/**
 *
 * adodb.php, TSM Monitor
 * 
 * DB Stuff for TSM Monitor
 * 
 * @author Frank Fegert, Michael Clemens
 * @package tsmmonitor
 */


/**
 *
 * Class ADOdb
 *
 */

class ADOdb {


    var $conn;
    var $debug;
    var $logfile;

    /**
     * constructor - establishes a DB connection via ADODB
     *
     * @param string $host the hostname of the DB server
     * @param string $port the portnumber for the DB connection
     * @param string $user the username for the DB connection
     * @param string $pass the password for the DB connection
     * @param string $db_name the name of the DB
     * @param string $db_type the type of the DB (currently only 'mysql')
     * @param string $retr the number attempts for the DB connection before a failure is reported
     * @return 0
     */
    function ADOdb($host, $port = "3306", $user, $pass, $db_name, $db_type, $retr = 20, $debug = FALSE, $logfile = FALSE) {

        $this->debug = $debug;
        if ($logfile != FALSE) $this->logfile = $logfile;

        $try = 0;
        $hostport = $host . ":" . $port;
        $this->conn = NewADOConnection($db_type);
        while ($try <= $retries) {
            if ($this->conn->NConnect($hostport,$user,$pass,$db_name)) {
                $this->conn = $this->conn;
                return 0;
            }
            $try++;
            usleep(50000);
        }

        die("FATAL: Cannot connect to database server on '$host':'$port'. Please make sure you have specified a valid database name in 'includes/config.php'\n");
        return 0;
    }




    /**
     * setDebug - enables or disabled debug mode
     *
     * @param string $debug On or Off
     */
    function setDebug($debug) {
        if ($debug == "On") {
            $this->debug = TRUE;
        } else {
            $this->debug = FALSE;
        }
    }



    /**
     * setLogfile - set logfile path and name
     *
     * @param string $logfile Logfile path and name
     */
    function setLogfile($logfile) {
        if ($logfile != "") {
            $this->logfile = $logfile;
        }
    }



    /**
     * writeMSG
     *
     * @param mixed $msg
     * @access public
     * @return void
     */
    function writeMSG($msg) {

        if ($this->logfile != "") {
            if ($loghandle = fopen($this->logfile, 'a')) {
                $starttime = microtime();
                do {
                    $lock = flock($loghandle, LOCK_EX);
                    if (!$lock) usleep(round(rand(0, 100) * 1000));
                } while (!$lock && ((microtime() - $starttime) < 1000));

                if ($lock) {
                    fwrite($loghandle, $msg);
                }
                fclose($loghandle);
            } else {
                echo "ERROR: Cannot open logfile: '".$logfile."' for writing. Falling back to STDOUT.\n";
                echo $msg;
            }
        } else {
            echo $msg;
        }
    }


    /**
     * closeDB - close an open DB connection
     *
     * @return string 
     */
    function closeDB() {
        if ($this->conn) {
            return $this->conn->Close();
        }
    }


    /**
     * execDB - execute a SQL statement against the DB via ADODB
     *
     * @param string $sql SQL statement to execute
     * @return ADORecordSet
     */
    function execDB($sql) {
        $this->conn->debug = $this->debug;
        $sql = $this->sanitizeSQL($sql);

        try {
            $recordSet = &$this->conn->Execute($sql);
        } catch (exception $e) {
            $this->writeMSG(adodb_backtrace($e->gettrace()));
            exit;
        }

        if (($recordSet) || ($this->conn->ErrorNo() == 0)) {
            return($recordSet);
        }
    }


    /**
     * fetchCellDB - execute a SQL query against the DB via ADODB and 
     *               return only the first column of the fist row found
     *               or a specified column of the fist row found
     *
     * @param string $sql SQL statement to execute
     * @param $column_name Column name to use instead of the first column
     * @return string Content of the cell as a single variable
     */
    function fetchCellDB($sql, $column_name) {
        $this->conn->debug = $this->debug;
        $sql = $this->sanitizeSQL($sql);

        if ($column_name != '') {
            $this->conn->SetFetchMode(ADODB_FETCH_ASSOC);
        } else {
            $this->conn->SetFetchMode(ADODB_FETCH_NUM);
        }
        try {
            $recordSet = $this->conn->Execute($sql);
        } catch (exception $e) {
            $this->writeMSG(adodb_backtrace($e->gettrace()));
            exit;
        }

        if (($recordSet) || ($this->conn->ErrorNo() == 0)) {
            if (!$recordSet->EOF) {
                if ($column_name != '') {
                    $column = $recordSet->fields[$column_name];
                }else{
                    $column = $recordSet->fields[0];
                }
                $recordSet->close();

                return($column);
            }
        }
    }


    /**
     * fetchRowDB - execute a SQL query against the DB via ADODB
     *              and return only the first row found
     *
     * @param string $sql SQL statement to execute
     * @return array First row of results as an associative array
     */
    function fetchRowDB($sql) {
        $this->conn->debug = $this->debug;
        $sql = $this->sanitizeSQL($sql);

        $this->conn->SetFetchMode(ADODB_FETCH_ASSOC);
        try {
            $recordSet = $this->conn->Execute($sql);
        } catch (exception $e) {
            $this->writeMSG(adodb_backtrace($e->gettrace()));
            exit;
        }

        if (($recordSet) || ($this->conn->ErrorNo() == 0)) {
            if (!$recordSet->EOF) {
                $recordFields = $recordSet->fields;
                $recordSet->close();

                return($recordFields);
            }
        }
    }


    /**
     * fetchArrayDB - execute a SQL query against the DB via ADODB
     *                and return results in an associative array.
     *
     * @param string $sql SQL statement to execute
     * @return array All results in an associative array
     */
    function fetchArrayDB($sql) {
        $this->conn->debug = $this->debug;
        $sql = $this->sanitizeSQL($sql);

        $recordArray = array();
        $this->conn->SetFetchMode(ADODB_FETCH_ASSOC);
        try {
            $recordSet = $this->conn->Execute($sql);
        } catch (exception $e) {
            $this->writeMSG(adodb_backtrace($e->gettrace()));
            exit;
        }

        if (($recordSet) || ($this->conn->ErrorNo() == 0)) {
            while ((!$recordSet->EOF) && ($recordSet)) {
                $recordArray{sizeof($recordArray)} = $recordSet->fields;
                $recordSet->MoveNext();
            }
            $recordSet->close();
            return($recordArray);
        }
    }


    /**
     * updateDB - execute a SQL update statement against the DB via ADODB
     *            to update a record. If the record is not found, an insert
     *            statement is generated and executed.
     *
     * @param string $table The name of the table containing the record to be updated
     * @param array $cells An array of columnname/value pairs of the record to be updated
     * @param string $keys Name of the primary key
     * @param boolean $autoquote Use intelligent auto-quoting
     * @param ADOConnection $this->conn DB connection ID to run the SQL against
     * @return string Auto-increment ID if insert was performed
     */
    function updateDB($table, $cells, $keys, $autoquote = TRUE) {
        $this->conn->debug = $this->debug;
        try {
            $this->conn->Replace($table, $cells, $keys, $autoquote);
        } catch (exception $e) {
            $this->writeMSG(adodb_backtrace($e->gettrace()));
            exit;
        }

        return $this->conn->Insert_ID();
    }


    /**
     * sanitizeSQL - removes unwanted chars in values passed for use in
     *               SQL statements
     *
     * @param string $sql SQL expression to sanitize
     * @return string
     */
    function sanitizeSQL($sql) {
        $sql = str_replace(";", "\;", $sql);
        $sql = str_replace("\n", "", $sql);
        $sql = str_replace("\r", "", $sql);
        $sql = str_replace("\t", " ", $sql);
        return $sql;
    }

}

?>
