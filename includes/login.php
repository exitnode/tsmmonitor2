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
<form action="<?php echo $_SERVER['PHP_SELF']."?q=".$qq."&m=".$menu; ?>" method="post">
*/

/**
 *
 * login.php, TSM Monitor
 * 
 * provides login form for TSM Monitor
 * 
 * @author Michael Clemens
 * @package tsmmonitor
 */

$isAdmin = strstr($_SERVER['PHP_SELF'], 'admin.php');
if ($isAdmin) {
	$headerimage = "images/PollDTitleAdmin.gif";
} else {
	$headerimage = "images/PollDTitle.gif";
}

?>

<form action="<?php echo $_SERVER['PHP_SELF']; if ($GETVars['qq'] != 'logout'){ echo '?q='.$GETVars['qq'].'&m='.$GETVars['menu']; } ?>" method="post">
<br>
<br>
<br>
<br>
<table class='login'>
<tr>
    <td colspan="2" id="head"><a class='navheader' ><img src="<?php echo $headerimage ?>" border=0></img></a></td>
</tr>
<tr><th colspan="2"><?php echo $errormsg ?></th></tr>
<tr><td>Username:</td><td><input name="loginname"></td></tr>
<tr><td>Password:</td><td><input name="loginpasswort" type=password></td></tr>
<tr><td colspan="2"><input type=submit name=submit value="Login"></td></tr>
</table>
<br>
<br>
<br>
<br>
<br>
<br>
</form>
