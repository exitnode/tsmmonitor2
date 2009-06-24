<?php

/*
 ************************************************************************
 TSM Monitor 2 v0.1 (www.tsm-monitor.org)

 Copyright (C) 2009 Michael Clemens <mail@tsm-monitor.org>

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ************************************************************************
 */

/**
 *
 * index.php, TSM Monitor
 *
 * main php file
 *
 * @author Michael Clemens
 * @package tsmmonitor
 */

include_once "includes/global.php";
include_once "includes/page_head.php";

?>
<?php if ($_SESSION["logindata"]["loggedin"]) { ?>
	<tr>
		<td colspan="2" id="head"><a class='navheader' href="index.php"><img src="images/PollDTitle.gif" border=0></img></a></td>
	</tr>
	<tr>
		<td id="tnleft"></td>
		<td id="tnright">
		<div id="tnbox1">

			<?php
			if ( $_SESSION["logindata"]["loggedin"] && !in_array($tsmmonitor->GETVars['qq'], array("admin", "serverlist", "custom", "polldstat", "index", "overview", "")) && !strstr($tsmmonitor->GETVars['qq'], 'table') ) {
				echo $tsmmonitor->getSearchfield();
			}
			?>

		</div>
		<div id="tnbox2">
			<?php if ($_SESSION["logindata"]["loggedin"]) { include_once "includes/topnav.php"; }  ?>
		</div>
	</td>
<?php } ?>
	</tr>
	<tr>
	<?php if ($_SESSION["logindata"]["loggedin"]) { ?>
		<!-- Start left navigation menu -->
		<td id="menue">
			<div class="menuelinks">
				<?php echo $tsmmonitor->getMenu( $tsmmonitor->menu['main'], "index.php?q=".$tsmmonitor->GETVars['qq']."&m=".$tsmmonitor->GETVars['menu']."&s=".$tsmmonitor->GETVars['server'], "index" );  ?>
			</div>
			<br>
			<div class='menuelinks' id='datechooser'>
				<?php echo $tsmmonitor->getTimemachine();  ?>
			</div>
			<!--
			<br>
			<div class="menuelinks">
				<?php //echo $tsmmonitor->getInfo();  ?>
			</div>
			-->
			<br>
			<div class="menuelinks">
				<?php echo $tsmmonitor->getStylesheetSwitcher();  ?>
			</div>
			<img src="/images/trans.gif" alt="" width="150" height="1" border="0"><br>
		</td>
		<!-- End left navigation menu -->
	<?php } ?>
		<td id="content">
			<?php

			// main content, right of menu
			if (isset($_SESSION["logindata"]["user"]) && isset($_SESSION["logindata"]["pass"]) && $tsmmonitor->GETVars['qq'] != "logout" && $_SESSION["logindata"]["loggedin"]){
				if ($tsmmonitor->GETVars['qq'] != "" && $tsmmonitor->GETVars['qq'] != "overview"){

					// show overview page
					if ($tsmmonitor->GETVars['qq'] == "index") {
						include_once "includes/overview.php" ;

						// show polld status
					} else if ($tsmmonitor->GETVars['qq'] == "polldstat") {
                        $lines = $_POST["lpp"];
                        if ($lines != "") {
                            if (!isset($_SESSION["lines"])){
                                $temp = array();
                                $temp[$tsmmonitor->GETVars['qq']] = $lines;
                                $_SESSION["lines"] = $temp;
                            } else {
                                $_SESSION["lines"][$tsmmonitor->GETVars['qq']] = $lines;
                            }
                        }
						echo $tsmmonitor->getPollDStat();

						// show serverlist
					} else if ( $tsmmonitor->GETVars['qq'] == "serverlist" ) {
						echo $tsmmonitor->getServerlist();

						// "vertical" table
					} else if ( strstr($tsmmonitor->GETVars['qq'], 'vertical'))  {

						$i = 0;
						$tablearray = $tsmmonitor->execute('verticaltable');
						echo "<table class='zebra'>";
						echo "<tr><th>Key</th><th>Value</th></tr>";
						foreach ($tablearray as $row) {
							while(list($keycell, $valcell) = each($row)) {
								$vertrow = array();
								$vertrow["key"] = $keycell;
								$vertrow["value"] = $valcell;
								echo $tsmmonitor->renderZebraTableRow($vertrow, $i%2, "", "", "");
								$i++;
							}
						}

						echo "</table>";


						// show normal table layout
					} else {
						$whereclause = array();
    					$whereclause["field"] = $_POST["wcfield"];
						$whereclause["val"] = $_POST["wcval"];
						$whereclause["op"] = $_POST["wcop"];
                        if ($whereclause["op"] == 'LIKE') {
                            $whereclause["val"] = ereg_replace("\*","%",$whereclause["val"]);
                            $_POST["wcval"] = $whereclause["val"];
                        }
						if ($whereclause["field"] != "" && $whereclause["val"] != "") {
							if ($_POST["Clear"] == "Clear") {
								$_SESSION["search"][$tsmmonitor->GETVars['qq']] = "";
							} else {
								if (!isset($_SESSION["search"])){
									$temp = array();
									$temp[$tsmmonitor->GETVars['qq']] = $whereclause;
									$_SESSION["search"] = $temp;
								} else {
									$_SESSION["search"][$tsmmonitor->GETVars['qq']] = $whereclause;
								}
							}
						}
						if ($_SESSION["tabletype"] != "" && $_SESSION["tabletype"] == "timetable") {

							if ($_POST["back"] != "") {
								$_SESSION['timeshift'] += $_SESSION['selectedtimestep'];
							}
							if ($_POST["forward"] != "") {
								$_SESSION['timeshift'] -= $_SESSION['selectedtimestep'];
							}
							if ($_SESSION['timeshift'] < 0) {
								$_SESSION['timeshift'] = 0;
							}

							$tablearray = $tsmmonitor->execute('timetable');
							$headerarray = $queryarray[$tsmmonitor->GETVars['qq']]["header"]["column"];
							echo $tsmmonitor->generateTimetable($tablearray, $headerarray[0]);

                        } else if ($_SESSION["tabletype"] != "" && $_SESSION["tabletype"] == "timetable2") {

                            if ($_POST["back"] != "") {
                            	$_SESSION['timeshift'] += $_SESSION['selectedtimestep'];
                            }
                            if ($_POST["forward"] != "") {
                            	$_SESSION['timeshift'] -= $_SESSION['selectedtimestep'];
                            }
                            if ($_SESSION['timeshift'] < 0) {
                            	$_SESSION['timeshift'] = 0;
                            }

                            $tablearray = $tsmmonitor->execute('timetable2');
                            $headerarray = $queryarray[$tsmmonitor->GETVars['qq']]["header"]["column"];
                            echo $tsmmonitor->generateTimetable2($tablearray, $headerarray[0]);

						} else {
                            $lines = $_POST["lpp"];
                            if ($lines != "") {
                                if (!isset($_SESSION["lines"])){
                                    $temp = array();
                                    $temp[$tsmmonitor->GETVars['qq']] = $lines;
                                    $_SESSION["lines"] = $temp;
                                } else {
                                    $_SESSION["lines"][$tsmmonitor->GETVars['qq']] = $lines;
                                }
                            }
							echo "<table class='zebra'>";
							$thead = $tsmmonitor->getTableheader();
							$tbody = $tsmmonitor->execute('table');
							$nav = $tsmmonitor->showPageNavigation("40");
							if ($nav != "") {
								echo "<tr><td colspan='".$thead["numfields"]."' align='center' class='footer'><a>".$nav."</a></td></tr>";
							}
							echo $thead["header"];
                            if ($tbody != "") {
                                echo $tbody;
                            } else {
                                echo "<tr class='d0'><td colspan='".$thead["numfields"]."' align='center'>No entries found in database.</td></tr>";
                            }
							if ($nav != "") {
								echo "<tr><td colspan='".$thead["numfields"]."' align='center' class='footer'><a>".$nav."</a></td></tr>";
							}
							echo "</table>";
						}

					}
				}
			} else {
				if (isset($_SESSION["logindata"])){
					$errormsg = "Login failed!";
				}else{
					$errormsg = "Login";
				}

				session_unset();
				$_SESSION=array();

				include_once "includes/login.php";

			}
			$_SESSION['from'] = $tsmmonitor->GETVars['qq'];
			session_write_close(void);
	?>

		</td>
	</tr>

	<?php if ($_SESSION["logindata"]["loggedin"])  include_once "includes/footer.php";  ?>


</table>

</div>
</body>
</html>
