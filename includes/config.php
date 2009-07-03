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
 * config.php, TSM Monitor
 * 
 * This file defines user configureable global constants
 * 
 * @author Michael Clemens
 * @package tsmmonitor
 */

// ** database settings ** //
$config["db_type"] = 'mysql';             // Name of the DBMS hosting the tsmmonitor database
$config["db_name"] = 'tsmmonitor';        // Name of the tsmmonitor database
$config["db_user"] = 'tsmmonitor';        // Username used to connect to the tsmmonitor database
$config["db_password"] = 'tsmmonitor';    // Password used to connect to the tsmmonitor database
$config["db_host"] = 'localhost';         // Hostname or IP address the DBMS is listening on
$config["db_port"] = '3306';              // Port number the DBMS is listening on
$config["db_charset"] = 'utf8';
$config["db_collate"] = '';
?>
