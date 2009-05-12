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
 * overview.php, TSM Monitor
 * 
 * Overview page for TSM Monitor
 * 
 * @author Michael Clemens
 * @package tsmmonitor
 */

?>

<table width=90% cellspacing="3" cellpadding="3">
<tr><td colspan="3" height='15px'></td></tr>
<tr><th colspan="3" align='center'>Quick Overview for Server <?php echo $server; ?></th></tr>
<tr><td colspan="3" height='15px'></td></tr>
<tr>
<td width='48%' valign='top'>
<table class='zebra'>
<tr><th colspan="2">Health Status</th></tr>
<?php echo $tsmmonitor->getOverviewRows($tsmmonitor->configarray["infoboxarray"]["healthdata"]); ?>
</table>
</td>
<td width='4%'>
</td>
<td width='48%' valign='top'>
<table class='zebra'>
<tr><th colspan="2">TSM Database</th></tr>
<?php //echo getOverviewRows(getInfobox("database")); ?>
<?php echo $tsmmonitor->getOverviewRows($tsmmonitor->configarray["infoboxarray"]["database"]); ?>
</table>
</td>
</tr>
<tr><td colspan="3" height='20px'></td></tr>
<tr>
<td width='48%' valign='top'>
<table class='zebra'>
<tr><th colspan="2">Total Data</th></tr>
<?php //echo getOverviewRows(getInfobox("totaldata")); ?>
<?php echo $tsmmonitor->getOverviewRows($tsmmonitor->configarray["infoboxarray"]["totaldata"]); ?>
</table>
</td>
<td width='4%'>
</td>
<td width='48%' valign='top'>
<table class='zebra'>
<tr><th colspan="2">Schedule Status</th></tr>
<?php //echo getOverviewRows(getInfobox("schedules")); ?>
<?php echo $tsmmonitor->getOverviewRows($tsmmonitor->configarray["infoboxarray"]["schedules"]); ?>
</table>
</td>
</tr>
</table>
