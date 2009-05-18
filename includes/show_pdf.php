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
 * showpdf.php, TSM Monitor
 * 
 * used for PDF popup window, calls class PDF
 * 
 * @author Michael Clemens
 * @package tsmmonitor
 */


require('global.php');
require('pdf.php');

define('FPDF_FONTPATH','font/');

$pdf=new PDF();
$pdf->Open();
$pdf->AddPage();
//First table: put all columns automatically
$prop=array('HeaderColor'=>array(180,180,180),
		'color1'=>array(255,255,255),
		'color2'=>array(230,230,230),
		'padding'=>2);
$res = $adodb->fetchArrayDB($_SESSION["lastsql"]);

//build Page Title
$configarray = $_SESSION["configarray"];
$queryarray = $configarray["queryarray"];
$qq = $_GET["q"];
$server = $_GET["s"];
$label = $queryarray[$qq]["label"];
$snapshotdate = $_SESSION['timemachine']['time'];
if ($snapshotdate != "") $snapshotdate = " (".strftime("%Y/%m/%d %H:%m", $snapshotdate).")";
$pageheader = $server.": ".$label." ".$snapshotdate;

$pdf->PageHeader($pageheader);
$pdf->Table($_SESSION["lastsql"],$prop,$res);
$pdf->Output();

?>
