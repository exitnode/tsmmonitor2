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
    <td id="tnleft" width="160"></td>
    <td id="tnright"width="740" height="30" align="right">

</td>
<?php } ?>
</tr>
<tr>
<?php if ($_SESSION["logindata"]["loggedin"]) { ?>
<!-- Start left cik navigation menu -->
    <td id="menue">
        <div class="menuelinks">
        <?php echo getMenu( $adminmenu, "admin.php?q=".$GETVars['qq']."&m=".$GETVars['menu'], "admin" );  ?>
        </div>
    <br>
        <img src="/images/trans.gif" alt="" width="150" height="1" border="0"><br>
    </td>
<!-- End left cik navigation menu -->
<?php } ?>
    <td id="content">
<?php

// main content, right of menu
if (isset($_SESSION["logindata"]["user"]) && isset($_SESSION["logindata"]["pass"]) && $GETVars['qq'] != "logout" && $_SESSION["logindata"]["loggedin"]) {
    if ($GETVars['qq'] != "" && $GETVars['qq'] != "overview") {

        // show overview page
        if ($GETVars['qq'] == "index") {
            //include_once "includes/overview.php" ;
        } else {

            if ( ($_GET['action'] != "" && ($_GET['action'] == "edit" && $_GET['id'] != "")) || $_POST['Add'] == "Add") {
                $i = 0;
                // show Add New Entry Form
                if ($_POST['Add'] == "Add") {
                    $sqlth = "SHOW COLUMNS from cfg_".$GETVars['qq'];
                    $sqlresth = fetchArrayDB($sqlth, $conn);
                    echo "<form action=".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']." method='post'>";
                    echo "<table class='zebra'>";
                    echo "<tr><th>Key</th><th>Value</th></tr>";
                    foreach ($sqlresth as $col) {
echo "TEST: ".$col['Field']." -> $colval<br>\n";
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
                                echo "<td><b>".$col['Field']."</b></td><td><input type='text' name='txt".$col['Field']."' value='' /></td></tr>";
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
                    $tablearray = getAdminTables("edit");
                    echo "<form action=".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']." method='post'>";
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
                                echo "<td><b>".$keycell."</b></td><td><input type='password' name='txt".$keycell."' value='' /></td></tr>";
                            } else if ($keycell == "id") {
                                $id = $valcell;
                            } else {
                                echo "<td><b>".$keycell."</b></td><td><input type='text' name='txt".$keycell."' value='".$valcell."' /></td></tr>";
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
                        execDB($sql, $conn);
                    }
                // Process update of an existing item or insert of a new one
                } else if ($_POST['EditSave'] == "Save" || $_POST['AddSave'] == "Save") {
                    $sqlth = "SHOW COLUMNS from cfg_".$_GET['q'];
                    $sqlresth = fetchArrayDB($sqlth, $conn);
                    $colarray = array();
                    $colarray['id'] = $_POST['id'];
                    $set = "";
                    $sqlcols = "";
                    $sqlvals = "";

                    // get all table fields to be selected
                    foreach ($sqlresth as $col) {
                        if ($col['Field'] != "id") {
                            if ($col['Field'] == "password") {
                                if ($_POST["txt".$col['Field']] != "") {
                                    $val = md5($_POST["txt".$col['Field']]);
                                } else {
                                    $val = "";
                                }
                            } else {
                                $val = $_POST["txt".$col['Field']];
                            }
                            if ($val != "") {
                                if ($_POST['AddSave'] == "Save") {
                                    $colarray["`".$col['Field']."`"] = $val;
                                    $sqlcols .= $col['Field'];
                                    $sqlvals .= "'".$val."'";
                                    $sqlcols .= ", ";
                                    $sqlvals .= ", ";
                                } else if ($_POST['EditSave'] == "Save") {
                                    $colarray["`".$col['Field']."`"] = $val;
                                    $set .= $col['Field']."='".$val."'";
                                    $set .= ", ";
                                }
                            }
                        }
                    }
                    $sqlcols = ereg_replace(", $", "", $sqlcols);
                    $sqlvals = ereg_replace(", $", "", $sqlvals);
                    if ($_POST['AddSave'] == "Save") {
                        $sql = "INSERT into cfg_".$_GET['q']." (".$sqlcols.") values (".$sqlvals.")";
                    } else if ($_POST['EditSave'] == "Save") {
                        $sql = "UPDATE cfg_".$_GET['q']." set ".$set." where id='".$_POST['id']."' LIMIT 1";
                    }
                    updateDB("cfg_".$_GET['q'], $colarray, 'id', $conn);
                }
                echo "<form action=".$_SERVER['PHP_SELF']."?q=".$GETVars['qq']."&m=".$GETVars['menu']." method='post'>";
                echo "<table class='zebra'>";
                echo getTableheader();
                echo getAdminTables("list");
                $nav = showPageNavigation("40");
                if ($nav!="") {
                    echo "<tr><td colspan='0' align='center' class='footer'><a class='navhead'>".$nav."</a></td></tr>";
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
$_SESSION['from'] = $GETVars['qq'];
session_write_close(void); 
?>

</tr>

<?php if ($_SESSION["logindata"]["loggedin"]) include_once "includes/footer.php";  ?>

</table>

</div>
</body>
</html>
