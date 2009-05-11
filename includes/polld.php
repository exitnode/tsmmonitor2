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
 * polld.php, TSM Monitor
 * 
 * This file is the TSM Monitor Polling Daemon. It executes queries against TSM
 * and inserts them into TMS Monitor's MySQL Database
 * 
 * @author Michael Clemens
 * @version 1.0
 * @package tsmmonitor
 */




/**
 *
 * Class PollD
 *
 */

class PollD {

var $servers;
var $queries;
var $overviewqueries;
var $db_host;
var $db_name;
var $db_user;
var $db_password;

var $log_timeneeded;
var $log_unchangedresult;
var $log_pollfreqnoreached;
var $log_updated;



/**
 * setDBParams - helper function to set db parameter properties
 *
 * @param string $db_host
 * @param string $db_name
 * @param string $db_user
 * @param string $db_password
 */

function setDBParams($db_host, $db_name, $db_user, $db_password){

	$this->db_host = $db_host;
	$this->db_name = $db_name;
	$this->db_user = $db_user;
	$this->db_password = $db_password;

}



/**
 * initialize - initializes this class
 *
 */

function initialize() {
	
	$this->servers = $this->getServers();
	$this->queries = $this->getQueries();
	$this->overviewqueries = $this->getOverviewQueries();

}




/**
 * fireMySQLQuery - executes a SQL query
 *
 * @param string $sql SQL query
 * @param boolean $direct give back MySQLResultSet directly or Array of Objects
 * @return MySQLResultSet/Array
 */

function fireMySQLQuery($sql, $getreturnval = TRUE){

	$ret = array();

	$db = mysql_connect($this->db_host, $this->db_user, $this->db_password);
	mysql_select_db($this->db_name, $db);
	$result = mysql_query($sql, $db);
	//if (strtolower(substr($sql,0,6)) == "select" && $getreturnval && $result ) {
	if ($getreturnval && $result ) {
		while($row = mysql_fetch_object($result))
		{
		     $ret[] = $row;
		}
		mysql_free_result($result);
	}

	return $ret;
}



/**
 * getQueries - returns an array filled with all configured TSM queries
 *
 */

function getQueries() {
	$queries = array();
	$query = "select * from cfg_queries";
	$querytablerows = $this->fireMySQLQuery($query);
	while (list ($subkey, $queryrow) = each ($querytablerows)) {
		$queries[$queryrow->name] = (array)$queryrow;
		$temparray=split(",", $queryrow->Fields);
		$cols = array();
		while (list ($subkey, $col) = each ($temparray)) {
			$temp = split("!", $col);
			$cols[$subkey]["label"] = $temp[0];
			$cols[$subkey]["name"] = $temp[1];
		}
		$queries[$queryrow->name]["header"]["column"] = $cols;
	}
	return $queries;
}




/**
 * getOverviewQueries - returns an array filled with all configured TSM overview queries
 *
 * @return array
 */

function getOverviewQueries() {
        $queries = array();
        $query = "select * from cfg_overviewqueries";
        $querytablerows = $this->fireMySQLQuery($query);
        while (list ($subkey, $queryrow) = each ($querytablerows)) {
                $queries[$queryrow->name] = (array)$queryrow;
        }
        return $queries;
}


/**
 * getServers - returns an array containing all defined servers
 *
 * @return array
 */

function getServers() {
	$query = "select * from cfg_servers";
	$rows = $this->fireMySQLQuery($query);
	$servers = array();
	while (list ($key, $val) = each ($rows)) {
		$servers[$val->servername] = (array)$val;
	}
	return $servers;
}





/**
 * execute - executes a TSM query on a TSM server and returns an array containing SQL insert statements including the results
 *
 * @param string $query TSM query
 * @param string $servername name of the TSM server
 * @param string $restable
 * @param string $timestamp 
 * @param string $overviewname
 * @return array
 */

function execute($query = '', $servername = '', $restable = '', $timestamp = '', $overviewname = '') {

	$server = $this->servers[$servername];
        $ip = $server["ip"];
        $port = $server["port"];
	$user = $server["username"];
	$pass = $server["password"];
	$out = array();

        $originalquery = $query;
        $query = ereg_replace("NOTEQUAL","<>",$query);
        $query = ereg_replace("LESS","<",$query);

	$handle = popen("dsmadmc -se=$servername -id=$user -password=$pass -TCPServeraddress=$ip -COMMMethod=TCPIP -TCPPort=$port -dataonly=yes -TAB \"$query\" ", 'r');

	$hashstring = "";

	if ($handle) {

		 while (!feof($handle) && !$stop) {
			$read = fgets($handle, 4096);
			$hashstring .= $read;
			$stop = strstr($read, 'ANR2034E');
			//$stop = strstr($read, 'ANS8023E');
			if ($read != ' ' && $read != '' && !$stop) {
				$read = preg_replace('/[\n]+/', '', $read);
				if ($restable == "res_querysession_TSMSRV1") echo $read."\n";
				$read = ereg_replace("\t","\",\"",$read);
				if ($restable == "res_querysession_TSMSRV1") echo $read."\n";
				if ($timestamp != '') {
					$out[] = 'INSERT IGNORE INTO '.$restable.' values ("'.$timestamp.'", "'.$read.'")';
				} else {
					$out[] = 'INSERT INTO '.$restable.' (name, result) values ("'.$overviewname.'", "'.$read.'") ON DUPLICATE KEY update result="'.$read.'"';
				}
				//$out[] = $read;
			}
		}
	}
	$return["sql"] = $out;
	$return["md5"] = md5($hashstring);
	return $return;

}


/**
 * checkHash - checks with checksum if there's already the same result in the result database. If not, the hash will be stored.
 *
 * @param string $tablename name of table
 * @param string $hash md5 hash checksum of current resultSet
 * @return boolean
 */

function checkHash($tablename = '', $hash = ''){

	$sql = "select count(*) from log_hashes where TABLENAME='".$tablename."' and HASH='".$hash."'";
	$countobj = $this->fireMySQLQuery($sql, TRUE);
	$countarray = (array)$countobj[0];
	$count = $countarray["count(*)"];
	
	if ($count > 0) {
		return TRUE;
	} else {
		$sql = 'INSERT INTO log_hashes VALUES ("'.$tablename.'", "'.$hash.'")';
		//echo $sql;
		$this->fireMySQLQuery($sql, FALSE);
		return FALSE;
	}

}



/**
 * checkFreq - checks if configured checking frequency is reached
 *
 * @param string $tablename name of table
 * @param string $pollfreq polling frequency
 * @param string $timestamp timestamp
 * @return boolean
 */

function checkFreq($tablename, $pollfreq, $timestamp) {

        $sql = "select MAX(TimeStamp) from ".$tablename;
        $res = $this->fireMySQLQuery($sql, TRUE);
        $resarray = (array)$res[0];
	$lastinsert = $resarray["MAX(TimeStamp)"];

	if ($lastinsert!="" && ($lastinsert+($pollfreq*60))>=$timestamp) {
		return TRUE;
	} else {
		return FALSE;
	}
}



/**
 * getSleeptime - searches for the smallest polling frequency and returns the time to sleep
 *
 * @return string
 */

function getSleeptime() {

        $sql = "select MIN(pollfreq) from cfg_queries";
        $res = $this->fireMySQLQuery($sql, TRUE);
        $resarray = (array)$res[0];
        $minqueries = $resarray["MIN(pollfreq)"];

        $sql = "select MIN(pollfreq) from cfg_overviewqueries";
        $res = $this->fireMySQLQuery($sql, TRUE);
        $resarray = (array)$res[0];
        $minoverview = $resarray["MIN(pollfreq)"];

	return min($minoverview,$minqueries)*60;

}


/**
 * pollQuery - executes a TSM query and stores result in MySQL DB
 *
 * @param array $query
 * @param array $server
 * @param boolean $ignorePollFreq
 * @param string $timestamp
 */

function pollQuery($query = "", $server = "", $ignorePollFreq = FALSE, $timestamp){

	$tablename = "res_".$query["name"]."_".$server["servername"];
	if (!$ignorePollFreq) echo "---------".$query["name"].": ";
	// drop result table if init=yes
	if ($init == "yes") {
		$dropsql = "drop table ".$tablename;
		$this->fireMySQLQuery($dropsql, FALSE);
		$updatehashsql = "delete from log_hashes where tablename='".$tablename."'";
		$this->fireMySQLQuery($updatehashsql, FALSE);
		//echo "------------deleted hash for ".$tablename."\n";
	}
	// create table if not exists
	$showsql = "SHOW TABLES LIKE '".$tablename."'";
	$res = $this->fireMySQLQuery($showsql, TRUE);
	if (!isset($res[0])) {
		$fieldsql = "select fields from cfg_queries where name='".$query["name"]."'";
		$fields = $this->fireMySQLQuery($fieldsql, TRUE);
		$ctsql = "CREATE TABLE `".$tablename."` (".$fields[0]->fields.") DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		if (!$ignorePollFreq) echo "created table ".$tablename." and ";
		//$ctsql = "CREATE TABLE IF NOT EXISTS ".$tablename." LIKE smp_".$query["name"];
		$this->fireMySQLQuery($ctsql, FALSE);
	}
	// execute query and store result in mysql db
	$result = $this->execute($query["tsmquery"], $server["servername"], $tablename, $timestamp);
	if ($ignorePollFreq || !$this->checkFreq($tablename, $query["pollfreq"], $timestamp)){
		if (!$this->checkHash($tablename, $result["md5"])) {
			if ($query["polltype"]=="update") {
				$dropsql = "truncate table ".$tablename;
				$this->fireMySQLQuery($dropsql, FALSE);
				if (!$ignorePollFreq) echo " TRUNCATED TABLE and ";
			}
			foreach ($result["sql"] as $insertquery) {
				if ($query["name"] == "querysession") echo "\n\n".$insertquery."\n\n";
				$this->fireMySQLQuery($insertquery, FALSE);
			}
			if (!$ignorePollFreq) echo "inserted new rows into ".$tablename."\n";
			$this->log_updated++;
		} else {
			if (!$ignorePollFreq) echo "no need to update result -> result is the same as last time\n";
			$this->log_unchangedresult++;
		}
	} else {
		if (!$ignorePollFreq) echo "no need to update result -> pollfreq not reached!\n";
		$this->log_pollfreqnoreached++;
	}
}


/**
 * pollOverviewQuery - executes a TSM overview query and stores result in MySQL DB
 *
 * @param array $query
 * @param array $server
 * @param boolean $ignorePollFreq
 * @param string $timestamp
 */

function pollOverviewQuery($query = "", $server = "", $timestamp){

	$tablename = "res_overview_".$server["servername"];
	echo "---------".$query["name"].": ";
	if ($init == "yes") {

	}
	//$ctsql = "CREATE TABLE IF NOT EXISTS ".$tablename." LIKE smp_overview";
	$ctsql = "CREATE TABLE IF NOT EXISTS ".$tablename." ( `name` varchar(35) collate utf8_unicode_ci NOT NULL, `result` varchar(255) collate utf8_unicode_ci NOT NULL, UNIQUE KEY `name` (`name`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
	$this->fireMySQLQuery($ctsql, FALSE);
	$result = $this->execute($query["query"], $server["servername"], $tablename, '', $query["name"]);
	foreach ($result["sql"] as $insertquery) {
		$this->fireMySQLQuery($insertquery, FALSE);
		echo "inserted row\n";
	}

}



/**
 * poll - the main function that polls the data
 *
 * @param string $init deletes all result tables and sets TSM Monitor back to the state of virginity if "yes" is given
 * @return boolean
 */

function poll($init = "no"){

	$sleeptime = $this->getSleeptime();


	echo "Sleeptime will be ".$sleeptime." seconds\n";
	

	// infinite loop
	while(true) {
	
		$timestamp = time();

		echo "running!\n";
		echo "timestamp for this run is ".$timestamp."\n";


		foreach ($this->servers as $server) {
			$this->log_timeneeded = time();
			$this->log_unchangedresult = 0;
			$this->log_pollfreqnoreached = 0;
			$this->log_updated = 0;
			// go through all queries defined in xml file
			echo "---querying server ".$server["servername"]."\n";
			echo "------querying normal queries\n";
			foreach ($this->queries as $query) {
				$this->pollQuery($query, $server, FALSE, $timestamp);
			}
			echo "------querying overview queries\n";
			if ($init == "yes") {
				$tablename = "res_overview_".$server["servername"];
				$dropsql = "drop table ".$tablename;
				$this->fireMySQLQuery($dropsql, FALSE);
			}
			foreach ($this->overviewqueries as $query) {
				$this->pollOverviewQuery($query, $server, $timestamp);
			}
			$sql = 'INSERT INTO log_polldstat VALUES ("'.$timestamp.'", "'.$server["servername"].'", "'.$log_updated.'", "'.$log_unchangedresult.'", "'.$log_pollfreqnoreached.'", "'.(time()-$log_timeneeded).'")';
			$this->fireMySQLQuery($sql, FALSE);
		}
		$init = "no";

		
		echo "needed ".(time()-$timestamp)." seconds for this run.\n";
		//$tempsleeptime = $sleeptime-(time()-$timestamp);
		$tempsleeptime = 900 -(time()-$timestamp);
		echo "sleeping for ".$tempsleeptime." seconds...\n";
		echo "next run will be at ".strftime("%H:%M:%S", (time()+$tempsleeptime))."\n\n";

		sleep ($tempsleeptime);

	}

}

}


?>