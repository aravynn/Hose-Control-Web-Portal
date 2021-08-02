<?php

/** 
 *
 * PDF generator for the UL tester. Note: we'll need to use FPDF and $_GET for data 
 * retrieval. Note: FPDF only called here to limit includes.
 *
 */
 
require_once("config.php");
require_once("fpdf/fpdf.php");
 
$user = new user();
 
if($user->CheckLoggedIn()){
	if(!isset($_GET['id']) || $_GET['id'] == ''){
		echo 'ERROR: No ID Given';
	} else {
		// generate the PDF.
		$ht = new Hose();
		
		// get test information.
		$test = $ht->GetTestData($_GET['id']);
		$hose = $ht->getHoseData($test['HosePK']);
		

		
		
		$pdf = new FPDF('P', 'pt', 'Letter');
		$pdf->AddPage();
		$pdf->SetFont('Helvetica','',12);
		$pdf->SetAutoPageBreak(false);
		
		$headertop = makeHeader();
		
		$rightTop = makeChart($headertop, $test['Data'], $test['TargetLoad'], $test['MaxLoad']);
		//makeChart($top, $data, $targetPressure, $maxPressure)
		
		
		
		// testing array information.
		$testinfo = array(
			'Date' => $ht->DaypochToDate($test['Date']), 
			'Order #' => $test['OrderNumber'],  
			'Test #' => $test['TestNumber'], 
			'PO #' => $hose['PONumber'], 
			'Target Load' => $test['TargetLoad'], 
			'Peak Load' => $test['MaxLoad'], 
			'Load Hold Time' => $test['TargetLoadHoldTime'] . ' Seconds', 
			'Test Result' => $test['TestResult']
			);
		$leftTop = makeInfoArea('Testing Information', $testinfo, $headertop, 27);
		
		
		
		$hoseinfo = array(
			'Owner' => $hose['Company'],
			'Asset Desc' => $hose['Description'],
			'Asset #' => $hose['AssetNumber'],
			'Assembler' => $hose['CreatedBy'],
			'Assembly Date' =>  $ht->DaypochToDate($hose['MFGDate']),
			'Manufacturer' => $hose['Manufacturer'],
			'Model #' => $hose['PartNumber'],
			'Inner Diameter' => str_replace('&quot;', '"', $hose['InnerDiameter']),
			'Hose Length' => str_replace('&#039;', "'", $hose['OverallLength']),
			'Coupling A' => $hose['CouplingA']["Name"],
			'A Attachment' => $hose['CouplingA']["AttachMethod"],
			'Coupling B' => $hose['CouplingB']["Name"],
			'B Attachment' => $hose['CouplingB']["AttachMethod"],
			'Hose Notes' => $hose["Notes"]);
		
		$rightTop = $leftTop;
		$leftTop = makeInfoArea('Hose Information', $hoseinfo, $leftTop, 27);
		
		
		
		$addinfo = array(
			'Testing By' => $test['CreatedBy'],
			'Proof Test Type' => $test['ProofTestType'],
			'Working Pressure' => $hose['WorkingPres'],
			'Test Pressure' => $test["TargetLoad"],
			'Suggested Retest On:' => $ht->DaypochToDate($test["Date"] + 365),
			'Test Notes' => $test['Comments']
			);
		$rightTop = makeInfoArea('Additional Information', $addinfo, $rightTop, 320);
		
		
		
		
		$visualinfo = array(
			'Cover' => $test["VisualCover"],
			'Tube' => $test["VisualTube"],
			'Fittings' => $test["VisualFitting"],
			'Crimp/Clamp' => $test["VisualCrimp"],);
			
		$rightTop = makeInfoArea('Visual Inspection', $visualinfo, $rightTop, 320);
		
		
		
		makeFooter();
		

		if(count($test['Images']) > 0){
			
			for($i = 0; $i * 4 < count($test['Images']); $i++ ){
				// for each, send 4 images of the array. 
				
				$pdf->AddPage();

				$headertop = makeHeader();

				// set the images here. 
				// USING HOSE SAMPLE ID 3

				//var_dump($test['Images']);

				$count = (count($test['Images']) - $i * 4 >= 4 ? 4 : count($test['Images']) - $i * 4);

				placeTestImage(array_slice($test['Images'], $i * 4, $count, false));

				makeFooter();
			}
		}
		$pdf->Output();
	
	
	
	
	
	
	
	
	
	
	}
} else { 
	echo "ERROR: Not logged in";
}

// functions for PDF generator. 

function placeTestImage($images){
	// place the test images on the PDF. 
	global $pdf;

	/* // 4 boxes TEMP FOR PLACEMENT
	$pdf->Rect(18, 120, 278, 260, 'L');
	$pdf->Rect(316, 120, 278, 260, 'L');
	$pdf->Rect(18, 400, 278, 260, 'L');
	$pdf->Rect(316, 400, 278, 260, 'L'); */

	$x = array(18, 316, 18, 316);
	$y = array(120, 120, 400, 400);

	for($i = 0; $i < count($images) && $i < 4; $i++){
		// for each 4 images, place as per $x and $y
		
		$img = pngtojpeg($images[$i]); 

		$set = centerImage($img, $x[$i], $y[$i], 278, 260);
		$pdf->Image($img, $set['x'], $set['y'], $set['w'], $set['h']);

	}
	
	//$set = $this->centerImage($newjpeg1, 36, 36, 350, 325);
	//$this->Image($newjpeg1,$set['x'],$set['y'],$set['w'],$set['h']);

}


function makeHeader(){
	// place images etc in the header.
	global $pdf;
	
	// logo
	$pdf->Image('img/mepbro-pdf.png', 27, 27, 218);
	
	// title box
	$pdf->SetFillColor(51,102,255);
	$pdf->Rect(263, 27, 323, 72, 'F');
	// title lines
	$pdf->SetTextColor(255,255,255);
	$pdf->SetFont('Helvetica','B',34);
	$pdf->SetXY(263, 31);
	$pdf->Cell(323,36,'HOSE TEST', 0, 1, 'C');
	$pdf->SetXY(263, 64);
	$pdf->Cell(323,36,'CERTIFICATE', 0, 1, 'C');
	
	return 126;
	
}
function makeChart($top, $data, $targetPressure, $maxPressure){
	// place the chart in the top right 
	global $pdf;
	
	 // Pressure and temperature mins and maxes.
	$temps = array('min' => 100000000000, 'max' => -100000000000);
	$press = array('min' => 100000000000, 'max' => -100000000000);
	// take the DATA, and find MIN and MAX values for pressure and temperature.
	foreach($data as $d){
		if($d["Temperature"] < $temps['min']) { $temps['min'] = $d["Temperature"]; }
		if($d["Temperature"] > $temps['max']) { $temps['max'] = $d["Temperature"]; }
		if($d["Pressure"] < $press['min']) { $press['min'] = $d["Pressure"]; }
		if($d["Pressure"] > $press['max']) { $press['max'] = $d["Pressure"]; }
	} 
	
	$temps['min'] *= 0.9;
	$temps['max'] *= 1.15;
	$press['min'] *= 0.9;
	$press['max'] *= 1.15;
	
	// new code - Will create a dynamic chart, using the given test pressure to 
	// dynamically generate a chart. 
	
	$pdf->SetLineWidth(0);
	$pdf->SetDrawColor(0, 0, 0);
	// draw x axis
	$pdf->Line(330, 267, 570, 267);
	// draw y axis + edge bar
	$pdf->Line(570, $top, 570, 267);
	$pdf->Line(330, $top, 330, 267);
	
	// draw cross lines, 8 across for "10" spaces
	$pdf->SetDrawColor(150, 150, 150);
	
	for($i = 24; $i < 225; $i += 24){
		// vertical lines
		$pdf->Line($i + 330, $top, $i + 330, 270);
		
	}
	
	$h = 267 - $top;
	
	for($i = 0; $i < $h; $i += $h / 5){
		// horizontal lines
		$pdf->Line(327, $i + $top, 573, $i + $top);
	}
	
	// add horizontal numbers + description
	$pdf->SetDrawColor(0, 0, 0);
	$pdf->SetFont('Helvetica','',5);
	$pdf->SetTextColor(0,0,0);
	
	for($i = 0; $i < 11; $i++){
		// place 1 number at each spot.
		$pdf->SetXY(325 + ($i * 24) , 267);
		$pdf->Cell(10, 14, ceil((count($data) / 10) * $i) , 0, 2, 'C', false);
	}	
	
	// add vertical numbers, Pressure on left, temperature on right. 
	for($i = 0; $i < 6; $i++){
		$pdf->SetXY(318 , 260 - ($i * ($h / 5)));
		$pdf->Cell(10, 14, ceil(((($press['max'] - $press['min']) / 5) * $i + $press['min']) * 10) / 10 , 0, 2, 'R', false);
		
		$pdf->SetXY(572 , 260 - ($i * ($h / 5)));
		$pdf->Cell(10, 14, ceil(((($temps['max'] - $temps['min']) / 5) * $i + $temps['min']) * 10) / 10 , 0, 2, 'L', false);
	}
	
	
	// add vertical bar description
	$pdf->SetFillColor(255, 255, 255);
	$pdf->SetTextColor(150, 150, 150);
	$pdf->SetFont('Helvetica','',5);
	$pdf->Rect(331, $top + 1, 35, 12, 'F');
	$pdf->SetXY(331 , $top + 4);
	$pdf->Cell(100, 6, "Pressure (PSI)" , 0, 2, 'L', false);
	
	$pdf->Rect(531, $top + 1, 35, 12, 'F');
	$pdf->SetXY(526 , $top + 4);
	$pdf->Cell(100, 6, "Temperature (C)" , 0, 2, 'L', false);
	
	// add horizontal bar description
	$pdf->SetXY(330 , 279);
	
	$pdf->Cell(250, 6, "Time (Seconds)" , 0, 2, 'C', false);
	
	$mp = $press['max'] - $press['min'];
	$mt = $temps['max'] - $temps['min'];	
	
	// draw min pressure lines (100%)
	$lineAt = ((($mp - ($targetPressure - $press['min'])) / $mp) * (267 - $top)) + $top;
	$pdf->SetDrawColor(0, 200, 0);
	$pdf->Line(330, $lineAt, 570, $lineAt);
	
	
	// draw max pressure lines (110%)
	$lineAt = ((($mp - ($maxPressure - $press['min'])) / $mp) * (267 - $top)) + $top;
	$pdf->SetDrawColor(200, 0, 0);
	$pdf->Line(330, $lineAt, 570, $lineAt);
	

	for($i = 1; $i < count($data); $i++){
		// draw the line from point-1 to point 
		
		$pdf->SetDrawColor(0, 0, 0);	
		$pdf->Line(
				(241 * (($i - 1) / count($data))) + 330, 
				((($mp - ($data[$i - 1]['Pressure'] - $press['min'])) / $mp) * (267 - $top)) + $top,
				(241 * ($i / count($data))) + 330, 
				((($mp - ($data[$i]['Pressure'] - $press['min'])) / $mp) * (267 - $top)) + $top);
		
		$pdf->SetDrawColor(0, 0, 255);
		$pdf->Line(
				(241 * (($i - 1) / count($data))) + 330, 
				((($mt - ($data[$i - 1]['Temperature'] - $temps['min'])) / $mt) * (267 - $top)) + $top,
				(241 * ($i / count($data))) + 330, 
				((($mt - ($data[$i]['Temperature'] - $temps['min'])) / $mt) * (267 - $top)) + $top);
	}
	
	
	$pdf->SetLineWidth(0);
	return 304;
}
function makeInfoArea($title, $info, $top, $left){
	global $pdf;
	// we'll use a gap of 3 for the outer edge
	
	// full width 96. - 6 for edges is 90. 
	
	$height = 9;
	$lwidth = 0;
	$pdf->SetFont('Helvetica','B',10);
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFillColor(238, 238, 238);
	$pdf->SetDrawColor(200,200,200);
	foreach($info as $k => $i){
		// get the longest title to create the title left panel
		$str = $pdf->GetStringWidth($k);
		$lwidth = (($lwidth < $str) ? $str : $lwidth);
	}
	
	// go through again, and place everything, one by one. both sides to be done simultaneously.
	
	$first = true;
	
	foreach($info as $k => $i){
		
		// place header
		$pdf->SetXY($left + 9, $height + $top);
		$pdf->SetFont('Helvetica','B',9);
		$pdf->SetFillColor(238, 238, 238);
		$pdf->Cell($lwidth + 11,20,' ' . $k, 0, 1, 'L', true);
		
		// line across top.
		if(!$first){
			$pdf->line($left + 9, $top + $height, $left + 252, $top + $height);
		}
		
		// place content
		
		// check for width, if an issue we'll need to place a rect on the side
		// and break the line into pieces. 
		if($pdf->GetStringWidth($i) > 252 - ($lwidth + 23)){
			// this needs to be adjusted.
			$pdf->SetFont('Helvetica','',10);
			$ln = ceil($pdf->GetStringWidth($i) / (252 - ($lwidth + 23))); // determine number of lines for multicell.
			$pdf->SetXY($left + $lwidth + 23, $height + $top + 4);
			$pdf->MultiCell((252 - ($lwidth + 23)), 12, $i);
			
			
			
			$pdf->SetFillColor(238, 238, 238);
			$pdf->Rect($left + 9, $top + $height + 17, $lwidth + 11, 12 * $ln, 'F');
			
			
			$height += 12 * $ln;
			
			
			
		} else {
			// standard output.
			$pdf->SetXY($left + $lwidth + 20, $height + $top);
			$pdf->SetFont('Helvetica','',9);
			
			if($k == 'Test Result'){
				if($i == 'PASS'){
					$pdf->SetFillColor(0, 255, 0);
				} else {
					// assume fail
					$pdf->SetFillColor(255, 0, 0);
				}
				$pdf->Cell(252 - ($lwidth + 20),20,' ' . $i, 0, 1, 'L', true);
			} else {
				$pdf->Cell(252 - ($lwidth + 20),20,' ' . $i, 0, 1, 'L', false);
			}
		}
		
		$height += 18;
		$first = false;
	}
	
	// create the box and header last, surrounding everything.
	$pdf->SetFont('Helvetica','',8);
	$pdf->SetTextColor(0,0,0);
	$pdf->SetDrawColor(0,0,0);
	$pdf->Rect($left, $top, 261, $height + 7, 'D');
	$pdf->SetFillColor(255, 255, 255);
	$pdf->Rect($left + 9, $top - 1, $pdf->GetStringWidth($title) + 11, 6, 'F');
	
	$pdf->SetXY($left + 11,$top - 2);
	$pdf->Cell($left + 11, 4, $title, 0, 1, 'L', false);
	
	return $top + $height + 26;
}
function makeFooter(){
	// place images etc for the footer.
	global $pdf;
	$pdf->Image('img/continental-pdf.png', 27, 697, 142);
	$pdf->Image('img/star-pdf.png', 218, 683, 57);
	$pdf->Image('img/nahad-pdf.png', 320, 686, 85);
	$pdf->Image('img/ifps-pdf.png', 456, 686, 128);
	
	
	$pdf->line(27, 750, 585, 750);
	// draw the footer information
	$pdf->SetFont('Helvetica','',10);
	$pdf->SetTextColor(0,0,0);
	
	$pdf->SetXY(27, 755);
	$pdf->Cell(150,10,'1.877.632.4118', 0, 1, 'L');
	$pdf->SetXY(177, 755);
	$pdf->Cell(258,10,'725 Century St. Wpg, MB, R3H 0M2', 0, 1, 'C');
	$pdf->SetXY(435, 755);
	$pdf->Cell(150,10,'mepbrothers.com', 0, 1, 'R');
	
}

// NEW IMAGE HANDLING FUNCTIONS

function pngtojpeg($orgimage, $filename = false){
	// take the PNG file, and return the jpeg formatted image URL 
	// notice: this keeps a replication of the image in the folder. This may become cumbersome over time. 
	// HOWEVER - these files can also be deleted at any time, they have no requirement of being kept. 
	
	if (exif_imagetype($orgimage) == IMAGETYPE_PNG) {
		
		if(!$filename){
			// no file name specified
		$key = '';
		$keys = array_merge(range(0, 9), range('a', 'z'));

		for ($i = 0; $i < 5; $i++) {
			$key .= $keys[array_rand($keys)];
		}
	
	
		$newimgpath = 'tmppng/' . $key . time() . '.jpg';
		} else {
			// use a predefined file name
			$newimgpath = 'tmppng/' . $filename . '.jpg';
		}
		
		
		
		$image = imagecreatefrompng($orgimage);
		$bg = imagecreatetruecolor(imagesx($image), imagesy($image));
		imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
		imagealphablending($bg, TRUE);
		imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
		imagedestroy($image);
		$quality = 80; // 0 = worst / smaller file, 100 = better / bigger file 
		imagejpeg($bg, $newimgpath, $quality);
		imagedestroy($bg);
		
		return $newimgpath;
	
	} else {
		
		return $orgimage;
		
	}

}

function centerImage($image, $xmin, $ymin, $width, $height){
	// returns 4 values, x,y,w,h
	// this will center an image within the specified values, 
	// at maximum size to fill the area.
	$sizing = getimagesize($image); // 0 is width, 1 is height
		
		if($sizing[0] > $sizing[1]){
			// wider than taller.
			$xoffset = $xmin;
			
			$setwidth = $width;
			$setheight = round($width/$sizing[0]*$sizing[1]);
			
			$yoffset = floor(($height - $setheight) / 2) + $ymin;
			
			if($setheight > $height){
				// image is too large for contraints. re-adjust for width. 
				$yoffset = $ymin;
			
				$setheight = $height;
				$setwidth = round($height/$sizing[1]*$sizing[0]);
				
				$xoffset = floor(($width - $setwidth) / 2) + $xmin;
			}
			
		} else {
			// taller than wider
			$yoffset = $ymin;
			
			$setheight = $height;
			$setwidth = round($height/$sizing[1]*$sizing[0]);
			
			$xoffset = floor(($width - $setwidth) / 2) + $xmin;
			
			if($setwidth > $width){
				$xoffset = $xmin;
			
				$setwidth = $width;
				$setheight = round($width/$sizing[0]*$sizing[1]);
				
				$yoffset = floor(($height - $setheight) / 2) + $ymin;
			}
		}
		return array(
			'x' => $xoffset,
			'y' => $yoffset,
			'w' => $setwidth,
			'h' => $setheight);
}