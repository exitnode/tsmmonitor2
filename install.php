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
if ($_REQUEST["step"] != "30" && $_REQUEST["step"] != "40") {
    include_once "includes/page_head.php";
}

// allow the upgrade script to run for as long as it needs to
ini_set("max_execution_time", "0");

$tsm_monitor_versions = array("0.1.0", "0.1.1");

$old_tsm_monitor_version = fetchCellDB("select confval from cfg_config where confkey='version'", '', $conn);

// try to find current (old) version in the array
$old_version_index = array_search($old_tsm_monitor_version, $tsm_monitor_versions);

/* do a version check */
if ($old_tsm_monitor_version == $config["tsm_monitor_version"]) {
    echo "
      <p style='font-size: 16px; font-weight: bold; color: red;'>Error</p>\n
      <p>This installation is already up-to-date. Click <a href='index.php'>here</a> to use TSM Monitor.</p>\n
    </div>\n
  </table>\n
</body>\n
</html>\n";
    exit;
} elseif (empty($old_tsm_monitor_version)) {
    echo "
      <p style='font-size: 16px; font-weight: bold; color: red;'>Error</p>\n
      <p>You have created a new database, but have not yet imported the 'tsmmonitor.sql' file. At the command line, execute the following to continue:</p>\n
      <p><pre>mysql -u $db_user -p $db_password < tsmmonitor.sql</pre></p>\n
      <p>This error may also be generated if the TSM Monitor database user does not have correct permissions on the TSM Monitor database.<br>\n
         Please ensure that the TSM Monitor database user has the ability to SELECT, INSERT, DELETE, UPDATE, CREATE, ALTER, DROP, INDEX on the TSM Monitor database.</p>\n
    </div>\n
  </table>\n
 </body>\n
</html>\n";
    exit;
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
if (empty($_REQUEST["step"])) {
    $_REQUEST["step"] = 10;
} else {
    if ($_REQUEST["step"] == "10") {
        $_REQUEST["step"] = "20";
    } elseif (($_REQUEST["step"] == "20") && ($_REQUEST["install_type"] == "10")) {
        $_REQUEST["step"] = "30";
    } elseif (($_REQUEST["step"] == "20") && ($_REQUEST["install_type"] == "20")) {
        $_REQUEST["step"] = "40";
    } elseif ($_REQUEST["step"] == "30") {
        $_REQUEST["step"] = "90";
    } elseif ($_REQUEST["step"] == "40") {
        $_REQUEST["step"] = "90";
    }
}

if ($_REQUEST["step"] == "90") {
    // Flush updated data to DB
    // ...
    // ...
    // ...
    updateDB('cfg_config', array(confkey => 'version', confval => $config['tsm_monitor_version']), 'confkey', $conn);
    closeDB($conn);
    header("Location: index.php");
    exit;
} elseif (($_REQUEST["step"] == "40") && ($_REQUEST["install_type"] == "20")) {
    // if the version is not found, die
    if (!is_int($old_version_index)) {
        echo "
      <p style='font-size: 16px; font-weight: bold; color: red;'>Error</p>\n
      <p style='font-size: 12px;'>Invalid TSM Monitor version\n
        <strong>$old_tsm_monitor_version</strong>, cannot upgrade to <strong>".$config["tsm_monitor_version"]."</strong>\n
      </p>
    </div>\n
  </table>\n
</body>\n
</html>\n";
        exit;
    }

    // loop over all versions up to the current and perform incremental updates
    for ($i = ($old_version_index+1); $i < count($tsm_monitor_versions); $i++) {
        if ($tsm_monitor_versions[$i] == "0.1") {
            include "install/0_1_to_0_1_1.php";
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
                      <td>
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
                        <?php } ?>

                      </td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                    </tr>
                    <tr>
                      <td align="right">
                        <p>

                        <?php if ($_REQUEST["step"] == "30" || $_REQUEST["step"] == "40") { ?>

                          <input style='display: block; width: auto; background: #eaeaea; margin-bottom: 2px; padding: 3px 30px 3px 30px; color: #000000; font-size: 11px; font-weight: bold; text-decoration: none; border: 1px solid #ffffff;' type="submit" value="Finish">
                        <?php } else { ?>

                          <input style='display: block; width: auto; background: #eaeaea; margin-bottom: 2px; padding: 3px 30px 3px 30px; color: #000000; font-size: 11px; font-weight: bold; text-decoration: none; border: 1px solid #ffffff;' type="submit" value="Next">
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

