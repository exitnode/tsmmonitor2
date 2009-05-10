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
 * functions.php, TSM Monitor
 * 
 * This file includes all general functions for TSM Monitor
 * 
 * @author Michael Clemens
 * @package tsmmonitor
 */

/**
 * initialize - This function us called every time index.php is refreshed
 *
 */

function initialize() {

	global $configarray;
	global $queryarray;
	global $GETVars;
	global $page;

	global $submenu;
	global $adminmenu;
	//global $query;
	global $whereclause;
	global $message;
	global $db_type, $db_name, $db_user, $db_password, $db_host, $db_charset, $db_collate, $conn;

	session_name("tsmmonitordev");
	session_start();


	// Login if not logged in and credentials are ok
	if ((isset($_POST) && isset($_POST["loginname"]) && isset($_POST["loginpasswort"]) && (!isset($_SESSION["logindata"])))) {
		$_SESSION["logindata"]["user"] = $_POST["loginname"];
		$_SESSION["logindata"]["pass"] = $_POST["loginpasswort"];
                checkLogin();
	}

	if ($_GET['q'] == "logout") {
		unset($_SESSION["logindata"]);
	}

//	if (!isset($_SESSION) || !isset($_SESSION['configarray'])) {
		$_SESSION['configarray'] = getConfigArray();
//	}

	// GET-variables
	$GETVars["menu"] = $_GET['m'];
	$GETVars["qq"] = $_GET['q'];
	$GETVars['ob'] = $_GET['sort'];
	$GETVars['orderdir'] = $_GET['so'];

	if ($_POST['s'] != '') {
		$GETVars['server'] = $_POST['s'];
	} else {
		$GETVars['server'] = $_GET['s'];
	}


	// Session-variables
	$configarray = $_SESSION['configarray'];

	// timeout
	if( !ini_get('safe_mode') && ini_get('max_execution_time') != $configarray["settings"]["timeout"]) {
		ini_set('max_execution_time', $configarray["settings"]["timeout"]);
	}

	// set defaults if vars are empty
	if ($GETVars["menu"] == "") { $GETVars["menu"]="main"; }
	if ($GETVars["qq"] == "") { $GETVars["qq"]="index"; }
	if ($GETVars['server'] == "") { $GETVars['server']=$configarray["defaultserver"]; }
	if ($GETVars['orderdir'] == "") { $GETVars['orderdir'] = "asc"; }

	if ($_SESSION['timeshift'] == '' ||  !strstr($GETVars["qq"], 'dynamictimetable')) {
		$_SESSION['timeshift'] = 0 ;
	}

	$submenu = $configarray["menuarray"][$GETVars['menu']];
	$adminmenu = $configarray["adminmenuarray"];
	//$query = $configarray["queryarray"][$GETVars['qq']]["tsmquery"];
	$queryarray = $configarray["queryarray"];

	$_SESSION["GETVars"] = $GETVars;


	// BEGIN Timemachine
	if ($_SESSION["from"] != $_GET['q']) {
		$_SESSION['timemachine'] = "";	
	}

	if ($_POST['dateinput'] != "") $_SESSION['timemachine']['date'] = strtotime($_POST['dateinput']);
	if ($_POST['timestamps'] != "") $_SESSION['timemachine']['time'] = $_POST['timestamps'];


	if ($_POST["Poll"] == "Poll Now!") {
		$timestamp = time();
		$tmonpolld = new PollD();
		$tmonpolld->setDBParams($db_host, $db_name, $db_user, $db_password);
		$tmonpolld->initialize();
		$tmonpolld->pollQuery($tmonpolld->queries[$GETVars['qq']], $tmonpolld->servers[$GETVars['server']], TRUE, $timestamp);
		$_SESSION['timemachine']['date'] = $timestamp;
		$_SESSION['timemachine']['time'] = $timestamp;
	}


        if (($_POST['Poll'] == "Poll Now!" || $_SESSION['timemachine']['date'] == "") && $queryarray[$GETVars['qq']]["polltype"]=="snapshot") {
                $qtable = $configarray["queryarray"][$GETVars['qq']]["name"];
                $sql = "SELECT MAX(TimeStamp) FROM res_".$qtable."_".$GETVars["server"];
                $res = fetchArrayDB($sql, $conn);
                $resarr = (array)$res[0];
                $_SESSION['timemachine']['date'] = $resarr["MAX(TimeStamp)"];
                $_SESSION['timemachine']['time'] = $resarr["MAX(TimeStamp)"];
        }


	// Custom Stylesheet
	if ($_SESSION['stylesheet'] == "") {
	if ($configarray['stylesheet'] != "") {
		$_SESSION['stylesheet'] = $configarray['stylesheet'];
	} else {
		$_SESSION['stylesheet'] = "default.css";
	}
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

	//$page = $_GET["page"];
	global $max_pages;
	global $GETVars;

	$page = intval($_GET['page']);
	$so = $_GET['so'];
	$sortcol = $_GET['sort'];

	$getvars = 'q='.$_GET['q'].'&m='.$_GET['m'].'&s='.$GETVars['server'].'&sort='.$sortcol."&so=".$so;
	$self = htmlspecialchars($_SERVER['PHP_SELF']);
	$navelement = '<a class="tablefooter" href="'.$self.'?'.$getvars.'&page=';

	$fp = "First";
	$lp = "Last";
	$np = ' &gt;&gt;';
	$pp = '&lt;&lt;';

	if($page != 1) {
		//$fp =  '<a class="tablefooter" href="'.$self.'?'.$urlappend.'&page=1">'.$fp.'</a>';
		$fp =  $navelement.'1">'.$fp.'</a>';
	}

	if($page != $max_pages) {
		$lp = $navelement.($max_pages).'">'.$lp.'</a>';
	}

	if($page > 1) {
		$pp = $navelement.($page-1).'">'.$pp.'</a>';
	}

	if($page < $max_pages) {
		$np = $navelement.($page+1).'">'.$np.'</a>';
	}

	// Numbers
	for($i=1;$i<=$max_pages;$i+=$links_per_page) {
		if($page >= $i) {
			$start = $i;
		}
	}

	if($max_pages > $links_per_page) {
		$end = $start+$links_per_page;
		if($end > $max_pages) $end = $max_pages+1;
	}
	else {
		$end = $max_pages;
	}

	$numbers = '';

	for( $i=$start ; $i<=$end ; $i++) {
		if($i == $page) {
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

        global $GETVars;
        global $configarray;
	$queryarray = $configarray["queryarray"];

	$ret = "";

	if ($queryarray[$GETVars['qq']]["polltype"]=="snapshot") {
                $ret .= "<div class='sidebarinfo'><b>Time Machine</b><br><br><div id='datechooser'>";
                $ret .= "<form name='calform' action='".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']."' method='post'>";
                $ret .= "<input id='dateinput' class='textfield' name='dateinput' type='text' style='width: 100px' value='".strftime("%Y/%m/%d", $_SESSION['timemachine']['date'])."'>";
		$ret .= "<br>";
		$ret .=  "<select name='timestamps' class='button' size=1 style='width: 103px' onchange='submit()'>";
		if (sizeof(getTimestampsOfADay($_SESSION['timemachine']['date'])) > 0) {
			$ret .= "<option value='Select Time'> Select Time </option>";
		} else {
			$ret .= "<option value='---------'>---------</option>";
		}
		foreach (getTimestampsOfADay($_SESSION['timemachine']['date']) as $ts) {
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

	} else if ($queryarray[$GETVars['qq']]["polltype"]=="update" || $queryarray[$GETVars['qq']]["polltype"]=="append") {
		$LastTimestamp = GetLastSnapshot();
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

	global $GETVars;
	global $configarray;

	if (!isset($menu)) { return ""; };
	while(list($key, $val) = each($menu)) {

		$bCont = TRUE;
		$q = GetBetween($key,"q=","&m=");
		if ($configarray["queryarray"][$q]["notforlibclient"] == 1 && $configarray["serverlist"][$GETVars['server']]["libraryclient"] == 1) {
			$bCont = FALSE;
			
		}
		$key = $_SERVER['PHP_SELF']."?".$key;

		if ($type == "index") {
			$key .= "&s=".$GETVars['server'];
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

	global $message;

	return "<div class='sidebarinfo'><b>System Message:</b><br><br>".$message."</div>";
}




/**
 * getInfo - returns HTML Code for Infoboxes with information about current query
 *
 * @return string
 */

function getInfo() {

	global $configarray;
	global $GETVars;

	$label = $configarray["queryarray"][$GETVars['qq']]["label"];
	$info = $configarray["queryarray"][$GETVars['qq']]["info"];

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

    global $GETVars;
    global $configarray;
    global $timetablestarttime;
    global $conn;

    $tableheader="<tr>";
    $orderby = $configarray["queryarray"][$GETVars['qq']]["orderby"];
    $orderdir = $GETVars['orderdir'];
    $page = $_GET['page'];

    if ($orderdir == "asc") {
        $sonew="desc";
    } else if ($orderdir == "desc") {
        $sonew="asc";
    }

    $isAdmin = strstr($_SERVER['PHP_SELF'], 'admin.php');

    if ($isAdmin) {
        $sql = "SHOW COLUMNS FROM cfg_".$_GET['q'];
    } else {
        $sql = "SHOW COLUMNS FROM res_".$configarray["queryarray"][$GETVars['qq']]["name"]."_".$GETVars['server'];
    }
    $fieldnames = fetchArrayDB($sql, $conn);

    // If table has more than one column
    if (sizeof($fieldnames) > 1) {
        foreach ($fieldnames as $col) {
            if ($col['Field'] != "timestamp" && $col['Field'] != "id") {
                $name = $col['Field'];
                $arrow = "";
                if (($GETVars['ob'] == $name && $GETVars['ob']!="") || ($GETVars['ob']=="" && $orderby!="" && $orderby == $name)) {
                    $link = "href='".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']."&sort=".$name."&page=".$page."&so=".$sonew."&s=".$GETVars['server']."'";
                    if ($orderdir == "asc") {
                        $arrow = "&uArr;";
                    } else if ($orderdir == "desc") {
                        $arrow = "&dArr;";
                    }
                } else {
                    $arrow = "";
                    $link = "href='".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']."&sort=".$name."&page=".$page."&s=".$GETVars['server']."'";
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
        $link = "href='".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']."&sort=".$name."&page=".$page."&so=".$sonew."'";
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

	global $configarray;
	global $GETVars;
    global $conn;
	$user = $_SESSION["logindata"]["user"];
	$pass = $_SESSION["logindata"]["pass"];
	$wc = "";

	$isAdmin = strstr($_SERVER['PHP_SELF'], 'admin.php');
	
	if ($user != "" && $pass != "") {
		$sql = "SELECT password, role from cfg_users where username='".$user."'";
		$ret = fetchArrayDB($sql, $conn);

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

	global $GETVars;
	global $configarray;
    global $conn;
	
	$server = $GETVars['server'];
	$ret = array();

        $daystring = strftime("%Y-%m-%d", $timestamp);
        $startofday = strtotime($daystring." 00:00:00");
        $endofday = strtotime($daystring." 23:59:59");

	$qtable = $configarray["queryarray"][$GETVars['qq']]["name"];

        $timestampquery = " WHERE timestamp between ".$startofday." and ".$endofday;
        $sql = "SELECT distinct timestamp from res_".$qtable."_".$server.$timestampquery;
        $ret = fetchArrayDB($sql, $conn);

	return $ret;

}


/**
 * getLastSnapshot - returns the last inserted timestamp of a query result
 *
 * @return string
 */

function getLastSnapshot() {

    global $GETVars;
    global $configarray;
    global $conn;

    $server = $GETVars['server'];
    $ret = array();

    $qtable = $configarray["queryarray"][$GETVars['qq']]["name"];

    $sql = "SELECT MAX(TimeStamp) from res_".$qtable."_".$server;
    $ret = fetchArrayDB($sql, $conn);
    $ret = (array)$ret[0];

    return $ret["MAX(TimeStamp)"];

}


/**
 * getTableFields
 *
 * @param string tablename
 * @return string
 */

function getTableFields($tablename="") {

    global $GETVars;
    global $conn;

    $sqlth = "SELECT * from ".$tablename." LIMIT 1";

    $sqlresth = fetchArrayDB($sqlth, $conn);
    $columnnames = "";

    echo $sql;
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

    global $configarray;
    global $conn;
    global $GETVars;

    $columnnames = getTableFields("cfg_".$GETVars['qq']);

    if ($GETVars['ob'] != '' ) {
        $sqlappend = " order by `".$GETVars['ob']."` ".$GETVars['orderdir'];
    } elseif ($configarray["queryarray"][$GETVars['qq']]["orderby"] != '') {
        $sqlappend = " order by `".$configarray["queryarray"][$GETVars['qq']]["orderby"]."` ".$GETVars['orderdir'];
    }

    if ($type == "edit") {
        $wc = " where `id`='".$_GET['id']."' ";
    }

    $sql = "SELECT ".$columnnames." from cfg_".$GETVars["qq"].$wc.$sqlappend;
    $_SESSION["lastsql"] = $sql;
    if ($sqlres) $message = $sql;

    $i = 1;
    $rs = fetchArrayDB($sql, $conn);

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
            
            $baseurl = $_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu'];
            $outp .= "<td width='20px'><a href='".$baseurl."&id=".$id."&action=edit' onclick=''><img src='images/edit.png' border=0></img></a></td>";
            $outp .= "<td width='20px'><a href='#' onclick='show_confirm(\"".$baseurl."\", $id, \"delete\")'><img src='images/delete.png' border=0 ></img></a></td>";

            $outp .= "</tr>\n";
        } else {
            $outp = fetchArrayDB($sql, $conn);
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

	global $configarray;
	global $GETVars;
	global $timetablestarttime;
	global $whereclause;
	global $message;
	global $conn;

	$colorsarray = $configarray["colorsarray"];
	$queryarray = $configarray["queryarray"][$GETVars['qq']];

    $now = time();
    $oneday = 86400;
    $onehour = 3600;
    $tolerance = 1200;

	$server = $GETVars['server'];
	$outp = '';
	$outp_cache = '';
	$stop=FALSE;
	$tablearray = array(); 

	/**
	$query = ereg_replace("NOTEQUAL","<>",$query);
	$query = ereg_replace("LESS","<",$query);

    if (isset($timetablestarttime) && $timetablestarttime != "") {
            $query = ereg_replace("SEARCHFIELD","$timetablestarttime",$query);
	**/
	if ($GETVars['ob'] != '' ) {
		$sqlappend = " order by `".$GETVars['ob']."` ".$GETVars['orderdir'];
	} elseif ($configarray["queryarray"][$GETVars['qq']]["orderby"] != '') {
		$sqlappend = " order by `".$configarray["queryarray"][$GETVars['qq']]["orderby"]."` ".$GETVars['orderdir'];
	}

	$qtable = $configarray["queryarray"][$GETVars['qq']]["name"];
	$polltype = $configarray["queryarray"][$GETVars['qq']]["polltype"];

	if ($polltype == "snapshot") {
		if ($_SESSION['timemachine']['time'] == date) {
			$timestampquery = " WHERE timestamp=(SELECT MAX(TimeStamp) FROM res_".$qtable."_".$server.")";
		} else {
			$timestampquery = " WHERE timestamp = '".$_SESSION['timemachine']['time']."'";
		}
	} else {
		$timestampquery = "";
	}

	// get only latest entry

	//if ($whereclause["field"]!="" && $whereclause["val"]!="") {
	$searcharr = $_SESSION["search"][$GETVars['qq']];
	if (isset($searcharr) && $searcharr["field"] != "" && $searcharr["val"] != "") {
		if ($polltype == "snapshot") {
			$wc = " AND ";
		} else {
			$wc = " WHERE ";
		}
		$wc .= "`".$searcharr["field"]."`".$searcharr["op"]."'".$searcharr["val"]."' ";
    } else if (isset($timetablestarttime)) {
        $startunix = ((ceil($now/$onehour)*$onehour)-$onehour-$oneday)-(($timetablestarttime-24)*$onehour);
        $endunix = $startunix + $oneday + $onehour;
        $start = strftime("%Y-%m-%d %H:%M:%S.000000", $startunix);
        $end = strftime("%Y-%m-%d %H:%M:%S.000000", $endunix);
        $wc = " WHERE `End Time` >= '".$start."' AND `Start Time` <= '".$end."'";
	} else {
		$wc= " ";
	}

        $columnnames = getTableFields("res_".$qtable."_".$server);

	//execute the constructed query
	$sql = "SELECT ".$columnnames." from res_".$qtable."_".$server.$timestampquery.$wc.$sqlappend;

	$_SESSION["lastsql"] = $sql;
	if ($sqlres) $message = $sql;

	if ($type == "table") {
		$i = 1;
        $rs = fetchSplitArrayDB($sql,$conn,20);

        foreach ($rs as $row) {
			$color = "";
			$col = $queryarray["alert_field"];
			if ($col != '') {
				$error = checkAlert($queryarray["alert_comp"], $queryarray["alert_val"], $row[$col]);
				if($error) {
					$color = $queryarray["alert_col"];
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
		$outp = fetchArrayDB($sql, $conn);
	}
	else if ($type == "timetable") {
		$sqlres = fetchArrayDB($sql, $conn);
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

	return $outp;
}





/**
 * getCustomQuery - executes a custom tsm query directly on the selected TSM server and returns a HTML table
 *
 * @return string
 */

function getCustomQuery() {

        global $GETVars;
        global $configarray;

	$serverarr = $configarray["serverlist"][$GETVars['server']];
	$server = $serverarr["servername"];
	$port = $serverarr["port"];
	$ip = $serverarr["ip"];
        $user = $serverarr["username"];
        $pass = $serverarr["password"];
	
        $input = "";

	$input .= "<form action=".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']."&s=".$GETVars['server']." method='post'>";
	$input .= "<input type='text' name='querytxt' value='' >";
	$input .= "<input type='button' value='Go!' onclick='submit()' class='button'>";
	$input .= "</form><br>";

	$query = $_POST["querytxt"];

	if ($query != "") {

		$handle = popen("dsmadmc -se=$server -id=$user -password=$pass -TCPServeraddress=$ip -COMMMethod=TCPIP -TCPPort=$port -dataonly=yes -TAB \"$query\" ", 'r');

		$colcount = 0;

		if ($handle) {	
			while (!feof($handle)) {
                            $i=1;
                            while (!feof($handle) && !$stop) {
                                $read = fgets($handle, 4096);
                                $stop = strstr($read, 'ANR2034E');
                                if ($read != ' ' && $read != '' && !$stop) {
                                        $read=preg_replace('/[\n]+/', '', $read);
					$cols = split("\t", $read);
					if ($i % 2 == 0) {
						$outp = $outp."<tr class='d1'>";
					}else{
						$outp = $outp."<tr class='d0'>";
					}
					$i++;
					$colcount = count($cols);
					for ($co = 0; $co < count($cols); $co++) {
						$outp = $outp."<td>".$cols[$co]."</td>";
					}
					$outp = $outp."</tr>\n";
					$outp_cache = $outp;
                                }
                            }
			}
			$outp .= "</table>";
		}
	
		$header = "<table class='zebra'><tr>";
		for ($count = 0; $count < $colcount; $count++) {
			$header .= "<th>-</th>";
		}
		$header .= "</tr>";
	}

	
	return $input.$header.$outp;

}

/**
 * getSearchfield - returns the HTML code of the upper searchfield panel
 *
 * @return string
 */

function getSearchfield() {

    global $GETVars;
    global $configarray;
    global $conn;
    $ret = "";
    $arrfield = "";
    $arrval = "";
    $arrop = "";

    $operators = array ("<", "=", "<>", ">");

    $searcharr = $_SESSION["search"][$GETVars['qq']];
    if (isset($searcharr)) {
        $arrfield = $searcharr["field"];    
        $arrval = $searcharr["val"];    
        $arrop = $searcharr["op"];    
    }
    $sql = "SHOW COLUMNS FROM res_".$configarray["queryarray"][$GETVars['qq']]["name"]."_".$GETVars['server'];
    $fieldnames = fetchArrayDB($sql, $conn);

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

    $link = $_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']."&s=".$GETVars['server'];
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

	global $configarray;
	global $GETVars;
	$ret = "";
	$serverlist = $configarray["serverlist"];

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
		$listlink = $_SERVER['PHP_SELF']."?q=".$_SESSION["from"]."&m=".$GETVars['menu']."&s=".$servername;
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

    global $GETVars;
    global $configarray;
    global $conn;

    $i=1;
    $outp = "<table class='zebra'>";
    $outp .= "<tr><th>Time</th><th>Servername</th><th>Updated</th><th>Unchanged</th><th>Pollfreq not reached</th><th>Time needed (s)</th></tr>";

    $sql = "SELECT * from log_polldstat where timestamp > '".(time()-86400)."' order by timestamp desc";
    $_SESSION["lastsql"] = $sql;
    $rs = fetchSplitArrayDB($sql,$conn,20);
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
    $nav = showPageNavigation("20");
    if ($nav!="") {
        $outp = $outp."<tr><td colspan='0' align='center' class='footer'><a class='navhead'>".$nav."</a></td></tr>";
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

    global $GETVars;
    global $configarray;
    global $conn;

    $out="";
    $i=0;

    while(list($key, $val) = each($subindexqueryarray)) {

        $bgcol="";
        $comperator = "";
        $alertval = "";
        $alertcol = "";
        $cellcolors = $configarray["colorsarray"];

        $cache = $subindexqueryarray[$key]["cache"];
        if ($configarray["serverlist"][$GETVars['server']]["libraryclient"] == 1 && $subindexqueryarray[$key]["notforlibclient"] == 1) {
            $res = "-§§§-";
        } else {
            $res = '';
            $sql = "SELECT name, result from res_overview_".$GETVars['server']." where name='".$subindexqueryarray[$key]["name"]."'";
            $sqlres = fetchArrayDB($sql, $conn);
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
        $error = checkAlert($comperator, $alertval, $res[1]);
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

       global $GETVars;

        $timesteps = array("1 hour" => "1", "6 hours" => "6", "12 hours" => "12", "24 hours" => "24");

        $timetablestarttime = 24 + $_SESSION['timeshift'];
        $out = "<form action=".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']."&s=".$GETVars['server']." method='post'>";
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

	global $timetablestarttime;

	$now = time();
	$out = '';
	$height = 8;
	$faktor = 120;
	$oneday = 86400;
	$onehour = 3600;
	$tolerance = 1200;

    $timetablestarttime = 24 + $_SESSION['timeshift'];

    $startpunkt = ((ceil($now/$onehour)*$onehour)-$onehour-$oneday)-(($timetablestarttime-24)*$onehour);
	$endpunkt = $startpunkt + $oneday + $onehour;
	$lastpoint = ($endpunkt - $startpunkt)/$faktor;

	$out .= "<table class='timetable' width='".$lastpoint."'>";
    $out .= generateTimetableNavigation();
	$out .= generateTimetableHeader($startpunkt, $FirstCol);
	$out .= "</td></tr>";

    $lasttimepoint=$now-($timetablestarttime*$onehour)-$tolerance;

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
	$out .= generateTimetableHeader($startpunkt);
    $out .= generateTimetableNavigation();
	$out .= "</table>";

	return $out;

}


/**
 * getConfigArray - queries the DB and generates the global config array
 *
 * @return array
 */

function getConfigArray() {

    global $conn;
    $retArray = array();

    // Navigation
    $query = "SELECT * from cfg_mainmenu";
    $mainmenutablerows = fetchArrayDB($query, $conn);

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
    $mainmenuarray["q=custom&m=main"] = "Custom TSM Query";
    $mainmenuarray["q=polldstat&m=main"] = "Polling Daemon Log";
    $mainmenuarray["q=serverlist&m=main"] = "Change Server";
    if ($_SESSION["logindata"]["role"] == "admin") $mainmenuarray["admin"] = "Admin";
    $mainmenuarray["q=logout"] = "Logout";
    $menuarray["main"] = $mainmenuarray;

    $query = "SELECT * from cfg_mainmenu";
    $mainmenutablerows = fetchArrayDB($query, $conn);
    $query = "SELECT * from cfg_queries";
    $querytablerows = fetchArrayDB($query, $conn);


    while (list ($key, $val) = each ($mainmenutablerows)) {
        $menuname = $val['name'];
        $menulabel = $val['label'];
        $submenuarray = array();
        $submenuarray[""] = "<---";
        $query = "SELECT * from cfg_queries where parent='".$menuname."'";
        $querytablerows = fetchArrayDB($query, $conn);
        while (list ($subkey, $submenuitem) = each ($querytablerows)) {
            $submenuitem_name = $submenuitem['name'];
            $submenuitem_label = $submenuitem['label'];
            $url = "q=".$submenuitem_name."&m=".$menuname;
            $submenuarray[$url] = $submenuitem_label;
        }
        $submenuarray["trennlinie"] = "trennlinie";
        $submenuarray["q=custom&m=".$submenu['name']] = "Custom TSM Query";
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
    $adminmenuarray["tsmmonitor"] = "TSM Monitor";
    $adminmenuarray["q=logout"] = "Logout";
    $retArray["adminmenuarray"] = $adminmenuarray;

    // Overview Boxes
    $ret = array();
    
    $query = "SELECT * from cfg_overviewboxes order by sortorder asc";
    $queryoverviewboxes = fetchArrayDB($query, $conn);
    while (list ($subkey, $box) = each ($queryoverviewboxes)) {
        $query = "SELECT * from cfg_overviewqueries where parent='".$box['name']."' order by sortorder asc";
        $queryoverview = fetchArrayDB($query, $conn);
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
    $querytablerows = fetchArrayDB($query, $conn);
    while (list ($subkey, $queryrow) = each ($querytablerows)) {
        $dbret[$queryrow['name']] = (array)$queryrow;
    }
    $retArray["queryarray"] = $dbret;
    
    // General settings
    $query = "SELECT * from cfg_config";
    $rows = fetchArrayDB($query, $conn);
    $ret = array();
    foreach ($rows as $key => $val) {
        $ret[$val['confkey']] = $val['confval'];
    }
    $retArray["settings"] = $ret;

    // Set Stylesheet
    $query = "SELECT stylesheet from cfg_users where username='".$_SESSION["logindata"]["user"]."'";
    $row = fetchArrayDB($query, $conn);
    $retArray["stylesheet"] = $row[0]['stylesheet'];

    // Colors
    $query = "SELECT * from cfg_colors";
    $rows = fetchArrayDB($query, $conn);

    $ret = array();
    while (list ($key, $val) = each ($rows)) {
        $ret[$val['name']] = $val['value'];
    }
    $retArray["colorsarray"] = $ret;

    // Servers
    $query = "SELECT * from cfg_servers";
    $rows = fetchArrayDB($query, $conn);

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
 * connectDB - establish a DB connection via ADODB
 *
 * @param string $host the hostname of the DB server
 * @param string $port the portnumber for the DB connection
 * @param string $user the username for the DB connection
 * @param string $pass the password for the DB connection
 * @param string $db_name the name of the DB
 * @param string $db_type the type of the DB (currently only 'mysql')
 * @param string $retr the number attempts for the DB connection before a failure is reported
 * @return ADOConnection DB connection ID or error code if connection failed
 */
function connectDB($host, $port = "3306", $user, $pass, $db_name, $db_type, $retr = 20) {
    $try = 0;
    $hostport = $host . ":" . $port;
    $conn = NewADOConnection($db_type);

    while ($try <= $retries) {
        if ($conn->PConnect($hostport,$user,$pass,$db_name)) {
            return($conn);
        }
        $try++;
        usleep(50000);
    }

    die("FATAL: Cannot connect to database server on '$host':'$port'. Please make sure you have specified a valid database name in 'includes/config.php'\n");
    return 0;
}


/**
 * closeDB - close an open DB connection
 *
 * @param ADOConnection $DBconn DB connection ID to be closed
 * @return string 
 */
function closeDB($DBconn = FALSE) {
    if ($DBconn) {
        return $DBconn->Close();
    }
}


/**
 * execDB - execute a SQL statement against the DB via ADODB
 *
 * @param string $sql SQL statement to execute
 * @param ADOConnection $DBconn DB connection ID to run the SQL against
 * @return ADORecordSet
 */
function execDB($sql, $DBconn = FALSE) {
//    $DBconn->debug = true;
    $sql = sanitizeSQL($sql);

    $recordSet = &$DBconn->Execute($sql);
    if (($recordSet) || ($DBconn->ErrorNo() == 0)) {
        return($recordSet);
    } else {
        echo "<p style='font-size: 16px; font-weight: bold; color: red;'>Database Error (".$DBconn->ErrorNo().")</p>\n<p>".$DBconn->ErrorMsg()."</p>";
        exit;
    }
}


/**
 * fetchCellDB - execute a SQL query against the DB via ADODB and 
 *               return only the first column of the fist row found
 *               or a specified column of the fist row found
 *
 * @param string $sql SQL statement to execute
 * @param $column_name Column name to use instead of the first column
 * @param ADOConnection $DBconn DB connection ID to run the SQL against
 * @return string Content of the cell as a single variable
 */
function fetchCellDB($sql, $column_name, $DBconn = FALSE) {
//    $DBconn->debug = true;
    $sql = sanitizeSQL($sql);

    if ($column_name != '') {
        $DBconn->SetFetchMode(ADODB_FETCH_ASSOC);
    } else {
        $DBconn->SetFetchMode(ADODB_FETCH_NUM);
    }
    $recordSet = $DBconn->Execute($sql);

    if (($recordSet) || ($DBconn->ErrorNo() == 0)) {
        if (!$recordSet->EOF) {
            if ($column_name != '') {
                $column = $recordSet->fields[$column_name];
            }else{
                $column = $recordSet->fields[0];
            }
            $recordSet->close();

            return($column);
        }
    } else {
        echo "<p style='font-size: 16px; font-weight: bold; color: red;'>Database Error (".$DBconn->ErrorNo().")</p>\n<p>".$DBconn->ErrorMsg()."</p>";
        exit;
    }
}


/**
 * fetchRowDB - execute a SQL query against the DB via ADODB
 *              and return only the first row found
 *
 * @param string $sql SQL statement to execute
 * @param ADOConnection $DBconn DB connection ID to run the SQL against
 * @return array First row of results as an associative array
 */
function fetchRowDB($sql, $DBconn = FALSE) {
//    $DBconn->debug = true;
    $sql = sanitizeSQL($sql);

    $DBconn->SetFetchMode(ADODB_FETCH_ASSOC);
    $recordSet = $DBconn->Execute($sql);

    if (($recordSet) || ($DBconn->ErrorNo() == 0)) {
        if (!$recordSet->EOF) {
            $recordFields = $recordSet->fields;
            $recordSet->close();

            return($recordFields);
        }
    } else {
        echo "<p style='font-size: 16px; font-weight: bold; color: red;'>Database Error (".$DBconn->ErrorNo().")</p>\n<p>".$DBconn->ErrorMsg()."</p>";
        exit;
    }
}


/**
 * fetchArrayDB - execute a SQL query against the DB via ADODB
 *                and return results in an associative array.
 *
 * @param string $sql SQL statement to execute
 * @param ADOConnection $DBconn DB connection ID to run the SQL against
 * @return array All results in an associative array
 */
function fetchArrayDB($sql, $DBconn = FALSE) {
//    $DBconn->debug = true;
    $sql = sanitizeSQL($sql);

    $recordArray = array();
    $DBconn->SetFetchMode(ADODB_FETCH_ASSOC);
    $recordSet = &$DBconn->Execute($sql);

    if (($recordSet) || ($DBconn->ErrorNo() == 0)) {
        while ((!$recordSet->EOF) && ($recordSet)) {
            $recordArray{sizeof($recordArray)} = $recordSet->fields;
            $recordSet->MoveNext();
        }
        $recordSet->close();
        return($recordArray);
    } else {
        echo "<p style='font-size: 16px; font-weight: bold; color: red;'>Database Error (".$DBconn->ErrorNo().")</p>\n<p>".$DBconn->ErrorMsg()."</p>";
        exit;
    }
}


/**
 * fetchSplitArrayDB - execute a SQL query against the DB via ADODB
 *                     and return results in an associative array.
 *
 * @param string $sql SQL statement to execute
 * @param ADOConnection $DBconn DB connection ID to run the SQL against
 * @param string $rows_per_page number of rows per page a result will have
 * @return array All results in an associative array
 */
function fetchSplitArrayDB($sql, $DBconn = FALSE, $rows_per_page = '20') {
//    $DBconn->debug = true;
	global $max_pages;
    $page = intval($_GET['page']);

    $sql = sanitizeSQL($sql);

    $recordArray = array();
    $DBconn->SetFetchMode(ADODB_FETCH_ASSOC);
    $recordSet = &$DBconn->Execute($sql);

    if (($recordSet) || ($DBconn->ErrorNo() == 0)) {
	    $total_rows = $recordSet->RecordCount($recordSet);
        $max_pages = ceil($total_rows/$rows_per_page);

	    if($page > $max_pages || $page <= 0) {
    		$page = 1;
	    }
	    $offset = $rows_per_page * ($page-1);
        $endset = $offset + $rows_per_page;
        $recordSet->Move($offset);
       
        while (($recordSet->CurrentRow() < $endset) && ($recordSet->CurrentRow() < $total_rows) && ($recordSet)) {
            $recordArray{sizeof($recordArray)} = $recordSet->fields;
            $recordSet->MoveNext();
        }
        $recordSet->close();
        return($recordArray);
    } else {
        echo "<p style='font-size: 16px; font-weight: bold; color: red;'>Database Error (".$DBconn->ErrorNo().")</p>\n<p>".$DBconn->ErrorMsg()."</p>";
        exit;
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
 * @param ADOConnection $DBconn DB connection ID to run the SQL against
 * @return string Auto-increment ID if insert was performed
 */
function updateDB($table, $cells, $keys, $DBconn = FALSE, $autoquote = TRUE) {
    //$DBconn->debug = true;
    $DBconn->Replace($table, $cells, $keys, $autoquote);

    return $DBconn->Insert_ID();
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

?>
