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
$_SESSION["stylesheet"] = "style_classic.css";
if ($_REQUEST["step"] != "80" || $_REQUEST["refresh"] != "") {
    include_once "includes/page_head.php";
}

// Allow the upgrade script to run for as long as it needs to
ini_set("max_execution_time", "0");

// Some variables and HTML snippets
$input_err = "";
$page_foot = "
    </table>
  </div>
</body>
</html>";
$error_head = "<p style='font-size: 16px; font-weight: bold; color: red;'>Error</p>";

// Check if the necessary PHP functions are enabled
$funcs = array("popen");
$func_ena = true;
foreach ($funcs as $func) {
    if (!function_exists($func)){
        $func_ena = false;
        $func_miss .= "        <li style='font-size: 12px;'>$func</li>\n";
    }
}
if (!$func_ena) {
    echo $error_head;
    echo "
      <p style='font-size: 12px;'>The following PHP functions are missing:</p>
      <ul>
$func_miss
      </ul>
      <p style='font-family: Verdana, Arial; font-size: 12px;'>Please enable those PHP functions in your php.ini and retry the installation process.</p>";
    echo $page_foot;
    exit;
}

// Check if the necessary PHP extensions are loaded
$exts = array("session", "sockets");
$ext_load = true;
foreach ($exts as $ext) {
    if (!extension_loaded($ext)){
        $ext_load = false;
        $ext_miss .= "        <li style='font-size: 12px;'>$ext</li>\n";
    }
}
if (!$ext_load) {
    echo $error_head;
    echo "
      <p style='font-size: 12px;'>The following PHP extensions are missing:</p>
      <ul>
$ext_miss
      </ul>
      <p style='font-family: Verdana, Arial; font-size: 12px;'>Please install those PHP extensions and retry the installation process.</p>";
    echo $page_foot;
    exit;
}

// Try to find current (old) version in the array
$tsm_monitor_versions = array("0.1.0", "0.1.1");
$old_tsm_monitor_version = $adodb->fetchCellDB("SELECT confval FROM cfg_config WHERE confkey='version'", '');
$old_version_index = array_search($old_tsm_monitor_version, $tsm_monitor_versions);

// Do a version check
if ($old_tsm_monitor_version == $config["tsm_monitor_version"]) {
    echo $error_head;
    echo "
      <p>This installation is already up-to-date. Click <a href='index.php'>here</a> to use TSM Monitor.</p>";
    echo $page_foot;
    exit;
} else if (empty($old_tsm_monitor_version)) {
    echo $error_head;
    echo "
      <p>You have created a new database, but have not yet imported the 'tsmmonitor.sql' file. At the command line, execute the following to continue:</p>
      <p><pre>mysql -u $db_user -p $db_password < tsmmonitor.sql</pre></p>
      <p>This error may also be generated if the TSM Monitor database user does not have correct permissions on the TSM Monitor database.<br>
         Please ensure that the TSM Monitor database user has the ability to SELECT, INSERT, DELETE, UPDATE, CREATE, ALTER, DROP, INDEX on the TSM Monitor database.</p>";
    echo $page_foot;
    exit;
}

// Default for the install type
if (!isset($_REQUEST["install_type"])) {
    $_REQUEST["install_type"] = 0;
}
// Defaults for the install type dropdown
if ($old_tsm_monitor_version == "new_install") {
    $default_install_type = "10";
} else {
    $default_install_type = "20";
}

// Pre-processing that needs to be done for each step
// Intro and license page
if (empty($_REQUEST["step"])) {
    // Unset config data remaining from potential previous calls
    unset($_SESSION["install"]);
    $_REQUEST["step"] = 10;
} else {
    // Install or update chooser page
    if ($_REQUEST["step"] == "10") {
        $_REQUEST["step"] = "20";
    }
    // Initial admin user password page
    elseif ($_REQUEST["step"] == "20") {
        if ($_REQUEST["install_type"] == "10") {
            $_REQUEST["step"] = "30";
        } elseif ($_REQUEST["install_type"] == "20") {
            $_REQUEST["step"] = "60";
        }
    }
    // Binary and logfile path page
    elseif ($_REQUEST["step"] == "30") {
        // Check if passwords are not empty and match
        if ($_POST["adminpw"] == "" || $_POST["adminpwr"] == "") {
            $input_err = "Empty passwords are not allowed.";
            $_REQUEST["step"] = "30";
        } else if ($_POST["adminpw"] != $_POST["adminpwr"]) {
            $input_err = "Passwords do not match.";
            $_REQUEST["step"] = "30";
        } else {
            $_REQUEST["step"] = "40";
            $_SESSION["install"]["adminpw"] = md5($_POST["adminpw"]);

            // dsmadmc binary path
            $_SESSION["install"]["paths"]["path_dsmadmc"]["name"] = "dsmadmc Binary Path";
            $_SESSION["install"]["paths"]["path_dsmadmc"]["desc"] = "The path to the TSM admin client binary.";
            $which_dsmadmc = $tsmmonitor->findPath("dsmadmc", $config["search_path"]);
            if (isset($tsmmonitor->configarray["settings"]["paths"]["path_dsmadmc"])) {
                $_SESSION["install"]["paths"]["path_dsmadmc"]["default"] = $tsmmonitor->configarray["settings"]["path_dsmadmc"];
            } else if (!empty($which_dsmadmc)) {
                $_SESSION["install"]["paths"]["path_dsmadmc"]["default"] = $which_dsmadmc;
            } else {
                $_SESSION["install"]["paths"]["path_dsmadmc"]["default"] = "dsmadmc";
            }
            
            // php/php5 binary path
            $_SESSION["install"]["paths"]["path_php"]["name"] = "PHP Binary Path";
            $_SESSION["install"]["paths"]["path_php"]["desc"] = "The path to the PHP binary.";
            $which_php = $tsmmonitor->findPath("php", $config["search_path"]);
            if(!isset($which_php)) {
                $which_php = $tsmmonitor->findPath("php5", $config["search_path"]);
            }
            if (isset($tsmmonitor->configarray["settings"]["paths"]["path_php"])) {
                $_SESSION["install"]["paths"]["path_php"]["default"] = $tsmmonitor->configarray["settings"]["path_php"];
            } else if (!empty($which_php)) {
                $_SESSION["install"]["paths"]["path_php"]["default"] = $which_php;
            } else {
                $_SESSION["install"]["paths"]["path_php"]["default"] = "php";
            }
            
            // Logfile path
            $_SESSION["install"]["paths"]["path_tmlog"]["name"] = "TSM Monitor Logfile Path";
            $_SESSION["install"]["paths"]["path_tmlog"]["desc"] = "The path to the TSM Monitor log file.";
            if (isset($tsmmonitor->configarray["settings"]["paths"]["path_tmlog"])) {
                $_SESSION["install"]["paths"]["path_tmlog"]["default"] = $tsmmonitor->configarray["settings"]["path_tmlog"];
            } else {
                $_SESSION["install"]["paths"]["path_tmlog"]["default"] = $config["base_path"] . "tsmmonitor.log";
            }
            
            // PollD logfile path
            $_SESSION["install"]["paths"]["path_polldlog"]["name"] = "PollD Logfile Path";
            $_SESSION["install"]["paths"]["path_polldlog"]["desc"] = "The path to the PollD log file.";
            if (isset($tsmmonitor->configarray["settings"]["paths"]["path_polldlog"])) {
                $_SESSION["install"]["paths"]["path_polldlog"]["default"] = $tsmmonitor->configarray["settings"]["path_polldlog"];
            } else {
                $_SESSION["install"]["paths"]["path_polldlog"]["default"] = $config["base_path"] . "tsmmonitor.log";
            }
        }
    }
    // Refresh of binary and logfile path page or server definition page
    elseif ($_REQUEST["step"] == "40") {
        if ($_POST["refresh"] == "Refresh") {
            $_REQUEST["step"] = "40";
        } else {
            // Get server entries already in db
            $sql = "SELECT * FROM cfg_servers";
            $srvres = $adodb->fetchArrayDB($sql);
            $_REQUEST["step"] = "50";
        }
        foreach ($_SESSION["install"]["paths"] as $name => $array) {
            if (isset($_POST[$name])) {
                $_SESSION["install"]["paths"][$name]["default"] = $_POST[$name];
            }
        }
    }
    // Refresh/add on server definition page or finish page
    elseif ($_REQUEST["step"] == "50") {
        // Get server entries already in db
        $sql = "SELECT * FROM cfg_servers";
        $srvres = $adodb->fetchArrayDB($sql);

        if ($_POST["addsrv"] == "Add") {
            $_REQUEST["step"] = "50";
            // Get (possibly) updated values from the forms
            if (isset($_POST)) {
                $tmp_err = "";
                foreach ($_POST as $key => $val) {
                    if (ereg("^srv_.*", $key)) {
                        if (($key == "srv_description") || ($val != "")) {
                            if ($key == "srv_servername") {
                                $server[$key] = strtoupper($val);
                            } else {
                                $server[$key] = $val;
                            }
                        } else {
                            $tmp_key = ereg_replace("^srv_(.*)", "\\1", $key);
                            $tmp_err .= " ".(($tmp_key == "ip" ) ? strtoupper($tmp_key) : ucfirst($tmp_key));
                        }
                    }
                }
                if ($tmp_err != "") {
                    $input_err = "Missing parameter: ".$tmp_err;
                }
            }

            $sql = "SELECT * FROM cfg_servers WHERE servername='".$server['srv_servername']."'";
            $srvadd = $adodb->fetchArrayDB($sql);
            if (isset($srvadd[0])) {
                $input_err = "Server already configured in database.";
            }

            if ($input_err == "") {
                $dsmadmc = $_SESSION["install"]["paths"]["path_dsmadmc"]["default"];
                if (file_exists($dsmadmc) && is_executable($dsmadmc)) {
                    $popen_flags = ($os == "win32") ? 'rb' : 'r';
                    $oh = popen($dsmadmc." -se=".$server['srv_servername']." -id=".$server['srv_username']." -password=".$server['srv_password']." -TCPServeraddress=".$server['srv_ip']." -COMMMethod=TCPIP -TCPPort=".$server['srv_port']." -dataonly=yes -TAB \"SELECT SERVER_HLA,SERVER_LLA FROM status\" ", "$popen_flags");
                    if ($oh != 0) {
                        while (!feof($oh)) {
                            $read = fgets($oh, 4096);
                            if (ereg("^ANS.*", $read)) {
                                $input_err .= "$read ";
                            }
                        }
                    } else {
                        $input_err = "Cannot open connection to the TSM server. Check the servername,<br>username, password and the server entries in dsm.sys or dsm.opt.";
                    }
                    pclose($oh);
                } else {
                    $input_err = "$dsmadmc not found or not executeable.";
                }

                if ($input_err == "") {
                    $_SESSION["install"]["servers"][$server['srv_servername']] = $server;
                }
            }
        } elseif (!isset($_SESSION['install']['servers']) && !isset($srvres[0])) {
            $_REQUEST["step"] = "50";
        } else {
            $_REQUEST["step"] = "80";
        }
    }
    // Update page
    elseif ($_REQUEST["step"] == "60") {
        $_REQUEST["step"] = "80";
    }
    // Refresh on php limits page or finish and flush data to db
    elseif ($_REQUEST["step"] == "80") {
        if ($_POST["refresh"] == "Refresh") {
            $_REQUEST["step"] = "80";
        } else {
            $_REQUEST["step"] = "90";
        }
    }
}

if ($_REQUEST["step"] == "90") {
    // Flush updated data to DB
    foreach ($_SESSION["install"]["paths"] as $name => $array) {
        $adodb->updateDB('cfg_config', array(confkey => "$name", confval => $array["default"], description => $array["name"]), 'confkey');
    }
    if (isset($_SESSION["install"]["servers"])) {
        foreach ($_SESSION["install"]["servers"] as $name => $array) {
            $sqlcols = array();
            $sqlvals = array();
            foreach ($array as $col => $val) {
                $tmp_col = ereg_replace("^srv_(.*)", "\\1", $col);
                $tmp_col = "`".$tmp_col."`";
                $tmp_val = "'".$val."'";
                array_push($sqlcols, $tmp_col);
                array_push($sqlvals, $tmp_val);
            }
            $sql = "INSERT INTO cfg_servers (".(implode(",", $sqlcols)).") VALUES (".(implode(",", $sqlvals)).")";
            $adodb->execDB($sql);
        }
    }
    $adodb->updateDB('cfg_users', array(username => 'admin', password => $_SESSION["install"]["adminpw"]), 'username');
    
    // set new version, disable installer and redirect to login page
    $adodb->updateDB('cfg_config', array(confkey => 'version', confval => $config['tsm_monitor_version']), 'confkey');
    $adodb->closeDB();
    $_SESSION["stylesheet"] = "";
    header("Location: index.php");
    exit;
} elseif (($_REQUEST["step"] == "60") && ($_REQUEST["install_type"] == "20")) {
    // If the version is not found, die
    if (!is_int($old_version_index)) {
        echo $error_head;
        echo "
      <p style='font-size: 12px;'>Invalid TSM Monitor version
        <strong>$old_tsm_monitor_version</strong>, cannot upgrade to <strong>".$config["tsm_monitor_version"]."</strong>
      </p>";
        echo $page_foot;
        exit;
    }

    // Loop over all versions up to the current and perform incremental updates
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
                        <?php // Installation Step 10 (Greeting)
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
                        <?php // Installation Step 20 (Install/Update select)
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
                        <?php // Installation Step 30 (Admin password)
                            } elseif ($_REQUEST["step"] == "30") {
                        ?>

                        <p>
                          The default administrative TSM Monitor user '<b>admin</b>' needs a initial password.
                          Please choose a password according to your password policies and enter it below.
                          The default '<b>admin</b>' user has full rights to the TSM Monitor application, which
                          is why we recommend using a non-trivial and secure password. Additional users
                          with less privileges can be created from within TSM Monitor after this install
                          process is finished.
                        </p> 
                        <p align="center">
                        <?php
                            if ($input_err != "") {
                                echo "<span style='color:red'><b>ERROR: </b>$input_err</span>";
                            }
                        ?>
                        </p> 
                        <table width="90%" align="center" border=0>
                          <tr class='d0'>
                            <td>Password</td>
                            <td><input type='password' name='adminpw'/></td>
                          </tr>
                          <tr class='d0'>
                            <td>Password (repeat)</td>
                            <td><input type='password' name='adminpwr'/></td>
                          </tr>
                        </table>
                        <?php // Installation Step 40 (Paths)
                            } elseif ($_REQUEST["step"] == "40") {
                        ?>

                        <p>
                          Please check if the following values have been correctly determined for your
                          system and correct if necessary.
                        </p>
                        <?php
                            foreach ($_SESSION["install"]["paths"] as $name => $array) {
                                if (isset($_SESSION["install"]["paths"][$name])) {
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

                        <?php // Installation Step 50 (Servers)
                            } elseif ($_REQUEST["step"] == "50") {
                        ?>

                        <p>
                          Defined TSM servers in database:
                        </p>

                        <table width="90%" align="center" border=0>
                        <?php
                            $fields = array();
                            // Get server entries already in db
                            $sql = "SHOW COLUMNS FROM cfg_servers";
                            $srvresth = $adodb->fetchArrayDB($sql);

                            $th = "<tr>";
                            foreach ($srvresth as $col) {
                                if ($col['Field'] != "id") {
                                    if ($col['Field'] == "ip") {
                                        if ((isset($srvres)) && (count($srvres) != 0))
                                            $th .= "<th><b>".strtoupper($col['Field'])."</b></th>";
                                    } else {
                                        if ((isset($srvres)) && (count($srvres) != 0))
                                            $th .= "<th><b>".ucfirst($col['Field'])."</b></th>";
                                    }
                                    $fields[$col['Field']]['type'] = ereg_replace("([a-z]+)\(.*", "\\1", $col['Type']);
                                    $fields[$col['Field']]['len'] = ereg_replace("[a-z]+\(([0-9]+)\)", "\\1", $col['Type']);
                                }
                            }
                            $th .= "</tr>";
                            echo "$th";

                            if ((isset($srvres)) && (count($srvres) != 0)) {
                                foreach ($srvres as $row) {
                                    echo "<tr>";
                                    foreach ($row as $key => $val) {
                                        if ($key != "id") {
                                            if (($key == "libraryclient") || ($key == "default")) {
                                                if ($val == 0 ) {
                                                    echo "<td>No</td>";
                                                } else {
                                                    echo "<td>Yes</td>";
                                                }
                                            } else {
                                                echo "<td>".$val."</td>";
                                            }
                                        }
                                    }
                                    echo "</tr>";
                                }
                            } else {
                                echo "<td align='center'><span style='color:red'>No TSM server configured in database.</span></td>";
                            }
                        ?>

                        </table>
                        <p>
                          Defined TSM servers in the installer session cache:
                        </p>
                        <table width="90%" align="center" border=0>
                        <?php
                            if (is_array($_SESSION["install"]["servers"])) {
                                echo "$th";
                                foreach ($_SESSION["install"]["servers"] as $row) {
                                    echo "<tr>";
                                    foreach ($row as $key => $val) {
                                        if ($key != "id") {
                                            if (($key == "srv_libraryclient") || ($key == "srv_default")) {
                                                if ($val == 0 ) {
                                                    echo "<td>No</td>";
                                                } else {
                                                    echo "<td>Yes</td>";
                                                }
                                            } else {
                                                echo "<td>".$val."</td>";
                                            }
                                        }
                                    }
                                    echo "</tr>";
                                }
                            } else {
                                echo "<td align='center'><span style='color:red'>No TSM server configured in session cache.</span></td>";
                            }
                        ?>

                        </table>
                        <p>
                          <br>Please define at least one TSM server to be monitored:
                        </p>
                        <p align="center">
                        <?php
                            if ($input_err != "") {
                                echo "<span style='color:red'><b>ERROR: </b>$input_err</span>";
                            }
                        ?>
                        </p>
                        <table width="90%" align="center" border=0>
                        <?php
                            foreach ($srvresth as $col) {
                                if ($col['Field'] != "id") {
                                    echo "<tr class='d0'>";
                                    if ($col['Field'] == "ip") {
                                        echo "<td><b>".strtoupper($col['Field'])."</b></td>";
                                    } else {
                                        echo "<td><b>".ucfirst($col['Field'])."</b></td>";
                                    }

                                    if ($input_err != "") {
                                        $value = $_POST["srv_".$col['Field']];
                                    }
                                    if ($col['Field'] == "password") {
                                        echo "<td><input type='password' name='srv_".$col['Field']."' value='".$value."' /></td></tr>";
                                    } elseif (($col['Field'] == "libraryclient") || ($col['Field'] == "default")) {
                                        echo "<td><select name='srv_".$col['Field']."'><option value='0'";
                                        if ($value == 0) {
                                            echo " selected>No</option><option value='1'";
                                        } else {
                                            echo ">No</option><option value='1' selected";
                                        }
                                        echo ">Yes</option></select></td>";
                                    } else {
                                        echo "<td><input type='text' size='".($fields[$col['Field']]['len']+2)."' maxlength='".$fields[$col['Field']]['len']."' name='srv_".$col['Field']."' value='".$value."' /></td></tr>";
                                    }
                                    echo "</tr>";
                                }
                            }
                        ?>
                        </table>

                        <?php // Installation Step 60 (Updates)
                            } elseif ($_REQUEST["step"] == "60") {
                        ?>

                        <p>
                          Sorry, this is the initial TSM Monitor version, there are currently no
                          updates from previous versions available. Please choose "New install"
                          on the previous page.
                        </p>
                        <?php // Installation Step 80 (PHP Limits)
                            } elseif ($_REQUEST["step"] == "80") {
                        ?>

                        <p>
                          Default or configured PHP limits
                        </p>
                        <p>
                          <strong>PHP memory_limit settings</strong>:
                          <table width="90%" align="center" border="0">

                        <?php
                            $mem_recommend = 64;
                            $mem_limit = ini_get(memory_limit);
                            if ($mem_limit != "") {
                                $mem_val = ereg_replace("([0-9]*).*", "\\1", $mem_limit);
                                $mem_unit = ereg_replace("([0-9]*)(.*)", "\\2", $mem_limit);
                            } else {
                                $mem_val = "unknown";
                                $mem_unit = "";
                            }

                            $php_cli = $_SESSION["install"]["paths"]["path_php"]["default"]." -r 'echo ini_get(memory_limit);'";
                            $mem_cli_recommend = 64;
                            $mem_cli_limit = exec("$php_cli");
                            if ($mem_cli_limit != "") {
                                $mem_cli_val = ereg_replace("([0-9]*).*", "\\1", $mem_cli_limit);
                                $mem_cli_unit = ereg_replace("([0-9]*)(.*)", "\\2", $mem_cli_limit);
                            } else {
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

                        <p>
                          <strong>NOTE:</strong> Once you click "Finish", the previous settings will
                          be saved "as-is" to the TSM Monitor database. No further validation will
                          be performed, so please make sure the above settings are correct! Any of
                          the above settings can later on be changed with the TSM Monitor admin web
                          interface.
                          <br>
                          If you did choose to upgrade from a previous version of TSM Monitor, the
                          database will also be upgraded by clicking "Finish".
                        </p>
                        <?php } ?>

                      </td>
                    </tr>
                    <tr>
                      <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                      <td width="95%" align="right">
                        <p>
                        <?php if (($_REQUEST["step"] == "80") || ($_REQUEST["step"] == "40")) { ?>

                          <input style='display: block; width: auto; background: #eaeaea; margin-bottom: 2px; padding: 3px 30px 3px 30px; color: #000000; font-size: 11px; font-weight: bold; text-decoration: none; border: 1px solid #ffffff;' type="submit" name="refresh" value="Refresh">
                        <?php } elseif ($_REQUEST["step"] == "50") { ?>

                          <input style='display: block; width: auto; background: #eaeaea; margin-bottom: 2px; padding: 3px 30px 3px 30px; color: #000000; font-size: 11px; font-weight: bold; text-decoration: none; border: 1px solid #ffffff;' type="submit" name="addsrv" value="Add">
                        <?php } ?>

                        </p>
                      </td>

                      <td align="right">
                        <p>
                        <?php if ($_REQUEST["step"] == "80") { ?>

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
<?php echo $page_foot; ?>
