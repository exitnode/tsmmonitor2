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
 *************************************************************************/

/**
 *
 * page_head.php, TSM Monitor
 *
 * page header
 *
 * @author Frank Fegert
 * @package tsmmonitor
 */

if ($_POST["css"] != "") $_SESSION['stylesheet'] = $_POST["css"];
if ($_POST["tabletype"] != "") $_SESSION["tabletype"] = $_POST["tabletype"];
if ($_POST["DebugMode"] != "") $_SESSION["debug"] = $_POST["DebugMode"];
$adodb->setDebug($_SESSION["debug"]);

?>
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<!-- Begin: page_head.php -->
<html>
	<head>
		<title>TSM Monitor</title>
		<meta http-equiv='content-type' content='text/html; charset=ISO-8859-1'>
			<link rel='shortcut icon' href='images/favicon.ico' type='image/x-icon'>
			<link rel='icon' href='images/favicon.ico' type='image/x-icon'>
			<link rel="stylesheet" type="text/css" href="css/print.css" media="print"> 
			<link rel='stylesheet' type='text/css' href='css/layout.css'>
			<link rel='stylesheet' type='text/css' href='css/<?php echo $_SESSION['stylesheet']; ?>'>
			<script type="text/javascript" src="extlib/datechooser.js"></script>
			<script type="text/javascript" src="includes/functions.js"></script>
			<script type="text/javascript">
			<!-- //
			events.add(window, 'load', WindowLoad);

			function WindowLoad()
			{
				var datepicker = document.getElementById('datechooser');
				var objLateDate = new Date();
				var objStartDate = new Date();

				objStartDate.setMonth(<?php if ($_SESSION['timemachine']['date']!= "") echo strftime("%m", $_SESSION['timemachine']['date']); ?>);
				objStartDate.setDate(<?php if ($_SESSION['timemachine']['date']!= "") echo strftime("%d", $_SESSION['timemachine']['date']); ?>);
				objStartDate.setYear(<?php if ($_SESSION['timemachine']['date']!= "") echo strftime("%Y", $_SESSION['timemachine']['date']); ?>);

				objLateDate.setMonth(objLateDate.getMonth());
				datepicker.DateChooser = new DateChooser();
				datepicker.DateChooser.setXOffset(5);
				datepicker.DateChooser.setYOffset(-5);
				datepicker.DateChooser.setStartDate(objStartDate);
				datepicker.DateChooser.setLatestDate(objLateDate);
				datepicker.DateChooser.setUpdateField('dateinput', 'Y/m/d');
				datepicker.DateChooser.setIcon('images/datechooser.png', 'dateinput');

				return true;
			}

			function genPDF() {
				window.open( "includes/show_pdf.php?q=<?php echo $_SESSION['GETVars']['qq'] ?>&s=<?php echo $_SESSION['GETVars']['server'] ?>", "myWindow", "status = 1, fullscreen=yes,scrollbars=yes" )
			}


			// -->
			</script>
		</meta>
	</head>

	<body>
		<div id="inhalt">
			<table cellspacing="4" cellpadding="2" border="0" id="design">
			<!-- End: page_head.php -->
