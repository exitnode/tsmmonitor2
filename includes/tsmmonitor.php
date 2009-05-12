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
 * tsmmonitor.php, TSM Monitor
 * 
 * TSM Monitor main class
 * 
 * @author Michael Clemens
 * @package tsmmonitor
 */


/**
 *
 * Class TSMMonitor
 *
 */

class TSMMonitor {


	var $configarray;
	var $queryarray;
	var $GETVars;
	var $page;
	var $max_pages;
	var $timetablestarttime;

	var $submenu;
	var $adminmenu;
	var $message;
	var $adodb;




	/**
	 * TSMMonitor - constructor
	 *
	 */

	function TSMMonitor($adodb) {

		$this->adodb = $adodb;

		session_name("tsmmonitordev");
		session_start();


		// Login if not logged in and credentials are ok
		if ((isset($_POST) && isset($_POST["loginname"]) && isset($_POST["loginpasswort"]) && (!isset($_SESSION["logindata"])))) {
			$_SESSION["logindata"]["user"] = $_POST["loginname"];
			$_SESSION["logindata"]["pass"] = $_POST["loginpasswort"];
			$this->checkLogin();
		}

		if ($_GET['q'] == "logout") {
			unset($_SESSION["logindata"]);
		}

		$_SESSION['configarray'] = $this->getConfigArray();

		// GET-variables
		$this->GETVars["menu"] = $_GET['m'];
		$this->GETVars["qq"] = $_GET['q'];
		$this->GETVars['ob'] = $_GET['sort'];
		$this->GETVars['orderdir'] = $_GET['so'];

		if ($_POST['s'] != '') {
			$this->GETVars['server'] = $_POST['s'];
		} else {
			$this->GETVars['server'] = $_GET['s'];
		}


		// Session-variables
		$this->configarray = $_SESSION['configarray'];

		// timeout
		if( !ini_get('safe_mode') && ini_get('max_execution_time') != $this->configarray["settings"]["timeout"]) {
			ini_set('max_execution_time', $this->configarray["settings"]["timeout"]);
		}

		// set defaults if vars are empty
		if ($this->GETVars["menu"] == "") { $this->GETVars["menu"]="main"; }
		if ($this->GETVars["qq"] == "") { $this->GETVars["qq"]="index"; }
		if ($this->GETVars['server'] == "") { $this->GETVars['server']=$this->configarray["defaultserver"]; }
		if ($this->GETVars['orderdir'] == "") { $this->GETVars['orderdir'] = "asc"; }

		if ($_SESSION['timeshift'] == '' ||  !strstr($this->GETVars["qq"], 'dynamictimetable')) {
			$_SESSION['timeshift'] = 0 ;
		}

		$this->submenu = $this->configarray["menuarray"][$this->GETVars['menu']];
		$this->adminmenu = $this->configarray["adminmenuarray"];
		//$query = $this->configarray["queryarray"][$this->GETVars['qq']]["tsmquery"];
		$this->queryarray = $this->configarray["queryarray"];

		$_SESSION["GETVars"] = $this->GETVars;


		// BEGIN Timemachine
		if ($_SESSION["from"] != $_GET['q']) {
			$_SESSION['timemachine'] = "";	
		}

		if ($_POST['dateinput'] != "") $_SESSION['timemachine']['date'] = strtotime($_POST['dateinput']);
		if ($_POST['timestamps'] != "") $_SESSION['timemachine']['time'] = $_POST['timestamps'];


		if ($_POST["Poll"] == "Poll Now!") {
			$timestamp = time();
			$tmonpolld = new PollD();
			//$tmonpolld->setDBParams($this->db_host, $this->db_name, $this->db_user, $this>db_password);
			$tmonpolld->initialize();
			$tmonpolld->pollQuery($tmonpolld->queries[$this->GETVars['qq']], $tmonpolld->servers[$this->GETVars['server']], TRUE, $timestamp);
			$_SESSION['timemachine']['date'] = $timestamp;
			$_SESSION['timemachine']['time'] = $timestamp;
		}


		if (($_POST['Poll'] == "Poll Now!" || $_SESSION['timemachine']['date'] == "") && $this->queryarray[$this->GETVars['qq']]["polltype"]=="snapshot" || $_POST['s'] != "" && $this->GETVars['qq'] != "overview" && $this->GETVars['qq'] != "index") {
			$qtable = $this->configarray["queryarray"][$this->GETVars['qq']]["name"];
			$sql = "SELECT MAX(TimeStamp) FROM res_".$qtable."_".$this->GETVars["server"];
			$res = $this->adodb->fetchArrayDB($sql);
			$resarr = (array)$res[0];
			$_SESSION['timemachine']['date'] = $resarr["MAX(TimeStamp)"];
			$_SESSION['timemachine']['time'] = $resarr["MAX(TimeStamp)"];
		}


		// Custom Stylesheet
		if ($_SESSION['stylesheet'] == "") {
		if ($this->configarray['stylesheet'] != "") {
			$_SESSION['stylesheet'] = $this->configarray['stylesheet'];
		} else {
			$_SESSION['stylesheet'] = "default.css";
		}
		}

	}




        /**
         * $this->fetchSplitArrayDB - execute a SQL query against the DB via ADODB
         *                     and return results in an associative array.
         *
         * @param string $sql SQL statement to execute
         * @param string $rows_per_page number of rows per page a result will have
         * @return array All results in an associative array
         */
        function fetchSplitArrayDB($sql, $rows_per_page = '20') {
        //    $this->conn->debug = true;
            $this->page = intval($_GET['page']);

            $sql = $this->adodb->sanitizeSQL($sql);

            $recordArray = array();
            $this->adodb->conn->SetFetchMode(ADODB_FETCH_ASSOC);
            $recordSet = $this->adodb->conn->Execute($sql);

            if (($recordSet) || ($this->adodb->conn->ErrorNo() == 0)) {
                    $total_rows = $recordSet->RecordCount($recordSet);
		    $this->max_pages = ceil($total_rows/$rows_per_page);

                    if($this->page > $this->max_pages || $this->page <= 0) {
                        $this->page = 1;
                    }
                    $offset = $rows_per_page * ($this->page-1);
                $endset = $offset + $rows_per_page;
                $recordSet->Move($offset);

                while (($recordSet->CurrentRow() < $endset) && ($recordSet->CurrentRow() < $total_rows) && ($recordSet)) {
                    $recordArray{sizeof($recordArray)} = $recordSet->fields;
                    $recordSet->MoveNext();
                }
                $recordSet->close();
                return($recordArray);
            } else {
                echo "<p style='font-size: 16px; font-weight: bold; color: red;'>Database Error (".$this->conn->ErrorNo().")</p>\n<p>".$this->conn->ErrorMsg()."</p>";
                exit;
            }
        }



	/**
	 * showPageNavigation - generates a clickable navigation bar for sql results
	 * splitted by function fetchSplitArrayDB
	 *
	 * @param string links_per_page number of links that will be displayed per page
	 * @return string
	 */

	function showPageNavigation($links_per_page = "1") {

		$this->page = intval($_GET['page']);
		if ($this->page == "") $this->page = 1;
		$so = $_GET['so'];
		$sortcol = $_GET['sort'];

		$getvars = 'q='.$_GET['q'].'&m='.$_GET['m'].'&s='.$this->GETVars['server'].'&sort='.$sortcol."&so=".$so;
		$self = htmlspecialchars($_SERVER['PHP_SELF']);
		$navelement = '<a class="tablefooter" href="'.$self.'?'.$getvars.'&page=';

		$fp = "First";
		$lp = "Last";
		$np = ' &gt;&gt;';
		$pp = '&lt;&lt;';

		if($this->page != 1) {
			//$fp =  '<a class="tablefooter" href="'.$self.'?'.$urlappend.'&page=1">'.$fp.'</a>';
			$fp =  $navelement.'1">'.$fp.'</a>';
		}

		if($this->page != $this->max_pages) {
			$lp = $navelement.($this->max_pages).'">'.$lp.'</a>';
		}

		if($this->page > 1) {
			$pp = $navelement.($this->page-1).'">'.$pp.'</a>';
		}

		if($this->page < $this->max_pages) {
			$np = $navelement.($this->page+1).'">'.$np.'</a>';
		}

		// Numbers
		for($i=1;$i<=$this->max_pages;$i+=$links_per_page) {
			if($this->page >= $i) {
				$start = $i;
			}
		}

		if($this->max_pages > $links_per_page) {
			$end = $start+$links_per_page;
			if($end > $this->max_pages) $end = $this->max_pages+1;
		}
		else {
			$end = $this->max_pages;
		}

		$numbers = '';

		for( $i=$start ; $i<=$end ; $i++) {
			if($i == $this->page ) {
				$numbers .= " $i ";
			}
			else {
				$numbers .= ' '.$navelement.$i.'">'.$i.'</a> ';
			}
		}
		if ($end > 1) {
			return $fp.'&nbsp;'.$pp.'&nbsp;'.$numbers.'&nbsp;'.$np.'&nbsp;'.$lp;
		} else {
			return "";
		}
		
	}



	/**
	 * GetBetween - little helper function that returns a string between two given strings
	 *
	 * @param string $content complete string
	 * @param string $start first string
	 * @param string $end second string
	 * @return string
	 */

	function GetBetween($content,$start,$end) {
	    $r = explode($start, $content);
	    if (isset($r[1])) {
		$r = explode($end, $r[1]);
		return $r[0];
	    }
	    return '';
	}




	/**
	 * GetTimemachine - generates a date-/timechooser with which one can select a specific snapshot
	 *
	 * @return string
	 */

	function GetTimemachine() {

		$this->queryarray = $this->configarray["queryarray"];

		$ret = "";

		if ($this->queryarray[$this->GETVars['qq']]["polltype"]=="snapshot") {
			$ret .= "<div class='sidebarinfo'><b>Time Machine</b><br><br><div id='datechooser'>";
			$ret .= "<form name='calform' action='".$_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&s=".$this->GETVars['server']."' method='post'>";
			$ret .= "<input id='dateinput' class='textfield' name='dateinput' type='text' style='width: 100px' value='".strftime("%Y/%m/%d", $_SESSION['timemachine']['date'])."'>";
			$ret .= "<br>";
			$ret .=  "<select name='timestamps' class='button' size=1 style='width: 103px' onchange='submit()'>";
			if (sizeof($this->getTimestampsOfADay($_SESSION['timemachine']['date'])) > 0) {
				$ret .= "<option value='Select Time'> Select Time </option>";
			} else {
				$ret .= "<option value='---------'>---------</option>";
			}
			foreach ($this->getTimestampsOfADay($_SESSION['timemachine']['date']) as $ts) {
				$ret .= "<option value='".$ts['timestamp']."'";
				if ($_SESSION['timemachine']['time'] == $ts['timestamp'])	{ 
					$ret .= " SELECTED ";
				}
				$ret .= "> ".strftime('%H:%M:%S', $ts['timestamp'])."</option>";
				//echo '> '.$ts->timestamp.'</option>';
			}
			$ret .= "</select>";
			$ret .= "<br>";
			$ret .= "<br>";
			$ret .= "<input type='submit' name='Poll' value='Poll Now!' onclick='submit();' class='button'>";
			$ret .= "</form> </div><br></DIV>";

		} else if ($this->queryarray[$this->GETVars['qq']]["polltype"]=="update" || $this->queryarray[$this->GETVars['qq']]["polltype"]=="append") {
			$LastTimestamp = $this->GetLastSnapshot();
			if ($LastTimestamp!="") {
				$ret .= "<div class='sidebarinfo'><b>Time Machine</b><br><br><div id='datechooser'>";
				$ret .= "<br>";
				$ret .= "Last updated: ".strftime('%H:%M:%S', $LastTimestamp);
				$ret .= "<br>";
				$ret .= "<br>";
				$ret .= "<input type='submit' name='Poll' value='Poll Now!' onclick='submit();' class='button'>";
				$ret .= "</form> </div><br></DIV>";
			}

		}

		return $ret;

	}




	/**
	 * getMenu - resturns HTML code for sidebar menu
	 *
	 * @param string $menu currently displayed menu
	 * @param string $activelink link that will be marked as selected
	 * @param string $type index or admin
	 * @return string
	 */

	function getMenu($menu = '', $activelink = '', $type) {

		if (!isset($menu)) { return ""; };
		while(list($key, $val) = each($menu)) {

			$bCont = TRUE;
			$q = $this->GetBetween($key,"q=","&m=");
			if ($this->configarray["queryarray"][$q]["notforlibclient"] == 1 && $this->configarray["serverlist"][$this->GETVars['server']]["libraryclient"] == 1) {
				$bCont = FALSE;
				
			}
			$key = $_SERVER['PHP_SELF']."?".$key;

			if ($type == "index") {
				$key .= "&s=".$this->GETVars['server'];
			}

			if ($val == "Admin") {
				$key = "admin.php";
			}else if ($val == "TSM Monitor") {
				$key = "index.php";
			} 

			if ($val == "trennlinie") {
				$links .= "<br>\n";
			} else if ($bCont) {

				if (!stristr($key,$activelink)) {
					$links .= "<a href=\"$key\">$val</a>\n";
				} else {
					$links .= "<div class=\"aktuell\">$val</div>\n";
				}
			}
		}
		return $links;
	}



	/**
	 * getMessage - returns HTML code for global system message
	 *
	 * @return string
	 */

	function getMessage() {

		return "<div class='sidebarinfo'><b>System Message:</b><br><br>".$this->message."</div>";
	}




	/**
	 * getInfo - returns HTML Code for Infoboxes with information about current query
	 *
	 * @return string
	 */

	function getInfo() {

		$label = $this->configarray["queryarray"][$this->GETVars['qq']]["label"];
		$info = $this->configarray["queryarray"][$this->GETVars['qq']]["info"];

		if ($info != "") {
			$ret = "<div class='sidebarinfo'><b>".$label.":</b><br><br>".$info;
			$ret .= "</div>";
			return $ret;
		}
	}





	/**
	 * getTableheader - generates and returns headers for query result HTML tables.
	 *
	 * @return string
	 */

	function getTableheader() {

	    $tableheader="<tr>";
	    $orderby = $this->configarray["queryarray"][$this->GETVars['qq']]["orderby"];
	    $orderdir = $this->GETVars['orderdir'];
	    $this->page = $_GET['page'];

	    if ($orderdir == "asc") {
		$sonew="desc";
	    } else if ($orderdir == "desc") {
		$sonew="asc";
	    }

	    $isAdmin = strstr($_SERVER['PHP_SELF'], 'admin.php');

	    if ($isAdmin) {
		$sql = "SHOW COLUMNS FROM cfg_".$_GET['q'];
	    } else {
		$sql = "SHOW COLUMNS FROM res_".$this->configarray["queryarray"][$this->GETVars['qq']]["name"]."_".$this->GETVars['server'];
	    }
	    $fieldnames = $this->adodb->fetchArrayDB($sql);

	    // If table has more than one column
	    if (sizeof($fieldnames) > 1) {
		foreach ($fieldnames as $col) {
		    if ($col['Field'] != "timestamp" && $col['Field'] != "id") {
			$name = $col['Field'];
			$arrow = "";
			if (($this->GETVars['ob'] == $name && $this->GETVars['ob']!="") || ($this->GETVars['ob']=="" && $orderby!="" && $orderby == $name)) {
			    $link = "href='".$_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&sort=".$name."&page=".$this->page."&so=".$sonew."&s=".$this->GETVars['server']."'";
			    if ($orderdir == "asc") {
				$arrow = "&uArr;";
			    } else if ($orderdir == "desc") {
				$arrow = "&dArr;";
			    }
			} else {
			    $arrow = "";
			    $link = "href='".$_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&sort=".$name."&page=".$this->page."&s=".$this->GETVars['server']."'";
			}
			$tableheader = $tableheader."<th><a class='navhead' ".$link.">".ucfirst($name)." ".$arrow."</a></th>";
		    }
		}
	    } else {
		if ($orderdir == "asc") {
		    $arrow = "&uArr;";
		} else if ($orderdir == "desc") {
		    $arrow = "&dArr;";
		}
		$link = "href='".$_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&sort=".$name."&page=".$this->page."&so=".$sonew."'";
		$label = $fieldnames[0]['Field'];
		$tableheader = $tableheader."<th><a class='navhead' ".$link.">".$label." ".$arrow."</a></th>";    
	    }
	    if ($isAdmin) {
		$tableheader = $tableheader."<th colspan=2><a class='navhead'></a></th>";
	    }
	    $tableheader=$tableheader."</tr>";
	    return $tableheader;
	}


	/**
	 * checkLogin - processes login procedure and sets loggedin property in SESSION 
	 *
	 */

	function checkLogin() {

		$user = $_SESSION["logindata"]["user"];
		$pass = $_SESSION["logindata"]["pass"];
		$wc = "";

		$isAdmin = strstr($_SERVER['PHP_SELF'], 'admin.php');
		
		if ($user != "" && $pass != "") {
			$sql = "SELECT password, role from cfg_users where username='".$user."'";
			$ret = $this->adodb->fetchArrayDB($sql);

			if ($ret[0] != "" && $ret[0]['password'] == md5($pass)) {
				$_SESSION["logindata"]["role"] = $ret[0]['role'];
				if (!$isAdmin || ($isAdmin && $ret[0]['role'] == "admin")) { 
					$_SESSION["logindata"]["loggedin"] = TRUE;
				}
			} else {
				$_SESSION["logindata"]["loggedin"] = FALSE;
			}
		} else {
			$_SESSION["logindata"]["loggedin"] = FALSE;
		}
		
	}



	/**
	 * checkAlert - this function checks if a value is above, below or equal a alarm valu
	 *
	 * @param string $comperator
	 * @param string $alertval the value which will trigger an alert
	 * @param string $val the value that will be checked 
	 * @return boolean
	 */

	function checkAlert($comperator = '', $alertval = '', $val = '') {

		$error = false;
		
		if (substr($val, -1) == "*") {
			$val = substr($val,0,-1);
		}
		if ($comperator == "equal") {
			if ($val == $alertval ) {
				$error=true;
			}
		} else if ($comperator == "notequal") {
			if ($val != $alertval ) {
				$error=true;
			}
		} else if ($comperator == "less") {
			if ($val < $alertval ) {
				$error=true;
			}
		} else if ($comperator == "more") {
			if ($val > $alertval ) {
				$error=true;
			}
		}
		return $error;

	}




	/**
	 * getTimestampsOfADay - returns a list of all timestamps of a day which are in the database for the current query
	 *
	 * @param string $timestamp a timestamp that s needed to get the current day
	 * @return string
	 */

	function getTimestampsOfADay($timestamp = "") {

		$server = $this->GETVars['server'];
		$ret = array();

		$daystring = strftime("%Y-%m-%d", $timestamp);
		$startofday = strtotime($daystring." 00:00:00");
		$endofday = strtotime($daystring." 23:59:59");

		$qtable = $this->configarray["queryarray"][$this->GETVars['qq']]["name"];

		$timestampquery = " WHERE timestamp between ".$startofday." and ".$endofday;
		$sql = "SELECT distinct timestamp from res_".$qtable."_".$server.$timestampquery;
		$ret = $this->adodb->fetchArrayDB($sql);

		return $ret;

	}


	/**
	 * getLastSnapshot - returns the last inserted timestamp of a query result
	 *
	 * @return string
	 */

	function getLastSnapshot() {

	    $server = $this->GETVars['server'];
	    $ret = array();

	    $qtable = $this->configarray["queryarray"][$this->GETVars['qq']]["name"];

	    $sql = "SELECT MAX(TimeStamp) from res_".$qtable."_".$server;
	    $ret = $this->adodb->fetchArrayDB($sql);
	    $ret = (array)$ret[0];

	    return $ret["MAX(TimeStamp)"];

	}


	/**
	 * $this->getTableFields
	 *
	 * @param string tablename
	 * @return string
	 */

	function getTableFields($tablename="") {

	    $sqlth = "SELECT * from ".$tablename." LIMIT 1";

	    $sqlresth = $this->adodb->fetchArrayDB($sqlth);
	    $columnnames = "";

	    // get all table fields to be selected
	    foreach ($sqlresth as $row) {
		foreach ($row as $colname => $colval) {
		    if ($colname != "timestamp") {
			$columnnames .= "`".$colname."`";
			if ( $i < $numfields-1) $columnnames .= ", ";
		    }
		}
	    }
	    $columnnames = ereg_replace(", $", "", $columnnames);
	    return $columnnames;
	}


	/**
	 * getAdminTables - gets data out of the DB and generates a HTML result table for admin backend
	 *
	 * @param string $type (list, edit, add)
	 * @return string
	 */

	function getAdminTables($type="") {

	    $columnnames = $this->getTableFields("cfg_".$this->GETVars['qq']);

	    if ($this->GETVars['ob'] != '' ) {
		$sqlappend = " order by `".$this->GETVars['ob']."` ".$this->GETVars['orderdir'];
	    } elseif ($this->configarray["queryarray"][$this->GETVars['qq']]["orderby"] != '') {
		$sqlappend = " order by `".$this->configarray["queryarray"][$this->GETVars['qq']]["orderby"]."` ".$this->GETVars['orderdir'];
	    }

	    if ($type == "edit") {
		$wc = " where `id`='".$_GET['id']."' ";
	    }

	    $sql = "SELECT ".$columnnames." from cfg_".$this->GETVars["qq"].$wc.$sqlappend;
	    $_SESSION["lastsql"] = $sql;
	    if ($sqlres) $this->message = $sql;

	    $i = 1;
	    $rs = $this->adodb->fetchArrayDB($sql);

	    foreach ($rs as $row) {
		if ($type=="list") {
		    if ($i % 2 == 0) {
			$outp .= "<tr class='d1'>";
		    }else{
			$outp .= "<tr class='d0'>";
		    }
		    $i++;

		    while(list($keycell, $valcell) = each($row)) {
			if ($keycell == "id") {
			    $id = $valcell;
			} else {
			    $outp .= "<td>".$valcell."</td>";
			}
		    }
		    
		    $baseurl = $_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu'];
		    $outp .= "<td width='20px'><a href='".$baseurl."&id=".$id."&action=edit' onclick=''><img src='images/edit.png' border=0></img></a></td>";
		    $outp .= "<td width='20px'><a href='#' onclick='show_confirm(\"".$baseurl."\", $id, \"delete\")'><img src='images/delete.png' border=0 ></img></a></td>";

		    $outp .= "</tr>\n";
		} else {
		    $outp = $this->adodb->fetchArrayDB($sql);
		    var_dump($outp);
		}
	    }
	    return $outp;
	}


	/**
	 * execute - gets data out of the DB and generates a HTML result table
	 *
	 * @param string $type sets the table type (vertical, standard and graphical time table)
	 * @return string
	 */

	function execute($type = 'table') {

		$colorsarray = $this->configarray["colorsarray"];
		$this->queryarray = $this->configarray["queryarray"][$this->GETVars['qq']];

		$now = time();
		$oneday = 86400;
		$onehour = 3600;
		$tolerance = 1200;

		$server = $this->GETVars['server'];
		$outp = '';
		$outp_cache = '';
		$stop=FALSE;
		$tablearray = array(); 
		$bContinue = TRUE;

		if ($this->GETVars['ob'] != '' ) {
			$sqlappend = " order by `".$this->GETVars['ob']."` ".$this->GETVars['orderdir'];
		} elseif ($this->configarray["queryarray"][$this->GETVars['qq']]["orderby"] != '') {
			$sqlappend = " order by `".$this->configarray["queryarray"][$this->GETVars['qq']]["orderby"]."` ".$this->GETVars['orderdir'];
		}

		$qtable = $this->configarray["queryarray"][$this->GETVars['qq']]["name"];
		$polltype = $this->configarray["queryarray"][$this->GETVars['qq']]["polltype"];

		if ($polltype == "snapshot") {
			if ($_SESSION['timemachine']['time'] == $_SESSION['timemachine']['date']) {
				$timestampquery = " WHERE timestamp=(SELECT MAX(TimeStamp) FROM res_".$qtable."_".$server.")";
			} else {
				$timestampquery = " WHERE timestamp = '".$_SESSION['timemachine']['time']."'";
			}
		} else {
			$timestampquery = "";
		}

		// get only latest entry
		$searcharr = $_SESSION["search"][$this->GETVars['qq']];
		if (isset($searcharr) && $searcharr["field"] != "" && $searcharr["val"] != "") {
			if ($polltype == "snapshot") {
				$wc = " AND ";
			} else {
				$wc = " WHERE ";
			}
			$wc .= "`".$searcharr["field"]."`".$searcharr["op"]."'".$searcharr["val"]."' ";
		} else if (isset($this->timetablestarttime)) {
			$startunix = ((ceil($now/$onehour)*$onehour)-$onehour-$oneday)-(($this->timetablestarttime-24)*$onehour);
			$endunix = $startunix + $oneday + $onehour;
			$start = strftime("%Y-%m-%d %H:%M:%S.000000", $startunix);
			$end = strftime("%Y-%m-%d %H:%M:%S.000000", $endunix);
			$wc = " WHERE `End Time` >= '".$start."' AND `Start Time` <= '".$end."'";
		} else {
			$wc= " ";
		}

		$columnnames = $this->getTableFields("res_".$qtable."_".$server);

		if ($columnnames == "") $bContinue = FALSE;

		//execute the constructed query
		$sql = "SELECT ".$columnnames." from res_".$qtable."_".$server.$timestampquery.$wc.$sqlappend;

		$_SESSION["lastsql"] = $sql;
		if ($sqlres) $this->message = $sql;

		if ($bContinue) {
			if ($type == "table") {
				$i = 1;
				$rs = $this->fetchSplitArrayDB($sql,20);

				foreach ($rs as $row) {
					$color = "";
					$col = $this->queryarray["alert_field"];
					if ($col != '') {
						$error = $this->checkAlert($this->queryarray["alert_comp"], $this->queryarray["alert_val"], $row[$col]);
						if($error) {
							$color = $this->queryarray["alert_col"];
						} else {
							$color = "ok";
						}
						$colorzebra = $colorsarray[$color][$i];
					}
					if ($i % 2 == 0) {
						$outp = $outp."<tr class='d1'>";
					}else{
						$outp = $outp."<tr class='d0'>";
					}
					$i++;
					
					while(list($keycell, $valcell) = each($row)) {
						if($color!="" && $col==$keycell) {
							
							if ($i % 2 == 0) {
								$cellcol = $colorsarray[$color."_light"];
							} else {
								$cellcol = $colorsarray[$color."_dark"];
							}
							$outp = $outp."<td bgcolor='".$cellcol."'>".$valcell."</td>";
						} else {
							$outp = $outp."<td>".$valcell."</td>";
						}

					}
					$outp = $outp."</tr>\n";
				}
			}
			else if ($type == "verticaltable") {
				$outp = $this->adodb->fetchArrayDB($sql);
			}
			else if ($type == "timetable") {
				$sqlres = $this->adodb->fetchArrayDB($sql);
				$outp = array();;
				foreach ($sqlres as $row) {
					$rowarray2 = array();
					while(list($keycell, $valcell) = each($row)) {
						if ($keycell == "Start Time" || $keycell == "End Time") {
							$date = $row[$keycell];
							$rowarray2[] = mktime(substr($date,11,2),substr($date,14,2),substr($date,17,2),substr($date,5,2),substr($date,8,2),substr($date,0,4));
						} else {
							$rowarray2[] = $valcell;
						}
					}
					array_push($outp, $rowarray2);
				}
			}
		}

		return $outp;
	}



	/**
	 * getSearchfield - returns the HTML code of the upper searchfield panel
	 *
	 * @return string
	 */

	function getSearchfield() {

	    $ret = "";
	    $arrfield = "";
	    $arrval = "";
	    $arrop = "";

	    $operators = array ("<", "=", "<>", ">");

	    $searcharr = $_SESSION["search"][$this->GETVars['qq']];
	    if (isset($searcharr)) {
		$arrfield = $searcharr["field"];    
		$arrval = $searcharr["val"];    
		$arrop = $searcharr["op"];    
	    }
	    $sql = "SHOW COLUMNS FROM res_".$this->configarray["queryarray"][$this->GETVars['qq']]["name"]."_".$this->GETVars['server'];
	    $fieldnames = $this->adodb->fetchArrayDB($sql);

	    // Build Field Name Combobox
	    $fieldbox = "<select name='wcfield' size=1 onChange='' class='button'>";
	    foreach ($fieldnames as $field) {
		if ($field['Field'] != "timestamp") {
		    $fieldbox.= '<option value="'.$field['Field'].'"';
		    if ($arrfield == $field['Field']) {$fieldbox.= "SELECTED";}
		    $fieldbox.=  '> '.$field['Field'].' </option>';
		}
	    }
	    $fieldbox.= "</select>";
	    
	    // Build Operator Combobox
	    if ($arrop=="") $arrop="=";
	    $opbox = "<select name='wcop' size=1 onChange='' class='button'>";
		foreach ($operators as $op) {
		$opbox.= '<option value="'.$op.'"';
		if ($arrop == $op) {$opbox.= "SELECTED";}
		$opbox.=  '> '.$op.' </option>';
		}
		$opbox.= "</select>";

	    $link = $_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&s=".$this->GETVars['server'];
	    $ret .= "<form action=".$link." method='post'>";
	    $ret .= $fieldbox;
	    $ret .= $opbox;
	    $ret .= "<input name='wcval' type='text' size='15' maxlength='60' class='textfield' value='".$arrval."'>  ";
	    $ret .= "<input type='submit' name='Search' value='Search' onclick='submit();' class='button'>";
	    $ret .= "<input type='submit' name='Clear' value='Clear' onclick='submit();' class='button'>";
	    $ret .= "</form>";

	    return $ret;

	}



	/**
	 * getServerlist - returns teh HTML code for the server combobox
	 *
	 * @return string
	 */

	function getServerlist() {

		$ret = "";
		$serverlist = $this->configarray["serverlist"];

		$i = 0;
		$ret = "<table class='zebra'>";
		$ret .= "<tr><th>Servername</th><th>Description</th><th>IP-Address</th><th>Port</th></tr>";
		while(list($servername,$serveritems) = each($serverlist)) {
			$listip = $serveritems["ip"];
			$listdescription = $serveritems["description"];
			$listport = $serveritems["port"];
			if ($i == 0) {
				$ret .= "<tr class='d0'>";
				$i = 1;
			} else {
				$ret .= "<tr class='d1'>";
				$i = 0;
			}
			$listlink = $_SERVER['PHP_SELF']."?q=".$_SESSION["from"]."&m=".$this->GETVars['menu']."&s=".$servername;
			$ret .= "<td><a class='nav' href='".$listlink."'>".$servername."</a></td><td>".$listdescription."</td><td>".$listip."</td><td>".$listport."</td></tr>";
		}

		return $ret."</table>";

	}


	/**
	 * getPollDStat - returns the HTML code for the TSM Polling Daemon status/log table
	 *
	 * @return string
	 */

	function getPollDStat() {

	    $i=1;
	    $outp = "<table class='zebra'>";
	    $outp .= "<tr><th>Status</th><th>Last Run</th><th>Next Run</th></tr>";

	    $sql = "SELECT enabled, status, lastrun, nextrun from log_polldstat";
	    $sqlres = $this->adodb->fetchArrayDB($sql);
	    foreach ($sqlres as $row) {
		if ($row['enabled'] == "1") {
			if ($row['status'] == "running") {
				$cellcolor = "green";
			} else if ($row['status'] == "sleeping") {
				$cellcolor = "yellow";
			} else {
				$cellcolor = "red";
			}
			if ($row['nextrun'] != "") $nextrun = strftime("%Y/%m/%d %H:%M:%S", $row['nextrun']);
			$status = $row['status'];
		} else {
			$status = "disabled";
			$cellcolor = "red";
		}
		if ($row['lastrun'] != "") $lastrun = strftime("%Y/%m/%d %H:%M:%S", $row['lastrun']);
		$outp .= "<tr class='d1'><td bgcolor='".$cellcolor."'>".$status."</td><td>".$lastrun."</td><td>".$nextrun."</td></tr>";
	    }
	    $outp .= "</table><br><br>";

	    $outp .= "<table class='zebra'>";
	    $outp .= "<tr><th>Time</th><th>Servername</th><th>Updated</th><th>Unchanged</th><th>Pollfreq not reached</th><th>Time needed (s)</th></tr>";

	    $sql = "SELECT * from log_polldlog where timestamp > '".(time()-86400)."' order by timestamp desc";
	    $_SESSION["lastsql"] = $sql;
	    $rs = $this->fetchSplitArrayDB($sql,20);
	    foreach ($rs as $row) {
		if ($i % 2 == 0) {
		    $outp = $outp."<tr class='d1'>";
		} else {
		    $outp = $outp."<tr class='d0'>";
		}
		$i++;

		while(list($keycell, $valcell) = each($row)) {
		    if ($keycell == "timestamp") {
			$valcell = strftime("%Y/%m/%d %T", $valcell);
		    }
		    $outp = $outp."<td>".$valcell."</td>";
		}
		$outp = $outp."</tr>\n";
	    }
	    $nav = $this->showPageNavigation("20");
	    if ($nav!="") {
		$outp = $outp."<tr><td colspan='6' align='center' class='footer'><a class='navhead'>".$nav."</a></td></tr>";
	    }
	    
	    return $outp."</table>";
	}


	/**
	 * getOverviewRows - returns HTML code for overview page
	 *
	 * @param array $subindexqueryarray array of query objects
	 * @return string
	 */

	function getOverviewRows($subindexqueryarray = '') {

	    $out="";
	    $i=0;

	    while(list($key, $val) = each($subindexqueryarray)) {

		$bgcol="";
		$comperator = "";
		$alertval = "";
		$alertcol = "";
		$cellcolors = $this->configarray["colorsarray"];

		$cache = $subindexqueryarray[$key]["cache"];
		if ($this->configarray["serverlist"][$this->GETVars['server']]["libraryclient"] == 1 && $subindexqueryarray[$key]["notforlibclient"] == 1) {
		    $res = "-§§§-";
		} else {
		    $res = '';
		    $sql = "SELECT name, result from res_overview_".$this->GETVars['server']." where name='".$subindexqueryarray[$key]["name"]."'";
		    $sqlres = $this->adodb->fetchArrayDB($sql);
		    foreach ($sqlres as $row) {
			$res .= $row['name']."§§§".$row['result'];
		    }
		}
		
		if ($i == 1) {
		    $out = $out."<tr class='d1'><td width='50%'>";
		    $i=0;
		} else {
		    $out = $out."<tr class='d0'><td width='50%'>";
		    $i=1;
		}
		$res = split("§§§", $res);
		//$out .= $subindexqueryarray[$key]["header"];
		$out .= $res[0];
		$comperator = $subindexqueryarray[$key]["alert_comp"];
		$alertval = $subindexqueryarray[$key]["alert_val"];
		$alertcol = $subindexqueryarray[$key]["alert_col"];
		$unit = $subindexqueryarray[$key]["unit"];
		$error = $this->checkAlert($comperator, $alertval, $res[1]);
		if ($i==1) {
		    $shade="light";
		} else {
		    $shade="dark";
		}
		if ($error && $res != "" && $res[1] != "-") {
		    $bgcol="bgcolor='".$cellcolors[$alertcol."_".$shade]."'";
		} else {
		    $bgcol="bgcolor='".$cellcolors["ok_".$shade]."'";
		}
		$out .= "</td><td align='center' $bgcol>".$res[1]." ".$unit."</td></tr>\n";
	    }

	    return $out;

	}




	/**
	 * generateTimetableHeader - returns HTML code for timetable header (display of hours)
	 *
	 * @param string $startpunkt I forgot that one
	 * @param string $FirstCol first field of result table
	 * @return string
	 */

	function generateTimetableHeader($startpunkt = '', $FirstCol = '') {
		
		$header = $FirstCol["label"];
		$out= "<tr align='left'><th>".$header."</th><th>";
		for ($count = 0; $count <= 24; $count++) {
			$imagename = strftime("%H", $startpunkt+($count*3600));
			$out .= "<img src='images/".$imagename.".gif' height=20px width=30px title='".strftime("%H:00 (%D)", $startpunkt+($count*$hour))."' />";
		}

		$out .= "</th></tr>";

	return $out;

	}




	/**
	 * generateTimetableNavigation - returns HTML code for timetable header (navigation buttons)
	 *
	 * @return string
	 */

	function generateTimetableNavigation() {


		$timesteps = array("1 hour" => "1", "6 hours" => "6", "12 hours" => "12", "24 hours" => "24");

		$this->timetablestarttime = 24 + $_SESSION['timeshift'];
		$out = "<form action=".$_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&s=".$this->GETVars['server']." method='post'>";
		// get value from combobox
		if ($_POST["timestep"] != "") {
			$_SESSION['selectedtimestep'] = $_POST["timestep"];
		}
		$out .= "<tr><th align='center' colspan=2>";
		$out .= "<input type='submit' class='button' name='back' value='<-' onclick='submit();'>";
		$out .= "<select name='timestep' class='button' size=1 onChange='submit();'>";
		// build combobox
		while(list($label,$value) = each($timesteps)) {
			$out .= '<option value="'.$value.'"';
			if ($_SESSION['selectedtimestep'] == $value){$out .=  "SELECTED";}
			$out .= '> '.$label.'</option>';
		}
		$out .= "</select>";
		$out .= "<input type='submit' class='button' name='forward' value='->' onclick='submit();'>";
		$out .= "</th></tr>";
	       $out .= "</form>";

	       return $out;
	}





	/**
	 * generateTimetable - generates HTML code for graphical timetables
	 *
	 * @param array $tablearray Array containing an SQL query result
	 * @param string $FirstCol first field of result table
	 * @return string
	 */

	function generateTimetable($tablearray = '', $FirstCol = '') {

		$now = time();
		$out = '';
		$height = 8;
		$faktor = 120;
		$oneday = 86400;
		$onehour = 3600;
		$tolerance = 1200;

		$this->timetablestarttime = 24 + $_SESSION['timeshift'];

		$startpunkt = ((ceil($now/$onehour)*$onehour)-$onehour-$oneday)-(($this->timetablestarttime-24)*$onehour);
		$endpunkt = $startpunkt + $oneday + $onehour;
		$lastpoint = ($endpunkt - $startpunkt)/$faktor;

		$out .= "<table class='timetable' width='".$lastpoint."'>";
		$out .= $this->generateTimetableNavigation();
		$out .= $this->generateTimetableHeader($startpunkt, $FirstCol);
		$out .= "</td></tr>";

		$lasttimepoint=$now-($this->timetablestarttime*$onehour)-$tolerance;

		$repeatingcol = "";
		$ii=1;

		while(list($keyrow, $valrow) = each($tablearray)) {
			if ($valrow[1] <= $endpunkt && $valrow[2] > $lasttimepoint) {
				$name = $valrow[0];
				$status = $valrow[3];
				$statusmsg = "";
				$dur = strftime("%H:%M", ($valrow[2]-$valrow[1])-$onehour);
				$shade="";
				if ($valrow[1] < $lasttimepoint) {
					// cut the bar at the left side to fit into table
					$start = 0;
				} else {
					$start = ($valrow[1]-$startpunkt)/$faktor;
				}
				$end = ($valrow[2]-$startpunkt)/$faktor;
				$duration = $end - $start;
				// fake a longer time for better visibility
				if ($duration < 2) {$duration=2;} 
				// cut the bar at the right side to fit into table
				if (($start+$duration)>$lastpoint) {
					$duration = $lastpoint-$start;
					$shade="light";
				}
				if ($valrow[1] < $lasttimepoint) {
					$shade="light";
				}
				if (isset($status)) {
					if ($status == "YES" || $status == "Completed") {
						$barcol = $shade."green";
						$statusmsg = ", Status was OK";
					}else{
						$barcol = $shade."red";
						$statusmsg = ", Status was UNSUCCESSFUL";
					}
				} else {
					$barcol = $shade."grey";
					$statusmsg = "";
				}
				
				if($ii == 1) {
					$out .= "<tr class='d0' width=".$lastpoint.">";
				} else {
					$out .= "<tr class='d1' width=".$lastpoint.">";
					$ii = 0;
				}
				if ($repeatingcol != $valrow[0]) {
					$out .= "<td style='color:#000000;'>".$valrow[0]."</td>";
					$repeatingcol =  $valrow[0];
				} else {
					$out .= "<td>".$valrow[0]."</td>";
				}
				if ($valrow[3] != 'Missed') {
					$out .= "<td class='content'>";
					$out .= "<img src='images/trans.gif' height=1px width=".$start."px />";
					$out .= "<img src='images/".$barcol.".gif' height=".$height."px width=".$duration."px title='".strftime("%H:%M", $valrow[1])." - ".strftime("%H:%M", $valrow[2])." (".$name.", ".$dur."h".$statusmsg.")' />";
				} else {
					$out .= "<td bgcolor='#f49090'>";
				}
				$out .= "</td></tr>\n";
				$ii++;
			}
		}
		$out .= $this->generateTimetableHeader($startpunkt);
		$out .= $this->generateTimetableNavigation();
		$out .= "</table>";

		return $out;

	}




        /**
         * findPath - find a external program in the search path
         *
         * @param string $binary the external program to search for
         * @param string $search_path the search path in which to look for the external program
         * @return string the full path to the external program or empty string if not found
         */
        function findPath($binary, $search_path) {
            foreach ($search_path as $path) {
                if ((file_exists($path . "/" . $binary)) && (is_readable($path . "/" . $binary))) {
                    return($path . "/" . $binary);
                }
            }
        }




	/**
	 * getConfigArray - queries the DB and generates the global config array
	 *
	 * @return array
	 */

	function getConfigArray() {

	    $retArray = array();

	    // Navigation
	    $query = "SELECT * from cfg_mainmenu";
	    $mainmenutablerows = $this->adodb->fetchArrayDB($query);

	    $ret = array();

	    $menuarray = array();
	    $mainmenuarray = array();

	    while (list ($key, $val) = each ($mainmenutablerows)) {
		$menuname = $val['name'];
		$menulabel = $val['label'];
		$url = "q=overview&m=".$menuname;
		$mainmenuarray[$url] = $menulabel;
	    }

	    $menuarrayxml = $queryconfigarray["navigation"]["mainmenuitem"];
	    $mainmenuarrayxml = $menuarrayxml;
	    $mainmenuarray["trennlinie"] = "trennlinie";
	    $mainmenuarray["q=polldstat&m=main"] = "Polling Daemon Log";
	    $mainmenuarray["q=serverlist&m=main"] = "Change Server";
	    if ($_SESSION["logindata"]["role"] == "admin") $mainmenuarray["admin"] = "Admin";
	    $mainmenuarray["q=logout"] = "Logout";
	    $menuarray["main"] = $mainmenuarray;

	    $query = "SELECT * from cfg_mainmenu";
	    $mainmenutablerows = $this->adodb->fetchArrayDB($query);
	    $query = "SELECT * from cfg_queries";
	    $querytablerows = $this->adodb->fetchArrayDB($query);


	    while (list ($key, $val) = each ($mainmenutablerows)) {
		$menuname = $val['name'];
		$menulabel = $val['label'];
		$submenuarray = array();
		$submenuarray[""] = "<---";
		$query = "SELECT * from cfg_queries where parent='".$menuname."'";
		$querytablerows = $this->adodb->fetchArrayDB($query);
		while (list ($subkey, $submenuitem) = each ($querytablerows)) {
		    $submenuitem_name = $submenuitem['name'];
		    $submenuitem_label = $submenuitem['label'];
		    $url = "q=".$submenuitem_name."&m=".$menuname;
		    $submenuarray[$url] = $submenuitem_label;
		}
		$submenuarray["trennlinie"] = "trennlinie";
		$submenuarray["q=polldstat&m=".$submenu['name']] = "Polling Daemon Log";
		$submenuarray["q=serverlist&m=".$submenu['name']] = "Change Server";
		if ($_SESSION["logindata"]["role"] == "admin") $submenuarray["admin"] = "Admin";
		$submenuarray["q=logout"] = "Logout";
		$menuarray[$menuname] = $submenuarray;
	    }

	    $retArray["menuarray"] = $menuarray;

	    // Admin Backend Menu
	    $adminmenuarray = array();
	    $adminmenuarray["q=config&m=main"] = "General";
	    $adminmenuarray["q=users&m=main"] = "Users";
	    $adminmenuarray["q=groups&m=main"] = "Groups";
	    $adminmenuarray["q=servers&m=main"] = "Servers";
	    $adminmenuarray["q=mainmenu&m=main"] = "Mainmenu";
	    $adminmenuarray["q=queries&m=main"] = "Queries";
	    $adminmenuarray["trennlinie"] = "trennlinie";
	    $adminmenuarray["q=settings&m=main"] = "Settings";
	    $adminmenuarray["trennlinie2"] = "trennlinie";
	    $adminmenuarray["tsmmonitor"] = "TSM Monitor";
	    $adminmenuarray["q=logout"] = "Logout";
	    $retArray["adminmenuarray"] = $adminmenuarray;

	    // Overview Boxes
	    $ret = array();
	    
	    $query = "SELECT * from cfg_overviewboxes order by sortorder asc";
	    $queryoverviewboxes = $this->adodb->fetchArrayDB($query);
	    while (list ($subkey, $box) = each ($queryoverviewboxes)) {
		$query = "SELECT * from cfg_overviewqueries where parent='".$box['name']."' order by sortorder asc";
		$queryoverview = $this->adodb->fetchArrayDB($query);
		$temp = array ();
		//print_r($queryoverview);
		while (list ($subkey, $ovquery) = each ($queryoverview)) {
		    $ovquery['header'] = $queryoverview['name'];
		    $temp[] = (array)$ovquery;
		}
		$ret[$box['name']] = $temp;
	    }
	    $retArray["infoboxarray"] = $ret;

	    // Queries
	    $dbret = array();
	    $query = "SELECT * from cfg_queries";
	    $querytablerows = $this->adodb->fetchArrayDB($query);
	    while (list ($subkey, $queryrow) = each ($querytablerows)) {
		$dbret[$queryrow['name']] = (array)$queryrow;
	    }
	    $retArray["queryarray"] = $dbret;
	    
	    // General settings
	    $query = "SELECT * from cfg_config";
	    $rows = $this->adodb->fetchArrayDB($query);
	    $ret = array();
	    foreach ($rows as $key => $val) {
		$ret[$val['confkey']] = $val['confval'];
	    }
	    $retArray["settings"] = $ret;

	    // Set Stylesheet
	    $query = "SELECT stylesheet from cfg_users where username='".$_SESSION["logindata"]["user"]."'";
	    $row = $this->adodb->fetchArrayDB($query);
	    $retArray["stylesheet"] = $row[0]['stylesheet'];

	    // Colors
	    $query = "SELECT * from cfg_colors";
	    $rows = $this->adodb->fetchArrayDB($query);

	    $ret = array();
	    while (list ($key, $val) = each ($rows)) {
		$ret[$val['name']] = $val['value'];
	    }
	    $retArray["colorsarray"] = $ret;

	    // Servers
	    $query = "SELECT * from cfg_servers";
	    $rows = $this->adodb->fetchArrayDB($query);

	    $ret = array();
	    while (list ($key, $val) = each ($rows)) {
		$ret[$val['servername']] = (array)$val;
		if ($val['default'] == 1) {
		    $retArray["defaultserver"] = $val['servername'];
		}
	    }

	    $retArray["serverlist"] = $ret;
	    return $retArray;
	}

}

?>
