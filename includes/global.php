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
 * global.php, TSM Monitor
 *
 * This file defines global configuration variables, includes 
 * generic code and external libraries, initializes the PHP
 * session and establishes the database connection.
 *
 * @author Frank Fegert
 * @package tsmmonitor
 */

/*
   !!! WARNING !!!

   The defaults in this file are not meant to be altered by users!
   See include/config.php for user configurable database settings.

*/

// ** Default database settings ** //
$db_type = 'mysql';
$db_name = 'tsmmonitor';
$db_user = 'tsmmonitor';
$db_password = 'tsmmonitor';
$db_host = 'localhost';
$db_port = '3306';
$db_charset = 'utf8';
$db_collate = '';

// ** Include user configureable definitions ** //
include(dirname(__FILE__) . "/config.php");

// ** Global configuration array ** //
$config = array();

// ** Current TSM Monitor version ** //
$config["tsm_monitor_version"] = '0.1.0';

// ** Set TSM Monitor server OS to a general value (only 'unix' or 'win32') ** //
$config["server_os"] = (strstr(PHP_OS, "WIN")) ? "win32" : "unix";

// ** Search paths for external programs (dsmadmc, php, ...) ** //
if ($config["server_os"] == "win32") {
    $config["search_path"] = array('c:/php', 'c:/progra~1/php', 'd:/php', 'd:/progra~1/php', 'c:/progra~1/tivoli/tsm/baclient', 'd:/progra~1/tivoli/tsm/baclient');
} elseif ($config["server_os"] == "unix") {
    $config["search_path"] = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin', '/usr/tivoli/tsm/client/admin/bin', '/opt/tivoli/tsm/client/ba/bin');
}

// ** Paths (libraries, includes, ...) ** //
$config["base_path"] = ereg_replace("(.*[\\\/])includes", "\\1", dirname(__FILE__));
$config["library_path"] = ereg_replace("(.*[\\\/])includes", "\\1extlib", dirname(__FILE__));
$config["include_path"] = dirname(__FILE__);

// ** Try to convince browsers not to cache any pages ** //
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ** Display ALL PHP errors ** //
//error_reporting(E_ALL);

// ** Include generic code and external libraries ** //
include ($config["library_path"] . "/adodb5/adodb.inc.php");
include_once($config["include_path"] . "/functions.php");
include_once($config["include_path"] . "/polld.php");

// ** Connect to the database ** //
$conn = connectDB($db_host, $db_port, $db_user, $db_password, $db_name, $db_type);

// check to see if this is a new installation
$version = fetchCellDB("select confval from cfg_config where confkey='version'", '', $conn);
if ($version != $config["tsm_monitor_version"] && basename($_SERVER['REQUEST_URI']) != 'install.php') {
    header("Location: install.php");
    exit;
}
// ** Initialize PHP session ** //
initialize();

// ** Include generic code and external libraries ** //
// ... more includes here

?>