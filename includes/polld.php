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
	var $debuglevel; // VERBOSE=4, INFO=3, WARN=2, ERROR=1, OFF=0
    var $loghandle;

	var $log_timeneeded;
	var $log_unchangedresult;
	var $log_pollfreqnoreached;
	var $log_updated;


	/**
	 * PollD 
	 * 
	 * @param mixed $adodb 
	 * @access public
	 * @return void
	 */
	function PollD($adodb) {

		$this->setDebuglevel("INFO");
		$this->adodb = $adodb;
        $sql = "select confval from cfg_config WHERE `confkey`='loglevel_polld'";
        $loglevel = $this->adodb->fetchArrayDB($sql);
        $loglevel = strtoupper($loglevel[0][confval]);
        $this->setDebuglevel("$loglevel");
        $sql = "select confval from cfg_config WHERE `confkey`='path_polldlog'";
        $logfile = $this->adodb->fetchArrayDB($sql);
        if ($logfile[0][confval] != "") {
            $this->loghandle = fopen($logfile[0][confval], 'at');
            if (!$this->loghandle) {
                echo "ERROR: Cannot open logfile: '".$logfile[0][confval]."' for writing. Falling back to STDOUT.\n";
            }
        }
		$this->servers = $this->getServers();
		$this->queries = $this->getQueries();
		$this->overviewqueries = $this->getOverviewQueries();
		//$this->controlPollD("off");
	}


    /**
     * writeMSG 
     * 
     * @param mixed $msg 
     * @param mixed $level VERBOSE, INFO, WARN, ERROR, OFF
     * @access public
     * @return void
     */
    function writeMSG($msg, $level) {

		switch ($level) {
			case ("OFF"):
				$ilevel = 0;
				break;
			case ("ERROR"):
				$ilevel = 1;
				break;
			case ("WARN"):
				$ilevel = 2;
				break;
			case ("INFO"):
				$ilevel = 3;
				break;
			case ("VERBOSE"):
				$ilevel = 4;
				break;
		}

		if ($this->debuglevel >= $ilevel) {
            if ($this->loghandle) {
                fwrite($this->loghandle, $level.": ".$msg);
            }
            else {
                echo $level.": ".$msg;
            }
		}
	}




	/**
	 * setDebuglevel 
	 * 
	 * @param mixed $debuglevel VERBOSE, INFO, WARN, ERROR, OFF
	 * @access public
	 * @return void
	 */
	function setDebuglevel($debuglevel) {

		switch ($debuglevel) {
			case ("OFF"):
				$this->debuglevel = 0;
				break;
			case ("ERROR"):
				$this->debuglevel = 1;
				break;
			case ("WARN"):
				$this->debuglevel = 2;
				break;
			case ("INFO"):
				$this->debuglevel = 3;
				break;
			case ("VERBOSE"):
				$this->debuglevel = 4;
				break;
		}
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
				$blank = strstr($read, 'ANR2034E'); // dsmadmc runs correctly but result is empty (e.g. processes)
				//$stop = strstr($read, 'ANS8023E');
				if ($read != ' ' && $read != '' && !$stop) {
					if ($blank == "") {
						$read = preg_replace('/[\n]+/', '', $read);
						$read = ereg_replace("\t","\",\"",$read);
						$read = ereg_replace("\\\\",'\\\\',$read);
						if ($overviewname == '') {
							$out[] = 'INSERT IGNORE INTO '.$restable.' values ("'.$timestamp.'", "'.$read.'")';
						} else {
							$out[] = 'INSERT INTO '.$restable.' (timestamp, name, result) values ("'.$timestamp.'", "'.$overviewname.'", "'.$read.'") ON DUPLICATE KEY update result="'.$read.'", timestamp="'.$timestamp.'"';
						}
					} else { // result is empty and it's ok
						$out[0] = 'INSERT IGNORE INTO '.$restable.' (timestamp) values ("'.$timestamp.'")';
						$hashstring = "";
						$stop = TRUE;
					}
					//$out[] = $read;
				}
			}
		}
		$return["sql"] = $out;
		$return["md5"] = md5($hashstring);
		if (!$stop || ($blank && $stop)){
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
		$countarray = (array)$countobj[0];
		$count = $countarray["count(*)"];

		if ($count > 0) {
			return TRUE;
		} else {
			$sql = 'INSERT INTO log_hashes VALUES ("'.$tablename.'", "'.$hash.'")';
			$colarray = array();
			$colarray["tablename"] = $tablename;
			$colarray["hash"] = $hash;
			$this->adodb->updateDB("log_hashes", $colarray, 'tablename');
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
		$resarray = (array)$res[0];
		$minqueries = $resarray["MIN(pollfreq)"];

		$sql = "select MIN(pollfreq) from cfg_overviewqueries";
		$res = $this->adodb->fetchArrayDB($sql);
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
	function pollQuery($query = "", $server = "", $ignorePollFreq = TRUE, $timestamp){
        $starttquery = time();
        $querytime = 0;

		$queryname = $query["name"];
		$tablename = "res_".$queryname."_".$server["servername"];
		if (!$ignorePollFreq) {
			$this->writeMSG("---------".$queryname.": ", "INFO");
		}
		// create table if not exists
		$showsql = "SHOW TABLES LIKE '".$tablename."'";
		$res = $this->adodb->fetchArrayDB($showsql);
		if (!isset($res[0])) {
			$fieldsql = "select fields from cfg_queries where name='".$queryname."'";
			$fields = $this->adodb->fetchArrayDB($fieldsql);
			$ctsql = "CREATE TABLE `".$tablename."` (".$fields[0]['fields'].") DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
			if (!$ignorePollFreq) {
				$this->writeMSG("created table ".$tablename." and ", "INFO");
			}
			$this->adodb->execDB($ctsql);
		}
		// execute query and store result in mysql db
		if ($ignorePollFreq || !$this->checkFreq($tablename, $query["pollfreq"], $timestamp)){
			try {
				$result = $this->execute($query["tsmquery"], $server["servername"], $tablename, $timestamp);
			} catch (exception $e) {
				$this->writeMSG("Problem while querying from TSM Server!", "ERROR");
			}
			if ($result != "") {
				if (!$this->checkHash($tablename, $result["md5"])) {
					if ($query["polltype"]=="update") {
						$dropsql = "truncate table ".$tablename;
						try {
							$this->adodb->execDB($dropsql);
						} catch (exception $e) {
							$this->writeMSG("Error while truncating table (".$dropsql.")", "ERROR");
						}
						$this->writeMSG(" TRUNCATED TABLE and ", "INFO");
					}
					foreach ($result["sql"] as $insertquery) {
						try {
							$this->adodb->execDB($insertquery);
							//echo $insertquery;
						} catch (exception $e) {
							$this->writeMSG("Error while inserting into table (".$insertquery.")", "ERROR");
						}
					}
                    $querytime = time() - $starttquery;
					$this->writeMSG("inserted new rows into ".$tablename." ($querytime sec)\n", "INFO");
					$this->log_updated++;
				} else {
                    $querytime = time() - $starttquery;
					$this->writeMSG("no need to update result -> result is the same as last time ($querytime sec)\n", "INFO");
					$this->log_unchangedresult++;
				}
			} else {
				$this->writeMSG("There was a problem querying the TSM Server ".$server["servername"]."!\n", "ERROR");
			} 
		} else {
			$this->writeMSG("no need to update result -> pollfreq not reached!\n", "INFO");
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
        $starttquery = time();
        $querytime = 0;

		$tablename = "res_overview_".$server["servername"];
		$this->writeMSG("---------".$query["name"].": ", "INFO");
		//$ctsql = "CREATE TABLE IF NOT EXISTS ".$tablename." LIKE smp_overview";
		$ctsql = "CREATE TABLE IF NOT EXISTS ".$tablename." ( `timestamp` int(11) collate utf8_unicode_ci NOT NULL, `name` varchar(35) collate utf8_unicode_ci NOT NULL, `result` varchar(255) collate utf8_unicode_ci NOT NULL, UNIQUE KEY `name` (`name`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		$this->adodb->execDB($ctsql);
		$result = $this->execute($query["query"], $server["servername"], $tablename, $timestamp, $query["name"]);
		if ($result != "") {
			foreach ($result["sql"] as $insertquery) {
				$this->adodb->execDB($insertquery);
                $querytime = time() - $starttquery;
				$this->writeMSG("inserted row ($querytime sec)\n", "INFO");
			}
		} else {
			$this->writeMSG("There was a problem querying the TSM Server ".$server["servername"]."!\n", "ERROR");
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
	function cleanupDatabase($servername = "", $queryname = "", $overviewqueryname = "", $months = "9999" ,$hashonly = "yes"){

		if ($servername != "" && $queryname != "" && $overviewqueryname != "") {
			$time = time()-($months*30*24*60*60);
			$wc = " WHERE `timestamp`<'".$time."'";
			foreach ($this->servers as $server) { 
				if ($servername == "all" || $server["servername"] == $servername) {
					foreach ($this->queries as $query) {
						if (($queryname == "all" || $query["name"] == $queryname) && $queryname != "none") {
							$tablename = "res_".$query["name"]."_".$server["servername"];
							if ($hashonly != "yes") {
								$dropsql = "drop table ".$tablename;
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
		$this->adodb->execDB($sql);
	}



	/**
	 * isEnabled - returns true if PollD is enabled
	 *
	 * @returns boolean
	 */
	function isEnabled() {

		$sql = "select enabled from log_polldstat WHERE `id`='1'";
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
		$sql = "update log_polldstat set `enabled` = '".$val."' WHERE `id`='1'";
		$this->adodb->execDB($sql);

	}

	/**
	 * get Status - returns status of PollD
	 *
	 * @returns string
	 */
	function getStatus() {

		$sql = "select status from log_polldstat WHERE `id`='1'";
		$result = $this->adodb->fetchArrayDB($sql);

		return $result[0]["status"];


	}


	/**
	 * poll - the main function that polls the data
	 *
	 * @return boolean
	 */
	function poll(){


		$sleeptime = $this->getSleeptime();


		$this->writeMSG("Sleeptime will be ".$sleeptime." seconds\n", "WARN");


		// infinite loop
		while(true) {

			if ($this->isEnabled()) {	

				$timestamp = time();

				$this->writeMSG("running!\ntimestamp for this run is ".$timestamp."\n", "WARN");

				$this->setPollDStatus("running", "", "");


				foreach ($this->servers as $server) {
					$this->log_timeneeded = time();
					$this->log_unchangedresult = 0;
					$this->log_pollfreqnoreached = 0;
					$this->log_updated = 0;
					// go through all queries defined in xml file
					$this->writeMSG("---querying server ".$server["servername"]."\n", "WARN");
					$this->writeMSG("------querying normal queries\n", "WARN");
					foreach ($this->queries as $query) {
						$this->pollQuery($query, $server, FALSE, $timestamp);
					}
					$this->writeMSG("------querying overview queries\n", "WARN");
					foreach ($this->overviewqueries as $query) {
						$this->pollOverviewQuery($query, $server, $timestamp);
					}
					$sql = 'INSERT INTO log_polldlog VALUES ("'.$timestamp.'", "'.$server["servername"].'", "'.$log_updated.'", "'.$log_unchangedresult.'", "'.$log_pollfreqnoreached.'", "'.(time()-$log_timeneeded).'")';
					$this->adodb->execDB($sql);
				}
				$init = "no";


				$this->writeMSG("needed ".(time()-$timestamp)." seconds for this run.\n", "INFO");
				//$tempsleeptime = $sleeptime-(time()-$timestamp);
				$tempsleeptime = 900 -(time()-$timestamp);
                if ($tempsleeptime < 0) {
                    $tempsleeptime = 0;
                }
				$this->writeMSG("sleeping for ".$tempsleeptime." seconds...\n", "WARN");
				$this->writeMSG("next run will be at ".strftime("%H:%M:%S", (time()+$tempsleeptime))."\n\n", "WARN");

				$this->setPollDStatus("sleeping", $timestamp, (time()+$tempsleeptime));

				sleep ($tempsleeptime);

			} else {

				$this->writeMSG("PollD is disabled. Sleeping for 5 minutes...\n", "ERROR");
				sleep (3);

			}
		}

	}

}


?>
