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
 * install.php, TSM Monitor
 *
 * install php file
 *
 * @author Frank Fegert
 * @package tsmmonitor
 */

include_once "includes/global.php";
if ($_REQUEST["step"] != "50" || $_REQUEST["refresh"] != "") {
    include_once "includes/page_head.php";
}

// allow the upgrade script to run for as long as it needs to
ini_set("max_execution_time", "0");

// check if the necessary PHP extensions are loaded
$exts = array("session", "sockets");
$ext_load = true;
foreach ($exts as $ext) {
    if (!extension_loaded($ext)){
        $ext_load = false;
        $ext_miss .= "        <li style='font-size: 12px;'>$ext</li>\n";
    }
}
if (!$ext_load) {
    echo "
      <p style='font-size: 16px; font-weight: bold; color: red;'>Error</p>
      <p style='font-size: 12px;'>The following PHP extensions are missing:</p>
      <ul>
$ext_miss
      </ul>
      <p style='font-family: Verdana, Arial; font-size: 12px;'>Please install those PHP extensions and retry the installation process.</p>
    </table>
  </div>
</body>
</html>";
    exit;
}

$tsm_monitor_versions = array("0.1.0", "0.1.1");

$old_tsm_monitor_version = $adodb->fetchCellDB("select confval from cfg_config where confkey='version'", '');

// try to find current (old) version in the array
$old_version_index = array_search($old_tsm_monitor_version, $tsm_monitor_versions);

// do a version check
if ($old_tsm_monitor_version == $config["tsm_monitor_version"]) {
    echo "
      <p style='font-size: 16px; font-weight: bold; color: red;'>Error</p>
      <p>This installation is already up-to-date. Click <a href='index.php'>here</a> to use TSM Monitor.</p>
    </table>
  </div>
</body>
</html>";
    exit;
} elseif (empty($old_tsm_monitor_version)) {
    echo "
      <p style='font-size: 16px; font-weight: bold; color: red;'>Error</p>
      <p>You have created a new database, but have not yet imported the 'tsmmonitor.sql' file. At the command line, execute the following to continue:</p>
      <p><pre>mysql -u $db_user -p $db_password < tsmmonitor.sql</pre></p>
      <p>This error may also be generated if the TSM Monitor database user does not have correct permissions on the TSM Monitor database.<br>
         Please ensure that the TSM Monitor database user has the ability to SELECT, INSERT, DELETE, UPDATE, CREATE, ALTER, DROP, INDEX on the TSM Monitor database.</p>
    </table>
  </div>
 </body>
</html>";
    exit;
}

// dsmadmc binary path
$input["path_dsmadmc"]["name"] = "dsmadmc Binary Path";
$input["path_dsmadmc"]["desc"] = "The path to the TSM admin client binary.";
$which_dsmadmc = $tsmmonitor->findPath("dsmadmc", $config["search_path"]);
if (isset($tsmmonitor->configarray["settings"]["path_dsmadmc"])) {
    $input["path_dsmadmc"]["default"] = $tsmmonitor->configarray["settings"]["path_dsmadmc"];
} else if (!empty($which_dsmadmc)) {
    $input["path_dsmadmc"]["default"] = $which_dsmadmc;
} else {
    $input["path_dsmadmc"]["default"] = "dsmadmc";
}

// php/php5 binary path
$input["path_php"]["name"] = "PHP Binary Path";
$input["path_php"]["desc"] = "The path to the PHP binary.";
$which_php = $tsmmonitor->findPath("php", $config["search_path"]);
if(!isset($which_php)) {
    $which_php = $tsmmonitor->findPath("php5", $config["search_path"]);
}
if (isset($tsmmonitor->configarray["settings"]["path_php"])) {
    $input["path_php"]["default"] = $tsmmonitor->configarray["settings"]["path_php"];
} else if (!empty($which_php)) {
    $input["path_php"]["default"] = $which_php;
} else {
    $input["path_php"]["default"] = "php";
}

// logfile path
$input["path_tmlog"]["name"] = "TSM Monitor Logfile Path";
$input["path_tmlog"]["desc"] = "The path to the TSM Monitor log file.";
if (isset($tsmmonitor->configarray["settings"]["path_tmlog"])) {
    $input["path_tmlog"]["default"] = $tsmmonitor->configarray["settings"]["path_tmlog"];
} else {
    $input["path_tmlog"]["default"] = $config["base_path"] . "tsmmonitor.log";
}

// default for the install type
if (!isset($_REQUEST["install_type"])) {
    $_REQUEST["install_type"] = 0;
}
// defaults for the install type dropdown
if ($old_tsm_monitor_version == "new_install") {
    $default_install_type = "10";
} else {
    $default_install_type = "20";
}

// pre-processing that needs to be done for each step
// Intro and license page
if (empty($_REQUEST["step"])) {
    $_REQUEST["step"] = 10;
} else {
    // Install or update chooser
    if ($_REQUEST["step"] == "10") {
        $_REQUEST["step"] = "20";
    } elseif (($_REQUEST["step"] == "20") && ($_REQUEST["install_type"] == "10")) {
        $_REQUEST["step"] = "30";
    } elseif (($_REQUEST["step"] == "20") && ($_REQUEST["install_type"] == "20")) {
        $_REQUEST["step"] = "40";
    // Install
    } elseif ($_REQUEST["step"] == "30") {
        $_REQUEST["step"] = "50";
    // Update
    } elseif ($_REQUEST["step"] == "40") {
        $_REQUEST["step"] = "50";
    } elseif (($_REQUEST["step"] == "50") && ($_POST["refresh"] == "Refresh")) {
        $_REQUEST["step"] = "50";
        // get (possibly) updated values from the forms
        foreach ($input as $name => $array) {
            if (isset($_POST[$name])) {
                $input[$name]["default"] = $_POST[$name];
            }
        }
    } elseif ($_REQUEST["step"] == "50") {
        $_REQUEST["step"] = "90";
    }
}

if ($_REQUEST["step"] == "90") {
    // Flush updated data to DB
    foreach ($input as $name => $array) {
        if (isset($_POST[$name])) {
            $adodb->updateDB('cfg_config', array(confkey => "$name", confval => $_POST[$name], description => $array['name']), 'confkey');
        }
    }
    $adodb->updateDB('cfg_config', array(confkey => 'version', confval => $config['tsm_monitor_version']), 'confkey');
    $adodb->closeDB();
    header("Location: index.php");
    exit;
} elseif (($_REQUEST["step"] == "40") && ($_REQUEST["install_type"] == "20")) {
    // if the version is not found, die
    if (!is_int($old_version_index)) {
        echo "
      <p style='font-size: 16px; font-weight: bold; color: red;'>Error</p>
      <p style='font-size: 12px;'>Invalid TSM Monitor version
        <strong>$old_tsm_monitor_version</strong>, cannot upgrade to <strong>".$config["tsm_monitor_version"]."</strong>
      </p>
    </table>
  </div>
</body>
</html>";
        exit;
    }

    // loop over all versions up to the current and perform incremental updates
    for ($i = ($old_version_index+1); $i < count($tsm_monitor_versions); $i++) {
        if ($tsm_monitor_versions[$i] == "0.1.0") {
            include "install/0_1_0_to_0_1_1.php";
            upgrade_to_0_1_1();
        } /* elseif ($tsm_monitor_versions[$i] == "0.1.1") {
            include "install/0_1_1_to_0_1_2.php";
            upgrade_to_0_1_2();
        } elseif ($tsm_monitor_versions[$i] == "0.1.2") {
            include "install/0_1_2_to_0_1_3.php";
            upgrade_to_0_1_3();
        } */
    }
}
?>
      <tr>
        <td colspan="2" id="head">
          <a class='navheader' href="install.php"><img src="images/PollDTitle.png" border=0></img></a>
        </td>
      </tr>
      <tr>
        <td id="tnleft" align='center' style='font-size: 16px; font-weight: bold'>TSM Monitor Installer</td>
      </tr>
      <tr>
        <td>
          <form method="post" action="install.php">
            <table width="500" align="center" cellpadding="1" cellspacing="0" border="0">
              <tr bgcolor="#FFFFFF" height="10">
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td id="tnleft">
                  <table width="100%" border="0">
                    <tr>
                      <td colspan="2">
                        <?php // Installation Step 10
                            if ($_REQUEST["step"] == "10") {
                        ?>

                        <p>
                          Thank you for taking the time to download and install TSM Monitor.
                        </p>
                        <p>
                          TSM Monitor is a web application written in PHP to help TSM administrators
                          to quickly get reports and health status information of their TSM servers.
                          It generates it's content dynamically so one can easily add or modify queries
                          to adapt the application to one's own needs. Before you can start using TSM
                          Monitor, there are a few configuration steps that need to be done.
                        </p>
                        <p>
                          Make sure you have read and followed the required steps needed to install
                          TSM Monitor before continuing. Install information can be found here
                          <a href="http://www.tsm-monitor.org/wiki/doku.php">http://www.tsm-monitor.org/wiki/doku.php</a>
                        </p>
                        <p>
                          TSM Monitor is licensed under the GNU General Public License, you must agree
                          to its provisions before continuing:
                        </p>
                        <p>
                          TSM Monitor is free software; you can redistribute it and/or modify
                          it under the terms of the GNU General Public License as published by
                          the Free Software Foundation, either version 3 of the License, or
                          (at your option) any later version.
                        </p>
                        <p>
                          TSM Monitor is distributed in the hope that it will be useful,
                          but WITHOUT ANY WARRANTY; without even the implied warranty of
                          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
                          GNU General Public License for more details.
                        </p>
                        <p>
                          You should have received a copy of the GNU General Public License
                          along with TSM Monitor.  If not, see
                          <a href="http://www.gnu.org/licenses/">http://www.gnu.org/licenses/</a>.
                        </p>
                        <?php // Installation Step 20
                            } elseif ($_REQUEST["step"] == "20") {
                        ?>

                        <p>
                          Please select the type of installation
                        </p>
                        <p>
                          <select name="install_type">
                            <option value="10"<?php print ($default_install_type == "10") ? " selected" : "";?>>New install</option>
                            <option value="20"<?php print ($default_install_type == "20") ? " selected" : "";?>>Upgrade previous version</option>
                          </select>
                        </p>
                        <?php // Installation Step 30
                            } elseif ($_REQUEST["step"] == "30") {
                        ?>

                        <p>
                          Installation stuff could be done here ...
                        </p>

                        <?php // Installation Step 40
                            } elseif ($_REQUEST["step"] == "40") {
                        ?>

                        <p>
                          Upgrade stuff could be done here ...
                        </p>

                        <?php // Installation Step 50
                            } elseif ($_REQUEST["step"] == "50") {
                        ?>

                        <p>
                          Please check if the following values have been correctly determined for your
                          system and correct if necessary.
                        </p>
                        <?php
                            foreach ($input as $name => $array) {
                                if (isset($input[$name])) {
                                    $file = $array["default"];
                                    $resStr = "";
                                    $capStr = "";

                                    if (file_exists($file) && is_file($file)) {
                                        $resStr = "<font color='#008000'>[FOUND]</font> ";
                                        $capStr = "<span style='color:green'><br>[OK: FILE FOUND]</span>";
                                    } else {
                                        $resStr = "<font color='#FF0000'>[NOT FOUND]</font> ";
                                        $capStr = "<span style='color:red'><br>[ERROR: FILE NOT FOUND]</span>";
                                    }
                                    echo "
                        <p>
                          <strong>" . $resStr . $array["name"];
                                    if (!empty($array["name"])) {
                                        echo "</strong>: " . $array["desc"];
                                    } else {
                                        echo $array["desc"] . "</strong>";
                                    }
                                    echo "
                          <br>
                          <input type='text' id='$name' name='$name' size='50' value='". htmlspecialchars($file, ENT_QUOTES) . "'>" . $capStr . "
                          <br>
                        </p>";
                                }
                            }
                        ?>

                        <p>
                          <strong>NOTE:</strong> Once you click "Finish", the above settings will be
                          saved "as-is" to the TSM Monitor database. No further validation will be
                          performed, so please make sure the above settings are correct! Any of the
                          above settings can later on be changed with the TSM Monitor admin web
                          interface.
                          <br>
                          If you did choose to upgrade from a previous version of TSM Monitor, the
                          database will also be upgraded by clicking "Finish".
                        </p>

                        <p>
                          <strong>PHP memory_limit settings</strong>: Default or configured PHP limits
                          <table width="90%" align="center" border="0">

                        <?php
                            $mem_recommend = 64;
                            $mem_limit = ini_get(memory_limit);
                            if ($mem_limit != "") {
                                $mem_val = ereg_replace("([0-9]*).*", "\\1", $mem_limit);
                                $mem_unit = ereg_replace("([0-9]*)(.*)", "\\2", $mem_limit);
                            }
                            else {
                                $mem_val = "unknown";
                                $mem_unit = "";
                            }

                            $php_cli = $input["path_php"]["default"]." -r 'echo ini_get(memory_limit);'";
                            $mem_cli_recommend = 64;
                            $mem_cli_limit = exec("$php_cli");
                            if ($mem_cli_limit != "") {
                                $mem_cli_val = ereg_replace("([0-9]*).*", "\\1", $mem_cli_limit);
                                $mem_cli_unit = ereg_replace("([0-9]*)(.*)", "\\2", $mem_cli_limit);
                            }
                            else {
                                $mem_cli_val = "unknown";
                                $mem_cli_unit = "";
                            }

                            if ($mem_val < $mem_recommend) {
                                $mem_color = "#FF0000";
                                $mem_text = "Warning: at least";
                            } else {
                                $mem_color = "#008000";
                                $mem_text = "OK:";
                            }
                            echo "
                            <tr>
                              <td>
                                Webserver:
                              </td>
                              <td>
                                $mem_val $mem_unit
                              </td>
                              <td>
                                <font color='$mem_color'>[$mem_text $mem_recommend M recommended]</font>
                              </td>
                            </tr>";

                            if ($mem_cli_val < $mem_cli_recommend) {
                                $mem_color = "#FF0000";
                                $mem_text = "Warning: at least";
                            } else {
                                $mem_color = "#008000";
                                $mem_text = "OK:";
                            }
                            echo "
                            <tr>
                              <td>
                                Command line:
                              </td>
                              <td>
                                $mem_cli_val $mem_cli_unit
                              </td>
                              <td>
                                <font color='$mem_color'>[$mem_text $mem_cli_recommend M recommended]</font>
                              </td>
                            </tr>";
                        ?>
                          </table>
                        </p>

                        <p>
                          <strong>NOTE:</strong> Depending on the volume of data gathered from your
                          TSM servers by TSM Monitor, the PHP memory_limit settings shown above may
                          not be sufficient. Please edit your php.ini configuration files to at least
                          match the recommended values shown above and restart your webserver. This
                          has to be done manually and is not part of the TSM Monitor configuration!
                        </p>
                        <?php
                            }
                        ?>

                      </td>
                    </tr>
                    <tr>
                      <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                      <td width=75% align="right">
                        <p>

                        <?php if ($_REQUEST["step"] == "50") { ?>

                          <input style='display: block; width: auto; background: #eaeaea; margin-bottom: 2px; padding: 3px 30px 3px 30px; color: #000000; font-size: 11px; font-weight: bold; text-decoration: none; border: 1px solid #ffffff;' type="submit" name="refresh" value="Refresh">
                        </p>
                      </td>
                      <td align="right">
                        <p>
                          <input style='display: block; width: auto; background: #eaeaea; margin-bottom: 2px; padding: 3px 30px 3px 30px; color: #000000; font-size: 11px; font-weight: bold; text-decoration: none; border: 1px solid #ffffff;' type="submit" name="finish" value="Finish">
                        <?php } else { ?>

                          <input style='display: block; width: auto; background: #eaeaea; margin-bottom: 2px; padding: 3px 30px 3px 30px; color: #000000; font-size: 11px; font-weight: bold; text-decoration: none; border: 1px solid #ffffff;' type="submit" name="next" value="Next">
                        <?php } ?>

                        </p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            <input type="hidden" name="step" value="<?php print $_REQUEST["step"];?>">
          </form>
<!-- Done -->
          <?php session_write_close(void); ?>

        </td>
      </tr>
<?php include_once "includes/footer.php"; ?>
    </table>
  </div>
</body>
</html>

