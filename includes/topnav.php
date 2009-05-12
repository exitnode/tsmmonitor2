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
 * topnav.php, TSM Monitor
 * 
 * top navigation bar for TSM Monitor
 * 
 * @author Michael Clemens
 * @package tsmmonitor
 */

?>

<form action="<?php echo $_SERVER['PHP_SELF']."?q=".$tsmmonitor->GETVars['qq']."&m=".$tsmmonitor->GETVars['menu']."&s=".$tsmmonitor->GETVars['server']; ?>" method="post">
<?php 
//if ($tsmmonitor->GETVars['qq'] != "admin" && !$_POST["edit"] == "edit") {
if ($tsmmonitor->GETVars['qq'] != "index" && $tsmmonitor->GETVars['qq'] != "overview" && $tsmmonitor->GETVars['qq'] != "serverlist") {  
	echo "<input type='button' value='PDF' onclick='genPDF()' class='button'>";
}
if ($tsmmonitor->GETVars['qq'] != "polldstat" && $tsmmonitor->GETVars['qq'] != "serverlist") {

	echo "<select name='s' size=1 onChange='submit();' class='button'>";

	while(list($servername,$serveritems) = each($tsmmonitor->configarray["serverlist"])) {
		echo '<option value="'.$servername.'"';
		if ($tsmmonitor->GETVars['server'] == $servername){echo "SELECTED";}
		echo '> '.$servername.' ('.$serveritems["description"].')</option>';
	}
	echo "</select>";
}
?>
</form>
