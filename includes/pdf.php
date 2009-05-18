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
 * pdf.php, TSM Monitor
 * 
 * provides a PDF generation class, requires FPDF
 * 
 * @author Michael Clemens
 * @package tsmmonitor
 */


require('../extlib/fpdf/fpdf.php');
require('global.php');

define('FPDF_FONTPATH','font/');



/**
 *
 * Class PDF
 *
 */

class PDF extends FPDF
{
	var $ProcessingTable=false;
	var $aCols=array();
	var $TableX;
	var $HeaderColor;
	var $RowColors;
	var $ColorIndex;


	/**
	 * Header - generates page header
	 *
	 */

	function Header()
	{
		//Print the table header if necessary
		if($this->ProcessingTable) $this->TableHeader();
		//Ensure table header is output
		parent::Header();
	}


        /**
         * PageHeader - writes page title on top of first page
         * 
         * @param string $pagetitle title of page
         * @access public
         * @return void
         */
        function PageHeader($pagetitle) {

		//Title
		$this->SetFont('Arial','',12);
		$this->Cell(0,6,$pagetitle,0,1,'C');
		$this->Ln(10);

	}

	/**
	 *
	 * TableHeader
	 *
	 */

	function TableHeader()
	{
		$this->SetFont('Arial','B',6);
		$this->SetX($this->TableX);
		$fill=!empty($this->HeaderColor);
		if($fill)
			$this->SetFillColor($this->HeaderColor[0],$this->HeaderColor[1],$this->HeaderColor[2]);
		foreach($this->aCols as $col)
			$this->Cell($col['w'],6,$col['c'],1,0,'C',$fill);
		$this->Ln();
	}


	/**
	 * Row
	 *
	 * @param unknown $date
	 */

	function Row($data)
	{
		$this->SetX($this->TableX);
		$ci=$this->ColorIndex;
		$fill=!empty($this->RowColors[$ci]);
		if($fill)
			$this->SetFillColor($this->RowColors[$ci][0],$this->RowColors[$ci][1],$this->RowColors[$ci][2]);
		foreach($this->aCols as $col)
			$this->Cell($col['w'],5,$data[$col['f']],1,0,$col['a'],$fill);
		$this->Ln();
		$this->ColorIndex=1-$ci;
	}



	/**
	 * CalcWidths
	 *
	 * @param string $width
	 * @param string $align
	 */

	function CalcWidths($width,$align)
	{
		//Compute the widths of the columns
		$TableWidth=0;
		foreach($this->aCols as $i=>$col)
		{
			$w=$col['w'];
			if($w==-1)
				$w=$width/count($this->aCols);
			elseif(substr($w,-1)=='%')
				$w=$w/100*$width;
			$this->aCols[$i]['w']=$w;
			$TableWidth+=$w;
		}
		//Compute the abscissa of the table
		if($align=='C')
			$this->TableX=max(($this->w-$TableWidth)/2,0);
		elseif($align=='R')
			$this->TableX=max($this->w-$this->rMargin-$TableWidth,0);
		else
			$this->TableX=$this->lMargin;
	}


	/**
	 * AddCol
	 *
	 * @param int $field
	 * @param int $width
	 * @param string $caption
	 * @param string $align
	 */

	function AddCol($field=-1,$width=-1,$caption='',$align='L')
	{
		//Add a column to the table
		if($field==-1)
			$field=count($this->aCols);
		$this->aCols[]=array('f'=>$field,'c'=>$caption,'w'=>$width,'a'=>$align);
	}


	/**
	 * Table
	 *
	 * @param string $query
	 * @param array $prop
	 */

	function Table($query,$prop=array(),$dbresult)
	{
		//Issue query
		//$res=fetchArrayDB($query, $DBconn);
		//Add all columns if none was specified
		if(count($this->aCols)==0)
		{
			foreach($dbresult[0] as $colname => $col) {
				$this->AddCol(-1,-1,ucfirst($colname));
			}
		}

		//Handle properties
		if(!isset($prop['width']))
			$prop['width']=0;
		if($prop['width']==0)
			$prop['width']=$this->w-$this->lMargin-$this->rMargin;
		if(!isset($prop['align']))
			$prop['align']='C';
		if(!isset($prop['padding']))
			$prop['padding']=$this->cMargin;
		$cMargin=$this->cMargin;
		$this->cMargin=$prop['padding'];
		if(!isset($prop['HeaderColor']))
			$prop['HeaderColor']=array();
		$this->HeaderColor=$prop['HeaderColor'];
		if(!isset($prop['color1']))
			$prop['color1']=array();
		if(!isset($prop['color2']))
			$prop['color2']=array();
		$this->RowColors=array($prop['color1'],$prop['color2']);
		//Compute column widths
		$this->CalcWidths($prop['width'],$prop['align']);
		//Print header
		$this->TableHeader();
		//Print rows
		$this->SetFont('Arial','',6);
		$this->ColorIndex=0;
		$this->ProcessingTable=true;
		foreach($dbresult as $key => $row) {
		$row_num = array_values($row);
		$row_comb = array_merge($row, $row_num);
		$this->Row($row_comb);
	}
		$this->ProcessingTable=false;
		$this->cMargin=$cMargin;
		$this->aCols=array();
	}
}


?>
