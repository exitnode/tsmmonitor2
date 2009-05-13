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
 * footer.php, TSM Monitor
 * 
 * HTML footer for TSM Monitor
 * 
 * @author Michael Clemens
 * @package tsmmonitor
 */

?>
<!-- Begin: footer.php -->
<?php if ($message!="") { ?>
      <tr>
        <td colspan="2" id="sysinfo"><b>System Message: </b><?php echo $message;  ?></td>
      </tr>
<?php } ?>
      <tr>
        <td colspan="2">
          <table width=100%>
            <tr>
              <td width=100% id="footer">TSM Monitor 2 v<?php echo $config["tsm_monitor_version"]?> &copy; 2008 - <?php echo date('Y'); ?> TSM Monitor Development Team (<a class='nav' href="http://www.tsm-monitor.org">www.tsm-monitor.org</a>)</td>
              <td>
<?php
#/**
echo "                <form action=".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']."&s=".$GETVars['server']." method='post'>\n";
echo "                  <select name='css' size=1 onChange='submit();' class='button'>\n";
$styles = array('default.css', 'blue.css', 'dark.css', 'grey3d.css', 'horizon.css', 'orange3d');
foreach ($styles as $style) {
    echo '                    <option value="'.$style.'"';
    if ($_SESSION['stylesheet'] == $style){ echo "SELECTED"; }
    echo "> ".$style."</option>\n";
}
echo "                  </select>\n";
echo "                </form>\n";
#**/
?>
              </td>
            </tr>
          </table>
        </td>
      </tr>
<!-- End: footer.php -->
