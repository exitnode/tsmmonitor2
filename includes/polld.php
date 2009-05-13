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
	var $adodb;

	var $log_timeneeded;
	var $log_unchangedresult;
	var $log_pollfreqnoreached;
	var $log_updated;




	/**
	 * constructor
	 *
	 */

	function PollD($adodb) {

		$this->adodb = $adodb;
		$this->servers = $this->getServers();
		$this->queries = $this->getQueries();
		$this->overviewqueries = $this->getOverviewQueries();
	}




	/**
	 * getQueries - returns an array filled with all configured TSM queries
	 *
	 */

	function getQueries() {
		$queries = array();
		$query = "select * from cfg_queries";
		$querytablerows = $this->adodb->fetchArrayDB($query);
		while (list ($subkey, $queryrow) = each ($querytablerows)) {
			$queries[$queryrow["name"]] = (array)$queryrow;
			$temparray=split(",", $queryrow->Fields);
			$cols = array();
			while (list ($subkey, $col) = each ($temparray)) {
				$temp = split("!", $col);
				$cols[$subkey]["label"] = $temp[0];
				$cols[$subkey]["name"] = $temp[1];
			}
			if ($queryrow->name != "") $queries[$queryrow->name]["header"]["column"] = $cols;
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
		$querytablerows = $this->adodb->fetchArrayDB($query);
		while (list ($subkey, $queryrow) = each ($querytablerows)) {
			$queries[$queryrow["name"]] = (array)$queryrow;
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
		$rows = $this->adodb->fetchArrayDB($query);
		$servers = array();
		while (list ($key, $val) = each ($rows)) {
			$servers[$val["servername"]] = (array)$val;
		}
		return $servers;
		//return $rows;
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
				$stop = strstr($read, 'ANS1017E');
				$stop = strstr($read, 'ANS8023E');
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
		if (!$stop){
			return $return;
		} else {
			return "";
		}

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
		$countobj = $this->adodb->fetchArrayDB($sql);
		//$countobj = $this->fireMySQLQuery($sql, TRUE);
		$countarray = (array)$countobj[0];
		$count = $countarray["count(*)"];
		
		if ($count > 0) {
			return TRUE;
		} else {
			$sql = 'INSERT INTO log_hashes VALUES ("'.$tablename.'", "'.$hash.'")';
			$colarray = array();
			$colarray[$tablename] = $hash;
			//echo $sql;
			$this->adodb->updateDB("log_hashes", $colarray, 'tablename');
			//$this->fireMySQLQuery($sql, FALSE);
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
		//$res = $this->fireMySQLQuery($sql, TRUE);
		$res = $this->adodb->fetchArrayDB($sql);
		$resarray = $res[0];
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
		$res = $this->adodb->fetchArrayDB($sql);
		//$res = $this->fireMySQLQuery($sql, TRUE);
		$resarray = (array)$res[0];
		$minqueries = $resarray["MIN(pollfreq)"];

		$sql = "select MIN(pollfreq) from cfg_overviewqueries";
		$res = $this->adodb->fetchArrayDB($sql);
		//$res = $this->fireMySQLQuery($sql, TRUE);
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

		$queryname = $query["name"];
		$tablename = "res_".$queryname."_".$server["servername"];
		if (!$ignorePollFreq) echo "---------".$queryname.": ";
		// create table if not exists
		$showsql = "SHOW TABLES LIKE '".$tablename."'";
		//$res = $this->fireMySQLQuery($showsql, TRUE);
		$res = $this->adodb->fetchArrayDB($showsql);
		if (!isset($res[0])) {
			$fieldsql = "select fields from cfg_queries where name='".$queryname."'";
			//$fields = $this->fireMySQLQuery($fieldsql, TRUE);
			$fields = $this->adodb->fetchArrayDB($fieldsql);
			var_dump($fields);
			$ctsql = "CREATE TABLE `".$tablename."` (".$fields[0]['fields'].") DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
			if (!$ignorePollFreq) echo "created table ".$tablename." and ";
			//$ctsql = "CREATE TABLE IF NOT EXISTS ".$tablename." LIKE smp_".$query["name"];
			//$this->fireMySQLQuery($ctsql, FALSE);
			$this->adodb->execDB($ctsql);
		}
		// execute query and store result in mysql db
		if ($ignorePollFreq || !$this->checkFreq($tablename, $query["pollfreq"], $timestamp)){
			$result = $this->execute($query["tsmquery"], $server["servername"], $tablename, $timestamp);
			if ($result != "") {
				if (!$this->checkHash($tablename, $result["md5"])) {
					if ($query["polltype"]=="update") {
						$dropsql = "truncate table ".$tablename;
						//$this->fireMySQLQuery($dropsql, FALSE);
						$this->adodb->execDB($dropsql);
						if (!$ignorePollFreq) echo " TRUNCATED TABLE and ";
					}
					foreach ($result["sql"] as $insertquery) {
						if ($queryname == "querysession") echo "\n\n".$insertquery."\n\n";
						//$this->fireMySQLQuery($insertquery, FALSE);
						$this->adodb->execDB($insertquery);
					}
					if (!$ignorePollFreq) echo "inserted new rows into ".$tablename."\n";
					$this->log_updated++;
				} else {
					if (!$ignorePollFreq) echo "no need to update result -> result is the same as last time\n";
					$this->log_unchangedresult++;
				}
			} else {
				echo "There was a problem querying the TSM Server ".$server["servername"]."!\n";
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
		//$ctsql = "CREATE TABLE IF NOT EXISTS ".$tablename." LIKE smp_overview";
		$ctsql = "CREATE TABLE IF NOT EXISTS ".$tablename." ( `name` varchar(35) collate utf8_unicode_ci NOT NULL, `result` varchar(255) collate utf8_unicode_ci NOT NULL, UNIQUE KEY `name` (`name`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		$this->adodb->execDB($ctsql);
		//$this->fireMySQLQuery($ctsql, FALSE);
		$result = $this->execute($query["query"], $server["servername"], $tablename, '', $query["name"]);
		if ($result != "") {
			foreach ($result["sql"] as $insertquery) {
				//$this->fireMySQLQuery($insertquery, FALSE);
				$this->adodb->execDB($insertquery);
				echo "inserted row\n";
			}
		} else {
			echo "There was a problem querying the TSM Server ".$server["servername"]."!\n";
		}

	}




	/**
	 * cleanupDatabase - cleans up database (single result tables and/or hash entry)
	 *
	 * @param string $servername 
	 * @param string $queryname
	 * @param string $overviewqueryname
	 * @param string $hashonly do not drop table, just delete entry in log_hashes
	 */

	function cleanupDatabase($servername = "", $queryname = "", $overviewqueryname = "", $hashonly = "yes"){

		if ($servername != "" && $queryname != "" && $overviewqueryname != "") {
			foreach ($this->servers as $server) { 
				if ($servername == "all" || $server["servername"] == $servername) {
					foreach ($this->queries as $query) {
						if (($queryname == "all" || $query["name"] == $queryname) && $queryname != "none") {
							$tablename = "res_".$query["name"]."_".$server["servername"];
							if ($hashonly != "yes") {
								$dropsql = "drop table ".$tablename;
								//$this->fireMySQLQuery($dropsql, FALSE);
								$this->adodb->execDB($dropsql);
							}
							$delsql = "DELETE FROM log_hashes where `tablename` = '".$tablename."'";
							//$this->fireMySQLQuery($delsql, FALSE);
							$this->adodb->execDB($delsql);
						}
					}
					foreach ($this->overviewqueries as $query) {
						if (($overviewqueryname == "all" || $query["name"] == $overviewqueryname) && $overviewqueryname != "none") {
							$tablename = "res_overview_".$server["servername"];
							$dropsql = "drop table ".$tablename;
							//$this->fireMySQLQuery($dropsql, FALSE);
							$this->adodb->execDB($dropsql);
						}
					}
				}
			}
		}
	}




	/**
	 * setPollDStatus
	 *
	 * @param string $status
	 * @param string $lastrun
	 * @param string $nextrun
	 */

	function setPollDStatus($status, $lastrun, $nextrun) {

		if ($status != "") $status = "`status`='".$status."'";
		if ($lastrun != "") $lastrun = ", `lastrun`='".$lastrun."'";
		if ($nextrun != "") $nextrun = ", `nextrun`='".$nextrun."'";
		
		$sql = "update log_polldstat set ".$status." ".$lastrun." ".$nextrun." WHERE `id`='1'";
		//$this->fireMySQLQuery($sql, FALSE);
		$this->adodb->execDB($sql);
	}



	/**
	 * isEnabled - returns true if PollD is enabled
	 *
	 * @returns boolean
	 */

	function isEnabled() {

		$sql = "select enabled from log_polldstat WHERE `id`='1'";
		//$result = $this->fireMySQLQuery($sql, TRUE);
		$result = $this->adodb->fetchArrayDB($sql);
		
		if ($result != "" && $result[0]["enabled"] == "1"){
			return TRUE;
		} else {
			return FALSE;
		}

	}


	/**
	 * controlPollD - enables or disables polld
	 *
	 * @param string switch on or off
	 */

	function controlPollD($switch = "") {

		if ($switch == "on") {
			$val = "1";
		} else if ($switch == "off") {
			$val = "0";
		} else {
			return "";
		}
		//$colarray = array();
		//$colarray["enabled"] = $val;
		$sql = "update log_polldstat set `enabled` = '".$val."' WHERE `id`='1'";
		//$this->fireMySQLQuery($sql, FALSE);
		echo $sql;
		$this->adodb->execDB($sql);
		//$this->adodb->updateDB("log_polldstat", $colarray, 'id');

	}

	/**
	 * get Status - returns status of PollD
	 *
	 * @returns string
	 */

	function getStatus() {

		$sql = "select status from log_polldstat WHERE `id`='1'";
		//$result = $this->fireMySQLQuery($sql, TRUE);
		$result = $this->adodb->fetchArrayDB($sql);

		return $result[0]["status"];
		

	}


	/**
	 * poll - the main function that polls the data
	 *
	 * @return boolean
	 */

	function poll(){

		//$this->controlPollD("off");

		$sleeptime = $this->getSleeptime();


		echo "Sleeptime will be ".$sleeptime." seconds\n";
		

		// infinite loop
		while(true) {

			if ($this->isEnabled()) {	
		
				$timestamp = time();

				echo "running!\n";
				echo "timestamp for this run is ".$timestamp."\n";
				
				$this->setPollDStatus("running", "", "");


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
					foreach ($this->overviewqueries as $query) {
						$this->pollOverviewQuery($query, $server, $timestamp);
					}
					$sql = 'INSERT INTO log_polldlog VALUES ("'.$timestamp.'", "'.$server["servername"].'", "'.$log_updated.'", "'.$log_unchangedresult.'", "'.$log_pollfreqnoreached.'", "'.(time()-$log_timeneeded).'")';
					//$this->fireMySQLQuery($sql, FALSE);
					$this->adodb->execDB($sql);
				}
				$init = "no";

				
				echo "needed ".(time()-$timestamp)." seconds for this run.\n";
				//$tempsleeptime = $sleeptime-(time()-$timestamp);
				$tempsleeptime = 900 -(time()-$timestamp);
				echo "sleeping for ".$tempsleeptime." seconds...\n";
				echo "next run will be at ".strftime("%H:%M:%S", (time()+$tempsleeptime))."\n\n";

				$this->setPollDStatus("sleeping", $timestamp, (time()+$tempsleeptime));


				sleep ($tempsleeptime);

			} else {

				echo "PollD is disabled. Sleeping for 5 minutes...\n";
				sleep (3);

			}
		}

	}

}


?>
