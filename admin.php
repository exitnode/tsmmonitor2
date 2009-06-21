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
 * admin.php, TSM Monitor
 * 
 * admin backend
 * 
 * @author Michael Clemens
 * @package tsmmonitor
 */

include_once "includes/global.php";
include_once "includes/page_head.php";

if ($_SESSION["logindata"]["role"]!="admin") {
	$_SESSION["logindata"] = "";
}

if ($_POST["css"] != "") {
	$_SESSION['stylesheet'] = $_POST["css"];
}

?>
<?php if ($_SESSION["logindata"]["loggedin"]) { ?>
	<tr>
		<td colspan="2" id="head"><a class='navheader' href="admin.php"><img src="images/PollDTitleAdmin.gif" border=0></img></a></td>
	</tr>
	<tr>
		<td id="tnleft"></td>
		<td id="tnright"></td>
<?php } ?>
	</tr>
	<tr>
	<?php if ($_SESSION["logindata"]["loggedin"]) { ?>
		<!-- Start left navigation menu -->
			<td id="menue">
				<div class="menuelinks">
					<?php echo $tsmmonitor->getMenu( $tsmmonitor->adminmenu, "admin.php?q=".$tsmmonitor->GETVars['qq']."&m=".$tsmmonitor->GETVars['menu'], "admin" );  ?>
				</div>
				<br>
				<img src="/images/trans.gif" alt="" width="150" height="1" border="0"><br>
			</td>
			<!-- End left navigation menu -->
	<?php } ?>
			<td id="content">
	<?php
				// main content, right of menu
				if (isset($_SESSION["logindata"]["user"]) && isset($_SESSION["logindata"]["pass"]) && $tsmmonitor->GETVars['qq'] != "logout" && $_SESSION["logindata"]["loggedin"]) {
					if ($tsmmonitor->GETVars['qq'] != "" && $tsmmonitor->GETVars['qq'] != "overview") {

						// show overview page
						if ($tsmmonitor->GETVars['qq'] == "index") {
							// do nothing
							// show settings page
						} else if ($tsmmonitor->GETVars['qq'] == "settings") {
							$tmonpolld = new PollD($adodb);
							$tmonpolld->adodb->setDebug($_SESSION["debug"]);

							// If start/stop button was pressed
							if ($_POST["PollDControl"] != "") {
								if ($_POST["PollDControl"] == "Start") {
									$tmonpolld->controlPollD("on");	
								} else if ($_POST["PollDControl"] == "Stop") {
									$tmonpolld->controlPollD("off");	
								}
							}

							if ($tmonpolld->isEnabled()=="1") {
								$polldenabled = "enabled and ".$tmonpolld->getStatus();
								//$cellcolor = "green";
							} else {
								$polldenabled = "disabled";
								//$cellcolor = "red";
							}

							echo "<form action=".$_SERVER['PHP_SELF']."?q=".$tsmmonitor->GETVars['qq']."&m=".$tsmmonitor->GETVars['menu']." method='post'>";
							echo "<table class='zebra'>";
							echo "<tr><th>Configuration</th><th>Action</th><th>Status</th></tr>";
							echo "<tr class='d0'>";
							echo "	<td>PollD Control</td>";
							echo "	<td>";
							echo "		<input type='submit' class='button' name='PollDControl' value='Start' onclick='submit();'>";
							echo "		<input type='submit' class='button' name='PollDControl' value='Stop' onclick='submit();'>";
							echo "	</td>";
							echo "	<td bgcolor=".$cellcolor.">PollD is ".$polldenabled."</td>";
							echo "</tr>";
							echo "<tr class='d1'>";
							echo "  <td>Debug Mode</td>";
							echo "	<td>";
							echo "		<input type='submit' class='button' name='DebugMode' value='On' onclick='submit();'>";
							echo "		<input type='submit' class='button' name='DebugMode' value='Off' onclick='submit();'>";
							echo "	</td>";
							echo "	<td>".$_SESSION["debug"]."</td>";
							echo "</tr>";
							echo "<tr class='d0'>";
							echo "  <td>PollD Control</td>";
							echo "  <td>";
							echo "		<table border=0>";
							echo "          	<tr><td>";
							echo "				<select name='cleandbserver' size=1 class='button'>";
							echo "				<option value='all'>- all servers -</options>";
							while(list($servername,$serveritems) = each($tsmmonitor->configarray["serverlist"])) {
								echo '<option value="'.$servername.'"> '.$servername.' ('.$serveritems["description"].')</option>';
							}
							echo "				</select>";
							echo "			</td><td>";
							echo "				<select name='cleandbquery' size=1 class='button'>";
							echo '				<option value="all">- all queries -</options>';
							while(list($queryname,$queryitems) = each($tsmmonitor->queryarray)) {
								echo '<option value="'.$queryname.'"> '.$queryname.'</option>';
							}
							echo "				</select>";
							echo "			</td><td>"; 
							echo "				<select name='cleandbovqueires' size=1 class='button'>";
							echo '				<option value="yes">yes</options>';
							echo '				<option value="no">no</options>';
							echo "				</select>";
							echo "			</td><td>";
							echo "				<select name='cleandbtime' size=1 class='button'>";
							$times = array("1 month" => "30", "2 months" => "60", "3 months" => "90", "6 months" => "180", "1 year" => "360");
							while(list($label,$value) = each($times)) {
								echo '<option value="'.$value.'"> '.$label.'</option>';
							}
							echo "				</select>";
							echo "			</td><td>";
							echo "				<input type='submit' class='button' name='cleanaction' value='Clean Up' onclick='submit();'>";
							echo "			</td></tr>";
							echo "		</table>";
							echo "	</td>";
							echo "	<td></td>";
							echo "</tr>";
							echo "</table>";
							echo "</form>";

						} else {
							if ( ($_GET['action'] != "" && ($_GET['action'] == "edit" && $_GET['id'] != "")) || $_POST['Add'] == "Add") {
								$i = 0;
								// show Add New Entry Form
								if ($_POST['Add'] == "Add") {
									$sqlth = "SHOW COLUMNS from cfg_".$tsmmonitor->GETVars['qq'];
									$sqlresth = $adodb->fetchArrayDB($sqlth);
									echo "<form action=".$_SERVER['PHP_SELF']."?q=".$tsmmonitor->GETVars['qq']."&m=".$tsmmonitor->GETVars['menu']." method='post'>";
									echo "<table class='zebra'>";
									echo "<tr><th>Key</th><th>Value</th></tr>";
									foreach ($sqlresth as $col) {
										if ($col['Field'] != "id") {
											if ($i == 0) {
												echo "<tr class='d0'>";
												$i = 1;
											} else {
												echo "<tr class='d1'>";
												$i = 0;
											}
											if ($col['Field'] == "password") {
												echo "<td><b>".$col['Field']."</b></td><td><input type='password' name='txt".$col['Field']."' value='' /></td></tr>";
											} else {
												echo "<td><b>".$col['Field']."</b></td><td><input type='text' size='50' name='txt".$col['Field']."' value='' /></td></tr>";
											}
										}
									}
									echo "<tr><td colspan=2 class='footer'>";
									echo "<input type='submit' class='button' name='AddSave' value='Save' onclick='submit();'>";
									echo "<input type='submit' class='button' name='Cancel' value='Cancel' onclick='submit();'>";
									echo "</td></tr>";
									echo "</table></form>";

									// show Edit Existing Entry Form
								} else {
									$tablearray = $tsmmonitor->getAdminTables("edit");
									echo "<form action=".$_SERVER['PHP_SELF']."?q=".$tsmmonitor->GETVars['qq']."&m=".$tsmmonitor->GETVars['menu']." method='post'>";
									echo "<table class='zebra'>";
									echo "<tr><th>Key</th><th>Value</th></tr>";
									foreach ($tablearray as $row) {
										while(list($keycell, $valcell) = each($row)) {
											if ($i == 0) {
												echo "<tr class='d0'>";
												$i = 1;
											} else {
												echo "<tr class='d1'>";
												$i = 0;
											}
											if ($keycell == "password") {
												echo "<td><b>".$keycell."</b></td><td><input type='password' name='txt".$keycell."' value='' /></td></tr>\n";
											} else if ($keycell == "id") {
												$id = $valcell;
											} else {
                                                echo "<td><b>".$keycell."</b></td><td>";
                                                if (strlen($valcell) < 150) {
                                                    echo "<input type='text' size='50' maxlength='150' name='txt".$keycell."' value='".$valcell."' />";
                                                } else {
                                                    echo "<textarea name='txt".$keycell."' cols='60' rows='5'>$valcell</textarea>";
                                                }
                                                echo "</td></tr>\n";
                                            }
										}
									}
									echo "<tr><td colspan=2 class='footer'>";
									echo "<input type='submit' class='button' name='EditSave' value='Save' onclick='submit();'>";
									echo "<input type='submit' class='button' name='Cancel' value='Cancel' onclick='submit();'>";
									echo "<input type='hidden' name='id' value='".$id."' />";
									echo "</td></tr>";

									echo "</table></form>";
								}

								// show List of all entries
							} else {
								// Process deletion of an item
								if ( $_GET['id'] != "" && $_GET['action'] != "") {
									if ($_GET['action'] == "delete") {
										echo $_POST['hidfield'];
										$sql = "DELETE from cfg_".$_GET['q']." where id='".$_GET['id']."' LIMIT 1";
										$adodb->execDB($sql);
									}
									// Process update of an existing item or insert of a new one
								} else if ($_POST['EditSave'] == "Save" || $_POST['AddSave'] == "Save") {
									$sqlth = "SHOW COLUMNS from cfg_".$_GET['q'];
									$sqlresth = $adodb->fetchArrayDB($sqlth);
									$colarray = array();
									$colarray['id'] = $_POST['id'];
									$set = array();
									$sqlcols = array();
									$sqlvals = array();

									// get all table fields to be selected
									foreach ($sqlresth as $col) {
                                        if (get_magic_quotes_gpc() != 0) {
                                            $_POST["txt".$col['Field']] = stripslashes($_POST["txt".$col['Field']]);
                                        }
										if ($col['Field'] != "id") {
											if ($col['Field'] == "password" && $tsmmonitor->GETVars['qq'] != 'servers') {
												if ($_POST["txt".$col['Field']] != "") {
													$val = md5($_POST["txt".$col['Field']]);
												} else {
													$val = "";
												}
											} else {
												$val = $_POST["txt".$col['Field']];
											}
											if ($_POST['AddSave'] == "Save") {
												$colarray["`".$col['Field']."`"] = $val;
                                                $val = "'".$val."'";
                                                array_push($sqlcols, $col['Field']);
                                                array_push($sqlvals, $val);
											} else if ($_POST['EditSave'] == "Save") {
												$colarray["`".$col['Field']."`"] = $val;
												array_push($set, $col['Field']."='".$val."'");
											}
										}
									}
									if ($_POST['AddSave'] == "Save") {
										$sql = "INSERT into cfg_".$_GET['q']." (".(implode(",", $sqlcols)).") values (".(implode(",", $sqlvals)).")";
									} else if ($_POST['EditSave'] == "Save") {
										$sql = "UPDATE cfg_".$_GET['q']." set ".(implode(",", $set))." where id='".$_POST['id']."' LIMIT 1";
									}
									$adodb->updateDB("cfg_".$_GET['q'], $colarray, 'id');
								}
								echo "<form action=".$_SERVER['PHP_SELF']."?q=".$tsmmonitor->GETVars['qq']."&m=".$tsmmonitor->GETVars['menu']." method='post'>";
								echo "<table class='zebra'>";
								$thead = $tsmmonitor->getTableheader();
                                echo $thead["header"];
								$tbody = $tsmmonitor->getAdminTables("list");
                                if ($tbody != "") {
                                    echo $tbody;
                                } else {
                                    echo "<tr class='d0'><td colspan='".($thead["numfields"]+1)."' align='center'>No entries found in database.</td></tr>";
                                }
								$nav = $tsmmonitor->showPageNavigation("40");
								if ($nav!="") {
									echo "<tr><td colspan='".($thead["numfields"]+1)."' align='center' class='footer'><a class='navhead'>".$nav."</a></td></tr>";
								}
								echo "</table>";
								echo "<input type='submit' class='button' name='Add' value='Add' onclick='submit();'>";
								echo "</form>";
							}
						}
					}
				} else {
					if (isset($_SESSION["logindata"])) {
						$errormsg = "Login failed!";
					} else {
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

	<?php if ($_SESSION["logindata"]["loggedin"]) include_once "includes/footer.php";  ?>

	</table>

</div>
</body>
</html>
