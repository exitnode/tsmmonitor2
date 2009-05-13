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
 * tmonpolld.php, TSM Monitor
 * 
 * This file is the TSM Monitor Polling Daemon. It executes queries against TSM
 * and inserts them into TMS Monitor's MySQL Database
 * 
 * @author Michael Clemens
 * @version 1.0
 * @package tsmmonitor
 */

// ** Default database settings ** //
/**
$db_type = 'mysql';
$db_name = 'tsmmonitor';
$db_user = 'tsmmonitor';
$db_password = 'tsmmonitor';
$db_host = 'localhost';
$db_port = '3306';
$db_charset = 'utf8';
$db_collate = '';
*/
// ** Include user configureable definitions ** //
//include(dirname(__FILE__) . "/../includes/config.php");

// ** Global configuration array ** //
//$config = array();

// ** Display ALL PHP errors ** //
//error_reporting(E_ALL);

// ** Include generic code and external libraries ** //
//include ("../extlib/adodb5/adodb.inc.php");
//include_once("../includes/adodb.php");
include_once("../includes/global.php");

// ** Connect to the database ** //
//$adodb = new ADOdb($db_host, $db_port, $db_user, $db_password, $db_name, $db_type, '' ,FALSE);

$tmonpolld = new PollD($adodb);
$tmonpolld->poll();


?>
