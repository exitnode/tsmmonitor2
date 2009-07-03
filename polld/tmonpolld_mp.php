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
 * tmonpolld_mp.php, TSM Monitor
 * 
 * This file instantiates PollD_MP and executes the multi-process polling.
 * Start it like this: 'nohup php tmonpolld_mp.php &'
 * 
 * 
 * @author Michael Clemens, Frank Fegert
 * @version 1.0
 * @package tsmmonitor
 */

include_once("../includes/global.php");

$tmonpolld = new PollD_MP($config);
$tmonpolld->controlPollD("on");
$tmonpolld->poll();


?>
