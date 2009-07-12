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

	var $menu;
	var $adminmenu;
    var $menuindent = 0;
	var $message;
	var $adodb;




	/**
	 * TSMMonitor
	 *
	 * @param mixed $adodb
	 * @access public
	 * @return void
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
		$this->GETVars["menu"] = urlencode($_GET['m']);
		$this->GETVars["qq"] = urlencode($_GET['q']);
		$this->GETVars['ob'] = urldecode($_GET['sort']);
		$this->GETVars['orderdir'] = urlencode($_GET['so']);

		if ($_POST['s'] != '') {
			$this->GETVars['server'] = urlencode($_POST['s']);
		} else {
			$this->GETVars['server'] = urlencode($_GET['s']);
		}


		// Session-variables
		$this->configarray = $_SESSION['configarray'];

		// Timeout
		if( !ini_get('safe_mode')) {
			if (ini_get('max_execution_time') != $this->configarray["settings"]["timeout"]) {
				ini_set('max_execution_time', $this->configarray["settings"]["timeout"]);
			}
		}

		// set defaults if vars are empty
		if ($this->GETVars["menu"] == "") { $this->GETVars["menu"]="main"; }
		if ($this->GETVars["qq"] == "") { $this->GETVars["qq"]="index"; }
		if ($this->GETVars['server'] == "") { $this->GETVars['server']=$this->configarray["defaultserver"]; }
		if ($this->GETVars['orderdir'] == "") { $this->GETVars['orderdir'] = "asc"; }

		if ($_SESSION['timeshift'] == '' ||  $this->configarray["queryarray"][$this->GETVars['qq']]["timetablefields"] == "") {
			$_SESSION['timeshift'] = 0 ;
		}

		$this->menu = $this->configarray["menuarray"];
		$this->adminmenu = $this->configarray["adminmenuarray"];
		$this->queryarray = $this->configarray["queryarray"];

		$_SESSION["GETVars"] = $this->GETVars;

		// Cleanup
		if ($_SESSION["from"] != $_GET['q']) {
			$_SESSION['timemachine'] = "";
			$_SESSION['tabletype'] = "";
		}

		// BEGIN Timemachine
		if ($_POST['dateinput'] != "") $_SESSION['timemachine']['date'] = strtotime($_POST['dateinput']);
		if ($_POST['timestamps'] != "") $_SESSION['timemachine']['time'] = $_POST['timestamps'];


		if ($_POST["Poll"] == "Poll Now!") {
			$timestamp = time();
			$tmonpolld = new PollD($this->adodb, $config["server_os"]);
			$tmonpolld->adodb->setDebug($_SESSION["debug"]);
			if ($this->GETVars['qq'] == "index") {
				foreach ($tmonpolld->overviewqueries as $query) {
					$tmonpolld->pollOverviewQuery($query, $tmonpolld->servers[$this->GETVars['server']], $timestamp);
				}
			} else {
				$tmonpolld->pollQuery($tmonpolld->queries[$this->GETVars['qq']], $tmonpolld->servers[$this->GETVars['server']], TRUE, $timestamp);
			}
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
				$_SESSION['stylesheet'] = "style_classic.css";
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
		$this->page = intval($_GET['page']);

		$sql = $this->adodb->sanitizeSQL($sql);

		$recordArray = array();
		$this->adodb->conn->SetFetchMode(ADODB_FETCH_ASSOC);
		$recordSet = $this->adodb->conn->Execute($sql);

		if (($recordSet) || ($this->adodb->conn->ErrorNo() == 0)) {
			$total_rows = $recordSet->RecordCount($recordSet);
            if ($total_rows > 0 ) {
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
            }
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
		$sortcol = urlencode($_GET['sort']);

		$getvars = 'q='.$_GET['q'].'&m='.$_GET['m'].'&s='.$this->GETVars['server'].'&sort='.$sortcol."&so=".$so;
		$self = $_SERVER['PHP_SELF'];
		$navelement = '<a class="tablefooter" href="'.$self.'?'.$getvars.'&page=';

		$fp = "First";
		$lp = "Last";
		$np = ' &gt;&gt;';
		$pp = '&lt;&lt;';

		if($this->page != 1) {
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

		$lines = array ("20", "50", "100", "200", "500");
		$linesel = $_SESSION["lines"][$this->GETVars['qq']];
		if ($linesel == "") $linesel = "20";
		$linebox = "<form action=".$_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&s=".$this->GETVars['server']." method='post'><select name='lpp' size=1 onChange='submit();' class='button'>";

		foreach ($lines as $line) {
			$linebox .= '<option value="'.$line.'"';
			if ($linesel == $line) $linebox.= "SELECTED";
			$linebox .=  '> '.$line.' </option>';
		}
		$linebox .= "</select></form>";

	    $navline = $fp.'&nbsp;'.$pp.'&nbsp;'.$numbers.'&nbsp;'.$np.'&nbsp;'.$lp;

		if ($navline != "") {
			return '<div id="pagecountbox">'.$linebox.'</div>'.$navline.'';
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
		if (isset($r[1]) && $end != "") {
			$r = explode($end, $r[1]);
			return $r[0];
		}
        else {
            return $r[1];
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
		$qq = $this->GETVars['qq'];

		if ($this->queryarray[$qq]["polltype"]=="snapshot") {
			$ret .= "<div class='sidebarinfo'><b>Time Machine</b><br><br><div id='datechooser'>";
			$ret .= "<form name='calform' action='".$_SERVER['PHP_SELF']."?q=".$qq."&m=".$this->GETVars['menu']."&s=".$this->GETVars['server']."' method='post'>";
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
				if ($_SESSION['timemachine']['time'] == $ts['timestamp']) {
					$ret .= " SELECTED ";
				}
				$ret .= "> ".strftime('%H:%M:%S', $ts['timestamp'])."</option>";
			}
			$ret .= "</select>";
			$ret .= "<br>";
			$ret .= "<br>";
			$ret .= "<input type='submit' name='Poll' value='Poll Now!' onclick='submit();' class='button'>";
			$ret .= "</form> </div><br></DIV>";

		} else if ($this->queryarray[$qq]["polltype"]=="update" || $this->queryarray[$qq]["polltype"]=="append" || $qq == "index") {
			$ret .= "<div class='sidebarinfo'><b>Time Machine</b><br><br><div id='datechooser'>";
			$ret .= "<form name='calform' action='".$_SERVER['PHP_SELF']."?q=".$qq."&m=".$this->GETVars['menu']."&s=".$this->GETVars['server']."' method='post'>";
			$ret .= "<br>";
			if ($qq == "index") $qq = "overview";
			$LastTimestamp = $this->getLastSnapshot($qq);
			if ($LastTimestamp!="") $ret .= "Last updated: ".strftime('%Y-%m-%d %H:%M:%S', $LastTimestamp);
			$ret .= "<br>";
			$ret .= "<br>";
			$ret .= "<input type='submit' name='Poll' value='Poll Now!' onclick='submit();' class='button'>";
			$ret .= "</form> </div><br></DIV>";
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
        $this->menuindent++;
        for ($i=1;$i<$this->menuindent;$i++) {
            $indent .= "&nbsp;&nbsp;&nbsp;";
        }
		while (list($key, $val) = each($menu)) {

			$bCont = TRUE;
			$q = $this->GetBetween($key,"q=","&m=");
			$m = $this->GetBetween($key,"&m=","");
            $okey = $key;
			if ($this->configarray["queryarray"][$q]["notforlibclient"] == 1 && $this->configarray["serverlist"][$this->GETVars['server']]["libraryclient"] == 1) {
				$bCont = FALSE;

			}
			$key = $_SERVER['PHP_SELF']."?".$key;

			if ($type == "index") {
				$key .= "&s=".$this->GETVars['server'];
			}

			if ($val['label'] == "Admin") {
				$key = "admin.php";
			}else if ($val['label'] == "TSM Monitor") {
				$key = "index.php";
			}

			if ($val['label'] == "trennlinie") {
				$links .= "<br>\n";
			} else if ($bCont) {

				if (!stristr($key,$activelink)) {
					$links .= "<a href=\"$key\">$indent".$val['label']."</a>\n";
				} else {
					$links .= "<div class=\"aktuell\">$indent".$val['label']."</div>\n";
				}
			}
            if ($this->GETVars['menu'] == $m) {
                $links .= $this->getMenu($menu[$okey]['sub'], $activelink, $type);
            }
		}
        $this->menuindent--;
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
	 * getStylesheetSwitcher - returns HTML Code for Stylesheetswitchdropdownbox ;)
	 *
	 * @return string
	 */
	function getStylesheetSwitcher() {

		$ret = "";
		$ret .= "<div class='sidebarinfo'>";
		$ret .= "<b>Stylesheet Switcher</b><br><br>";
		$ret .= "<form action=".$_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&s=".$this->GETVars['server']." method='post'>\n";
		$ret .= "<select name='css' size=1 onChange='submit();' class='button'>\n";
		if ($handle = opendir('css')) {
			while (false !== ($file = readdir($handle))) {
				if ($file != '.' && $file != '..' && substr($file, 0, 6) == 'style_') {
					$fileName = str_replace('.css', '', $file);
					$fileName = str_replace('style_', '', $fileName);
					$ret .=  '<option value="' . $file . '"';
					if ($_SESSION['stylesheet'] == $file) $ret .= "SELECTED";
					$ret .=  '>' . $fileName . '</option>';
				}
			}
			closedir($handle);
		}
		$ret .= "</select>\n";
		$ret .= "</form>\n";
		$ret .= "</div>";

		return $ret;

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
					if (($this->GETVars['ob'] == $name && $this->GETVars['ob'] != "") || ($this->GETVars['ob'] == "" && $orderby != "" && $orderby == $name)) {
						$link = "href='".$_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&sort=".(urlencode($name))."&page=".$this->page."&so=".$sonew."&s=".$this->GETVars['server']."'";
						if ($orderdir == "asc") {
							$arrow = "&uarr;";
						} else if ($orderdir == "desc") {
							$arrow = "&darr;";
						}
					} else {
						$arrow = "";
						$link = "href='".$_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&sort=".(urlencode($name))."&page=".$this->page."&s=".$this->GETVars['server']."'";
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
			$link = "href='".$_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&sort=".(urlencode($name))."&page=".$this->page."&so=".$sonew."'";
			$label = $fieldnames[0]['Field'];
			$tableheader = $tableheader."<th><a class='navhead' ".$link.">".$label." ".$arrow."</a></th>";
		}
		if ($isAdmin) {
			$tableheader = $tableheader."<th colspan=2><a class='navhead'></a></th>";
		}
		$tableheader=$tableheader."</tr>";
		return array("numfields" => sizeof($fieldnames), "header" => "$tableheader");
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
	 * @param string qq name of query
	 * @return string
	 */
	function getLastSnapshot($qq) {

		$server = $this->GETVars['server'];
		$ret = array();

		$sql = "SELECT MAX(TimeStamp) from res_".$qq."_".$server;
		$ret = $this->adodb->fetchArrayDB($sql);
		$ret = (array)$ret[0];

		return $ret["MAX(TimeStamp)"];

	}


	/**
	 * getTableFields
	 *
	 * @param string $tablename
	 * @access public
	 * @return void
	 */
	function getTableFields($tablename="") {
		$sqltf = "SHOW COLUMNS FROM ".$tablename;
		$sqlrestf = $this->adodb->fetchArrayDB($sqltf);
		$fieldnames = array();

		// get all table fields to be selected
        foreach ($sqlrestf as $field) {
            if ($field['Field'] != "timestamp") {
                $tmp_field = "`".$field['Field']."`";
                array_push($fieldnames, $tmp_field);
			}
		}
		return implode(",", $fieldnames);
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
				$modrow = array();
				$widths = array();
				while (list($keycell, $valcell) = each($row)) {
					if ($keycell == "id") {
						$id = $valcell;
                    } else if ($valcell == "version") {
                        continue 2;
					} else {
						$modrow[$keycell] = $valcell;
					}
					$widths[$keycell] = "";
				}

				$baseurl = $_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu'];
				$modrow["edit"] = "<a href='".$baseurl."&id=".$id."&action=edit' onclick=''><img src='images/edit.png' border=0></img></a>";
				$widths["edit"] = " width='20px' ";
				$modrow["del"] = "<a href='#' onclick='show_confirm(\"".$baseurl."\", $id, \"delete\")'><img src='images/delete.png' border=0 ></img></a>";
				$widths["del"] = " width='20px' ";

				$outp .= $this->renderZebraTableRow($modrow, $i%2, "", "", $widths);
				$i++;
			} else {
				$outp = $this->adodb->fetchArrayDB($sql);
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
		$stop = FALSE;
		$tablearray = array();
		$bContinue = TRUE;
        $num_cols = array('Version', 'OS Level', 'Total GB', 'Pct of total files', 'GB', 'TX/GB', 'ELAPTIME (D HHMMSS)', 'MB', 'MB/s', 'GB sent', 'GB rcvd', 'Capacity (MB)', 'Usage (Pct)', 'Pct util', 'Pct mig', 'Pct reclaim', 'Write Err', 'Read Err', 'GB transfered');

		if ($this->GETVars['ob'] != '' ) {
            if (in_array($this->GETVars['ob'], $num_cols)) $num_order = " + 0";
			$sqlappend = " order by (`".$this->GETVars['ob']."`".$num_order.") ".$this->GETVars['orderdir'];
		} elseif ($this->configarray["queryarray"][$this->GETVars['qq']]["orderby"] != '') {
            if (in_array($this->configarray["queryarray"][$this->GETVars['qq']]["orderby"], $num_cols)) $num_order = " + 0";
			$sqlappend = " order by (`".$this->configarray["queryarray"][$this->GETVars['qq']]["orderby"]."`".$num_order.") ".$this->GETVars['orderdir'];
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

		if ($type == "timetable" || $type == "timetable2") {
			$columnnames = $this->configarray["queryarray"][$this->GETVars['qq']]["timetablefields"];
		} else {
			$columnnames = $this->getTableFields("res_".$qtable."_".$server);
		}

		if ($columnnames == "") $bContinue = FALSE;

		// execute the constructed query
		$sql = "SELECT ".$columnnames." from res_".$qtable."_".$server.$timestampquery.$wc.$sqlappend;

		$_SESSION["lastsql"] = $sql;
		if ($sqlres) $this->message = $sql;

        if ($_SESSION["lines"][$this->GETVars['qq']] != "") {
            $lpp = $_SESSION["lines"][$this->GETVars['qq']];
        } else {
            $lpp = 20;
        }

		if ($bContinue) {
			if ($type == "table") {
				$i = 0;
				$rs = $this->fetchSplitArrayDB($sql,$lpp);

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
					}
					$outp .= $this->renderZebraTableRow($row, $i%2, $col, $color, "");
					$i++;
				}
			} else if ($type == "verticaltable") {
				$outp = $this->adodb->fetchArrayDB($sql);
			} else if ($type == "timetable") {
				$sqlres = $this->adodb->fetchArrayDB($sql);
				$outp = array();
				foreach ($sqlres as $row) {
					$rowarray2 = array();
					while (list($keycell, $valcell) = each($row)) {
						if ($keycell == "Start Time" || $keycell == "Actual Start" || $keycell == "End Time") {
							$date = $row[$keycell];
							$rowarray2[] = mktime(substr($date,11,2),substr($date,14,2),substr($date,17,2),substr($date,5,2),substr($date,8,2),substr($date,0,4));
						} else {
							$rowarray2[] = $valcell;
						}
					}
					array_push($outp, $rowarray2);
				}
			} else if ($type == "timetable2") {
                $sql = ereg_replace(" order by .*", " order by `Node Name`, (`Start Time`)", $sql);
				$sqlres = $this->adodb->fetchArrayDB($sql);
				$outp = array();
				foreach ($sqlres as $row) {
					$rowarray2 = array();
					while (list($keycell, $valcell) = each($row)) {
						if ($keycell == "Start Time" || $keycell == "Actual Start" || $keycell == "End Time") {
							$date = $row[$keycell];
							$rowarray2[] = mktime(substr($date,11,2),substr($date,14,2),substr($date,17,2),substr($date,5,2),substr($date,8,2),substr($date,0,4));
                        } else if ($keycell == "Node Name") {
                            $nodename = $valcell;
                            if ($outp[$nodename] == "") {
                                $outp[$nodename] = array();
                            }
						} else {
							$rowarray2[] = $valcell;
						}
					}
					array_push($outp[$nodename], $rowarray2);
				}
			}
		}

		return $outp;
	}


	/**
	 * renderZebraTableRow - returns HTML code for one zebra tabel row
	 *
	 * @param mixed $row array of fields
	 * @param mixed $shade 1 or 0
	 * @param mixed $alarmcol column which should be colored in alarm color
	 * @param mixed $color alarm color
	 * @param mixed $cellproperties array of cell properties (optional)
	 * @access public
	 * @return void
	 */
	function renderZebraTableRow ($row, $shade , $alarmcol, $color, $cellproperties) {

		$isNotEmpty = FALSE;
		$colorsarray = $this->configarray["colorsarray"];
		$outp = $outp."<tr class='d".$shade."'>";

		while (list($keycell, $valcell) = each($row)) {

			if (isset($cellproperties) && $cellproperties[$keycell] != "") {
				$cellproperty = " ".$cellproperties[$keycell]." ";
			} else {
				$cellproperty = "";
			}
			if($color!="" && $alarmcol==$keycell) {

				if ($shade == 0) {
					$cellcol = $colorsarray[$color."_light"];
				} else {
					$cellcol = $colorsarray[$color."_dark"];
				}
				$outp = $outp."<td ".$cellproperty."bgcolor='".$cellcol."'>".$valcell."</td>";
			} else {
				$outp = $outp."<td".$cellproperty.">".$valcell."</td>";
			}
			if ($valcell != "") $isNotEmpty = TRUE;
		}
		$outp = $outp."</tr>\n";

		if ($isNotEmpty) {
			return $outp;
		} else {
			return "";
		}
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

		$operators = array ("<", "=", "<>", ">", "LIKE");
        $searcharr = $_SESSION["search"][$this->GETVars['qq']];
        if ($_POST["wcfield"] != "") {
            $arrfield = $_POST["wcfield"];
        } else if (isset($searcharr)) {
            $arrfield = $searcharr["field"];
        }
        if ($_POST["wcval"] != "") {
            $arrval = $_POST["wcval"];
        } else if (isset($searcharr)) {
            $arrval = $searcharr["val"];
        }
        if ($_POST["wcop"] != "") {
            $arrop = $_POST["wcop"];
        } else if (isset($searcharr)) {
            $arrop = $searcharr["op"];
        }
		$sql = "SHOW COLUMNS FROM res_".$this->configarray["queryarray"][$this->GETVars['qq']]["name"]."_".$this->GETVars['server'];
		$fieldnames = $this->adodb->fetchArrayDB($sql);

		// Build Field Name Combobox
		$fieldbox = "<select name='wcfield' size=1 onChange='' class='button topnavbutton'>";
		foreach ($fieldnames as $field) {
			if ($field['Field'] != "timestamp") {
				$fieldbox.= '<option value="'.$field['Field'].'"';
				if ($arrfield == $field['Field']) $fieldbox.= "SELECTED";
				$fieldbox.=  '> '.$field['Field'].' </option>';
			}
		}
		$fieldbox.= "</select>";

		// Build Operator Combobox
		if ($arrop == "") $arrop="=";
		$opbox = "<select name='wcop' size=1 onChange='' class='button topnavbutton'>";
		foreach ($operators as $op) {
			$opbox.= '<option value="'.$op.'"';
			if ($arrop == $op) $opbox.= "SELECTED";
			$opbox.=  '> '.$op.' </option>';
		}
		$opbox.= "</select>";

		$link = $_SERVER['PHP_SELF']."?q=".$this->GETVars['qq']."&m=".$this->GETVars['menu']."&s=".$this->GETVars['server'];
		$ret .= "<form action=".$link." method='post'>";
		$ret .= $fieldbox;
		$ret .= $opbox;
		$ret .= "<input name='wcval' type='text' size='15' maxlength='60' class='button topnavtextfield' value='".$arrval."'>  ";
		$ret .= "<input type='submit' name='Search' value='Search' onclick='submit();' class='button topnavbutton'>";
		$ret .= "<input type='submit' name='Clear' value='Clear' onclick='submit();' class='button topnavbutton'>";
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
		while (list($servername,$serveritems) = each($serverlist)) {
			$listip = $serveritems["ip"];
			$listdescription = $serveritems["description"];
			$listport = $serveritems["port"];
			$listlink = $_SERVER['PHP_SELF']."?q=".$_SESSION["from"]."&m=".$this->GETVars['menu']."&s=".(urlencode($servername));
			$row = array("<a class='nav' href='".$listlink."'>".$servername."</a>", $listdescription, $listip, $listport);
			$ret .= $this->renderZebraTableRow($row, $i%2, "", "", "");
			$i++;
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
        if ($_SESSION["lines"][$this->GETVars['qq']] != "") {
            $lpp = $_SESSION["lines"][$this->GETVars['qq']];
        } else {
            $lpp = 20;
        }

		$rs = $this->fetchSplitArrayDB($sql,$lpp);
		foreach ($rs as $row) {
			$modrow = array();
			while (list($keycell, $valcell) = each($row)) {
				if ($keycell == "timestamp") {
					$valcell = strftime("%Y/%m/%d %T", $valcell);
				}
				$modrow[$keycell] = $valcell;
			}
			$outp .= $this->renderZebraTableRow($modrow, $i%2, "", "", "");
			$i++;
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

		while (list($key, $val) = each($subindexqueryarray)) {

			$comperator = "";
			$alertval = "";
			$alertcol = "";

			if ($this->configarray["serverlist"][$this->GETVars['server']]["libraryclient"] == 1 && $subindexqueryarray[$key]["notforlibclient"] == 1) {
				$sqlres = array();
				$sqlres[0]["name"] = "-";
				$sqlres[0]["result"] = "-";
				$error = FALSE;
			} else {
				$sql = "SELECT name, result from res_overview_".$this->GETVars['server']." where name='".$subindexqueryarray[$key]["name"]."'";
				$sqlres = $this->adodb->fetchArrayDB($sql);
                if (empty($sqlres[0]["result"])) $sqlres[0]["result"] = 0;
				$comperator = $subindexqueryarray[$key]["alert_comp"];
				$alertval = $subindexqueryarray[$key]["alert_val"];
				$alertcol = $subindexqueryarray[$key]["alert_col"];
				$unit = $subindexqueryarray[$key]["unit"];
				$error = $this->checkAlert($comperator, $alertval, $sqlres[0]["result"]);
			}

			if ($error) {
				$errorcolor = $alertcol;
			} else {
				$errorcolor = "ok";
			}

			$cellprop = array();
			$cellprop["name"] = "width='50%'";
			$cellprop["result"] = "align='center'";

			$res = array();
			$res["name"] = $sqlres[0]["name"];
			$res["result"] = $sqlres[0]["result"]." ".$unit;

			$out .= $this->renderZebraTableRow($res, $i%2, "result", $errorcolor, $cellprop, "" );
			$i++;
		}

		return $out;

	}




	/**
	 * generateTimetableHeader - returns HTML code for timetable header (display of hours)
	 *
	 * @param string $startpunkt I forgot that one
	 * @param string $FirstCol first field of result table
     * @param integer $pxperHour Width of a on hour field in pixels
	 * @return string
	 */
	function generateTimetableHeader($startpunkt = '', $FirstCol = '', $pxperHour = 30) {

		$header = $FirstCol["label"];
		$out= "<tr align='left'><th>".$header."</th><th>";
		for ($count = 0; $count <= 24; $count++) {
			$imagename = strftime("%H", $startpunkt + ($count * 3600));
			$out .= "<img src='images/".$imagename.".gif' height=20px width=".$pxperHour."px title='".strftime("%H:00 (%D)", $startpunkt + ($count * 3600))."' />";
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
		while (list($label,$value) = each($timesteps)) {
			$out .= '<option value="'.$value.'"';
			if ($_SESSION['selectedtimestep'] == $value) $out .=  "SELECTED";
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
		$factor = 120;
		$oneday = 86400;
		$onehour = 3600;
		$tolerance = 1200;

		$this->timetablestarttime = 24 + $_SESSION['timeshift'];

		$startpunkt = ((ceil($now / $onehour) * $onehour) - $onehour - $oneday) - (($this->timetablestarttime - 24) * $onehour);
		$endpunkt = $startpunkt + $oneday + $onehour;
		$lastpoint = ($endpunkt - $startpunkt) / $factor;

		$out .= "<table class='timetable' width='".$lastpoint."'>";
		$out .= $this->generateTimetableNavigation();
		$out .= $this->generateTimetableHeader($startpunkt, $FirstCol);
		$out .= "</td></tr>";

		$lasttimepoint = $now - ($this->timetablestarttime * $onehour) - $tolerance;

		$repeatingcol = "";
		$ii = 1;

		while (list($keyrow, $valrow) = each($tablearray)) {
			if ($valrow[1] <= $endpunkt && $valrow[2] > $lasttimepoint) {
				$name = $valrow[0];
				$status = $valrow[3];
				$statusmsg = "";
				$dur = strftime("%H:%M", ($valrow[2] - $valrow[1]) - $onehour);
				$shade = "";
				if ($valrow[1] < $lasttimepoint) {
					// cut the bar at the left side to fit into table
					$start = 0;
				} else {
					$start = ($valrow[1] - $startpunkt) / $factor;
				}
				$end = ($valrow[2] - $startpunkt) / $factor;
				$duration = $end - $start;
				// fake a longer time for better visibility
				if ($duration < 2) {
                    $duration = 2;
                }
				// cut the bar at the right side to fit into table
				if (($start + $duration) > $lastpoint) {
					$duration = $lastpoint - $start;
					$shade = "light";
				}
				if ($valrow[1] < $lasttimepoint) {
					$shade = "light";
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
				if ($status != 'Missed') {
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
     * generateTimetable2 - generates HTML code for graphical timetables (version 2)
     *
     * @param array $tablearray Array containing an SQL query result
     * @param string $FirstCol first field of result table
     * @return string
     */
    function generateTimetable2($tablearray = '', $FirstCol = '') {

        $now = time();
        $out = '';
        $height = 8;
        $pxperhour = 30;
        $oneday = 86400;
        $onehour = 3600;
        $resolution = ($onehour / $pxperhour);
        $tolerance = 120;

        $this->timetablestarttime = 24 + $_SESSION['timeshift'];
        $startpoint = ((ceil($now / $onehour) * $onehour) - $onehour - $oneday) - (($this->timetablestarttime - 24) * $onehour);
        $endpoint = $startpoint + $oneday + $onehour;
        $lastpoint = ($oneday + $onehour) / $resolution;

        $out .= "<table class='timetable' width='".$lastpoint."'>";
        $out .= $this->generateTimetableNavigation();
        $out .= $this->generateTimetableHeader($startpoint, $FirstCol, $pxperhour);
        $out .= "</td></tr>";

        $lasttimepoint = $now - ($this->timetablestarttime * $onehour) - $tolerance;
        $lasttimepoint = $startpoint - $tolerance;

        $ii = 1;

        // every node with events
        while (list($nodename, $keyrow) = each($tablearray)) {
            $dummy = array("", "", "dummy");
            array_push($keyrow, $dummy);
            end($keyrow);
            $last = key($keyrow);
            reset($keyrow);

            if ($ii == 1) {
                $out .= "<tr class='d0' width=".$lastpoint.">";
            } else {
                $out .= "<tr class='d1' width=".$lastpoint.">";
                $ii = 0;
            }
            $out .= "<td style='color:#000000;'>".$nodename."</td>";
            $out .= "<td class='content'>";
 
            $egroup = array();
            $ebegin = 0;    # event begin time
            $eend = 0;      # event end time
            $gbegin = 0;    # group begin time
            $gend = 0;      # group end time
            $pend = 0;      # previous event end time
            $pendround = 0; # previous event end time rounded to the next pixel
            $line = "";
            $lstartpx = 0;
            $ldurpx = 0;
            // every event for the current backup
            while (list($key, $valrow) = each($keyrow)) {
                $ebegin = $valrow[0];
                $eend = $valrow[1];
                // event within display range
                if (($ebegin <= $endpoint && $eend > $lasttimepoint) || ($valrow[2] == "dummy")) {
                    if ($pend != 0) $pendround = (ceil($pend / $resolution) * $resolution);
                    if ((floor($ebegin / $resolution) * $resolution) > $pendround || $key == $last || $valrow[2] == "dummy") {
                        if (empty($egroup)) {
                            array_push($egroup, $valrow);
                            $gbegin = $ebegin;
                            $gend = $eend;
                            $pend = $eend;
                            $lstartpx = $startpx;
                            $ldurpx = $durpx;
                            if (count($keyrow) != 1) continue;
                        }
                        $gstatus = array();

                        // cut the bar at the left side to fit into table
                        if ($gbegin < $lasttimepoint) {
                            $startpx = 0;
                            $gshade = "light";
                        } else {
                            $startpx = ceil(($gbegin - $startpoint) / $resolution);
                            $gshade = "";
                        }

                        // cut the bar at the right side to fit into table
                        $endpx = ceil(($gend - $startpoint) / $resolution);
                        $durpx = ($endpx - $startpx);
                        if ($durpx == 0) $durpx = 1;

                        if (($startpx + $durpx) > $lastpoint) {
                            $durpx = ceil($lastpoint - $startpx);
                            $gshade = "light";
                        } else {
                            $gshade = "";
                        }
                        $barcol = $gshade."green";

                        while (list($gkey, $gvalrow) = each($egroup)) {
                            $cestatus = $gvalrow[2];
                            $cedur = strftime("%H:%M:%S", ($gvalrow[1] - $gvalrow[0]) - $onehour);
                            if (isset($cestatus)) {
                                if ($cestatus == "YES" || $cestatus == "Completed") {
                                    array_push($gstatus, $cedur."h, Status was OK");
                                } else {
                                    $barcol = $gshade."red";
                                    array_push($gstatus, $cedur."h, Status was UNSUCCESSFUL");
                                }
                            } else {
                                $barcol = $gshade."grey";
                                array_push($gstatus, "");
                            }
                        }

                        if ($line == "") {
                            $line .= "<img src='images/trans.gif' height=1px width=".$startpx."px />";
                        } else {
                            $line .= "<img src='images/trans.gif' height=1px width=".($startpx - $lstartpx - $ldurpx)."px />";
                        }
                        $line .= "<img src='images/".$barcol.".gif' height=".$height."px width=".$durpx."px title='".$nodename." ".strftime("%H:%M:%S", $gbegin)." - ".strftime("%H:%M:%S", $gend)." (".(join('; ', $gstatus)).")' />";

                        // cleanup for next event
                        unset($egroup);
                        $egroup = array();
                        array_push($egroup, $valrow);
                        $gbegin = $ebegin;
                        $gend = $eend;
                    } else if ((floor($ebegin / $resolution) * $resolution) <= $pendround) {
                        array_push($egroup, $valrow);
                        if ($ebegin < $gbegin) $gbegin = $ebegin;
                        if ($eend > $gend) $gend = $eend;
                    }
                    $pend = $eend;
                    $lstartpx = $startpx;
                    $ldurpx = $durpx;
                }
            }
            $out .= $line;
            $out .= "</td></tr>\n";
            $ii++;
            unset($egroup);
            unset($line);
        }
        $out .= $this->generateTimetableHeader($startpoint);
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

		$mainmenuarray["q=index&m=main"]['label'] = "Overview";
		while (list ($key, $val) = each ($mainmenutablerows)) {
			$menuname = $val['name'];
			$menulabel = $val['label'];
			$url = "q=overview&m=".$menuname;
			$mainmenuarray[$url]['label'] = $menulabel;
		}

		$menuarrayxml = $queryconfigarray["navigation"]["mainmenuitem"];
		$mainmenuarrayxml = $menuarrayxml;
		$mainmenuarray["trennlinie"]['label'] = "trennlinie";
		$mainmenuarray["q=polldstat&m=main"]['label'] = "Polling Daemon Log";
		$mainmenuarray["q=serverlist&m=main"]['label'] = "Change Server";
		if ($_SESSION["logindata"]["role"] == "admin") $mainmenuarray["admin"]['label'] = "Admin";
		$mainmenuarray["q=logout"]['label'] = "Logout";
		$menuarray["main"] = $mainmenuarray;

		$query = "SELECT * from cfg_mainmenu";
		$mainmenutablerows = $this->adodb->fetchArrayDB($query);
		$query = "SELECT * from cfg_queries";
		$querytablerows = $this->adodb->fetchArrayDB($query);


		while (list ($key, $val) = each ($mainmenutablerows)) {
			$menuname = $val['name'];
			$menulabel = $val['label'];
			$submenuarray = array();
			$query = "SELECT * from cfg_queries where parent='".$menuname."'";
			$querytablerows = $this->adodb->fetchArrayDB($query);
			while (list ($subkey, $submenuitem) = each ($querytablerows)) {
				$submenuitem_name = $submenuitem['name'];
				$submenuitem_label = $submenuitem['label'];
				$url = "q=".$submenuitem_name."&m=".$menuname;
				$submenuarray[$url]['label'] = $submenuitem_label;
			}
			$menuarray['main']["q=overview&m=".$menuname]['sub'] = $submenuarray;
		}

		$retArray["menuarray"] = $menuarray;

		// Admin Backend Menu
		$adminmenuarray = array();
		$adminmenuarray["q=config&m=main"]['label'] = "General";
		$adminmenuarray["q=users&m=main"]['label'] = "Users";
		$adminmenuarray["q=groups&m=main"]['label'] = "Groups";
		$adminmenuarray["q=servers&m=main"]['label'] = "Servers";
		$adminmenuarray["q=mainmenu&m=main"]['label'] = "Mainmenu";
		$adminmenuarray["q=queries&m=main"]['label'] = "Queries";
		$adminmenuarray["trennlinie"]['label'] = "trennlinie";
		$adminmenuarray["q=settings&m=main"]['label'] = "Settings";
		$adminmenuarray["trennlinie2"]['label'] = "trennlinie";
		$adminmenuarray["tsmmonitor"]['label'] = "TSM Monitor";
		$adminmenuarray["q=logout"]['label'] = "Logout";
		$retArray["adminmenuarray"] = $adminmenuarray;

		// Overview Boxes
		$ret = array();

		$query = "SELECT * from cfg_overviewboxes order by sortorder asc";
		$queryoverviewboxes = $this->adodb->fetchArrayDB($query);
		while (list ($subkey, $box) = each ($queryoverviewboxes)) {
			$query = "SELECT * from cfg_overviewqueries where parent='".$box['name']."' order by sortorder asc";
			$queryoverview = $this->adodb->fetchArrayDB($query);
			$temp = array ();
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
            if ($retArray["defaultserver"] == "") {
                $retArray["defaultserver"] = $val['servername'];
            }
			if ($val['default'] == 1) {
				$retArray["defaultserver"] = $val['servername'];
			}
		}

		$retArray["serverlist"] = $ret;
		return $retArray;
	}

}

?>
