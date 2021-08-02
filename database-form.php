<?php 

/**
 *
 * View Hoses Page
 * load via INCLUDE at page load.
 *
 */
 
 if(count(get_included_files()) ==1){
 	 http_response_code(403);
 	 exit("ERROR 403: Direct access not permitted.");
}

require_once("config.php");

if($_SESSION['TYPE'] != 0){
	die('Permission Denied');
}

?>

<h2> Edit Database </h2>

<form method="get" action="index.php" >
					<label for="t">Select Table</label>
					<select name="t" id="t" />
						<option value="c">Companies</option>
						<option value="f">FittingTemplates</option>
						<option value="ht">HoseTemplates</option>
					</select><br />
					<label for="l">Enter Line To Edit</label>
					<input type="text" name="l" id="l" placeholder="Blank for new line" />
					<input type="hidden" name="p" value="database" /><br />
					<input type="submit" value="Load" />
				</form>


<?php loadForm(); ?>





<?php 
 // functions for loading and displaying hoses that exist for this company. 
 
function loadForm(){
	// load the appropriate form for the user
	// this will subdelegate to individual functions for each table.
	
	if(isset($_GET['l']) && $_GET['l'] != ''){
		$line = intval($_GET['l']);
		$subval = 'Update';
	} else {
		$line = false;
		$subval = 'Insert';
	}
	
	echo '<form method="post" action="authenticate.php">';
	
	if(isset($_GET['t'])){
		// get the current table, and load the appropriate function.
		switch($_GET['t']){
			case 'c': // Company
				loadCompanyForm($line);
				break;
			case 'f': // Fitting Tmeplate
				loadFittingForm($line);
				break;
			/*case 'h': // hose
				loadHoseForm($line);
				break;*/
			case 'ht': // Hose Template
				loadHoseTemplateForm($line);
				break;
			/*case 't': // Hose test.
				loadHoseTestForm($line);
				break;*/
			default:
				break;
		}
		
	}
	
	echo '	<input type="submit" value="'. $subval . '" />
			</form>';
	
}

function loadCompanyForm($id){
	// load a company form, will need to get the company, all contacts, 
	// contact points, and locations
	
	echo '<p>COMPANY NOT COMPLETE</p>';
	
	// default form for now. NOTE: we won't be including forms for locations or contacts. 
	// this will update the billing address, billing location, main contact and contactpoints.
	
	
	if($id !== false){
		// if an ID is set, load a hidden token to update the value
		$admin = new admin();
		echo '<input type="hidden" name="dbid" value="' . filter_var($id, FILTER_SANITIZE_NUMBER_INT) . '" />';
		$data = $admin->getCompanyByID($id);
		
		$dflag = true;
	} else { 
		// otherwise, add a token to add the values as a new line. 
		echo '<input type="hidden" name="dbid" value="false" />';
		$dflag = false;
	}


	echo '	<h3>Company Table</h3>
			<label for="CompanyName">CompanyName</label><input type="text" id="CompanyName" name="CompanyName" ' . ($dflag ? 'Value="' . $data['CompanyName'] . '"' : '') . ' /><br />
			<label for="ContactName">ContactName</label><input type="text" name="ContactName" id="ContactName" ' . ($dflag ? 'Value="' . $data['Contact']['Name'] . '"' : '') . ' /><br />';
			
	if($dflag){
		echo '<input type="hidden" name="ContactPK" Value="' . $data['Contact']['PK'] . '" />';
	} else {
		echo '<input type="hidden" name="ContactPK" Value="false" />';
	}

	$phones = 1;
	$emails = 1;
	foreach($data['ContactPoint'] as $d){
		if(array_key_exists("phone", $d)){
			// phone number field.
			echo '<label for="Phone' . $phones . '">Phone' . $phones . '</label><input type="text" name="Phone' . $phones . '" id="Phone' . $phones . '" value="' . $d['phone'] . '" /><br />
			<input type="hidden" name="Phone' . $phones . 'PK" Value="' . $d['PK'] . '" />';
			
			$phones++;

		} else {
			// email field. 
			echo '<label for="Email' . $emails . '">Email' . $emails . '</label><input type="text" name="Email' . $emails . '" id="Email' . $emails . '" value="' . $d['email'] . '" /><br />
			<input type="hidden" name="Email' . $emails . 'PK" Value="' . $d['PK'] . '" />';

			$emails++;
		}
	}

	for(; $phones < 3; $phones++){
		// add blank lines for each unadded phone number. 
		echo '<label for="Phone' . $phones . '">Phone' . $phones . '</label><input type="text" name="Phone' . $phones . '" id="Phone' . $phones . '" /><br />
			<input type="hidden" name="Phone' . $phones . 'PK" Value="false" />';
	}

	for(; $emails < 3; $emails++){
		// add blank lines for each unadded phone number. 
		echo '<label for="Email' . $emails . '">Email' . $emails . '</label><input type="text" name="Email' . $emails . '" id="Email' . $emails . '" /><br />
			<input type="hidden" name="Email' . $emails . 'PK" Value="false" />';
	}
	
	echo '	<label for="BillAddr1">BillAddr1</label><input type="text" id="BillAddr1" name="BillAddr1" ' . ($dflag ? 'Value="' . $data['BillAddr1'] . '"' : '') . ' /><br />
			<label for="BillAddr2">BillAddr2</label><input type="text" id="BillAddr2" name="BillAddr2" ' . ($dflag ? 'Value="' . $data['BillAddr2'] . '"' : '') . ' /><br />
			<label for="BillCity">BillCity</label><input type="text" id="BillCity" name="BillCity" ' . ($dflag ? 'Value="' . $data['BillCity'] . '"' : '') . ' /><br />
			<label for="BillProvince">BillProvince</label><input type="text" id="BillProvince" name="BillProvince" ' . ($dflag ? 'Value="' . $data['BillProvince'] . '"' : '') . ' /><br />
			<label for="BillPostal">BillPostal</label><input type="text" id="BillPostal" name="BillPostal" ' . ($dflag ? 'Value="' . $data['BillPostal'] . '"' : '') . ' /><br />
			<label for="BillCountry">BillCountry</label><input type="text" id="BillCountry" name="BillCountry" ' . ($dflag ? 'Value="' . $data['BillCountry'] . '"' : '') . ' /><br />
			<label for="Notes" id="noteslabel">Notes</label><textarea id="Notes" name="Notes"> ' . ($dflag ? $data['Notes'] : '') . '</textarea><br />
			<input type="hidden" name="action" value="companydb" />
			';

	
}

function loadFittingForm($id){
	// load the fitting form
	
	if($id !== false){
		// if an ID is set, load a hidden token to update the value
		echo '<input type="hidden" name="dbid" value="' . filter_var($id, FILTER_SANITIZE_NUMBER_INT) . '" />';
		$admin = new admin();
		$data = $admin->getFittingTemplateByID($id);

		$dflag = true;
	} else { 
		// otherwise, add a token to add the values as a new line. 
		echo '<input type="hidden" name="dbid" value="false" />';
		$dflag = false;
	}
	
	echo '	<h3>Fitting Template Table</h3>
			<label for="Name">Name</label><input type="text" id="Name" name="Name" ' . ($dflag ? 'Value="' . $data['Name'] . '"' : '') . ' /><br />
			<label for="PartNumber">PartNumber</label><input type="text" id="PartNumber" name="PartNumber" ' . ($dflag ? 'Value="' . $data['PartNumber'] . '"' : '') . ' /><br />
			<label for="Description">Description</label><input type="text" id="Description" name="Description" ' . ($dflag ? 'Value="' . $data['Description'] . '"' : '') . ' /><br />
			<label for="AttachMethod">AttachMethod</label><input type="text" id="AttachMethod" name="AttachMethod" ' . ($dflag ? 'Value="' . $data['AttachMethod'] . '"' : '') . ' /><br />
			<input type="hidden" name="action" value="fittingtemplatedb" />';

	
}

function loadHoseTemplateForm($id){
	// load the hose template form
	
	$admin = new admin();
	$output = '';

	if($id !== false){
		// if an ID is set, load a hidden token to update the value
		$output .= '<input type="hidden" name="dbid" value="' . filter_var($id, FILTER_SANITIZE_NUMBER_INT) . '" />';
		$data = $admin->getHoseTemplateByID($id);

		$dflag = true;
	} else { 
		// otherwise, add a token to add the values as a new line. 
		$output .= '<input type="hidden" name="dbid" value="false" />';
		$dflag = false;
	}

	// load the fitting information for the dropdown. 
	$fittingdataraw = $admin->getFittingTemplates();
	
	$output .= '<label for="PartNumber">PartNumber</label><input type="text" id="PartNumber" name="PartNumber" ' . ($dflag ? 'Value="' . $data['PartNumber'] . '"' : '') . ' /><br />
			<label for="Name">Name</label><input type="text" id="Name" name="Name" ' . ($dflag ? 'Value="' . $data['Name'] . '"' : '') . ' /><br />
			<label for="Manufacturer">Manufacturer</label><input type="text" id="Manufacturer" name="Manufacturer" ' . ($dflag ? 'Value="' . $data['Manufacturer'] . '"' : '') . ' /><br />
			<label for="Description">Description</label><input type="text" id="Description" name="Description" ' . ($dflag ? 'Value="' . $data['Description'] . '"' : '') . ' /><br />
			<label for="DistributorRef">DistributorRef</label><input type="text" id="DistributorRef" name="DistributorRef" ' . ($dflag ? 'Value="' . $data['DistributorRef'] . '"' : '') . ' /><br />
			<label for="InnerDiameter">InnerDiameter</label><input type="text" id="InnerDiameter" name="InnerDiameter" ' . ($dflag ? 'Value="' . $data['InnerDiameter'] . '"' : '') . ' /><br />
			<label for="OverallLength">OverallLength</label><input type="text" id="OverallLength" name="OverallLength" ' . ($dflag ? 'Value="' . $data['OverallLength'] . '"' : '') . ' /><br />
			<label for="CutLength">CutLength</label><input type="text" id="CutLength" name="CutLength" ' . ($dflag ? 'Value="' . $data['CutLength'] . '"' : '') . ' /><br />
			<label for="WorkingPres">WorkingPres</label><input type="text" id="WorkingPres" name="WorkingPres" ' . ($dflag ? 'Value="' . $data['WorkingPres'] . '"' : '') . ' /><br />
			<label for="TestPres">TestPres</label><input type="text" id="TestPres" name="TestPres" ' . ($dflag ? 'Value="' . $data['TestPres'] . '"' : '') . ' /><br />
			<label for="TestTime">TestTime</label><input type="text" id="TestTime" name="TestTime" ' . ($dflag ? 'Value="' . $data['TestTime'] . '"' : '') . ' /><br />
			<label for="CouplingAPK">CouplingAPK</label><select name="CouplingAPK" id="CouplingAPK">';
			
			foreach($fittingdataraw as $f){
				// for each create an <option> and add it to the queue. 
				
				$output .= '<option value="' . $f['PK'] . '"' . ($f['PK'] == $data['CouplingAPK'] ? ' selected="selected"' : '') . '>' . $f['Name'] . '</option>';

			}

	$output .=  '</select><br />
			<label for="CouplingBPK">CouplingBPK</label><select name="CouplingBPK" id="CouplingBPK">';
			
			foreach($fittingdataraw as $f){
				// for each create an <option> and add it to the queue. 
				
				$output .= '<option value="' . $f['PK'] . '"' . ($f['PK'] == $data['CouplingBPK'] ? ' selected="selected"' : '') . '>' . $f['Name'] . '</option>';

			}

	$output .=  '</select><br />
			<label for="Image">Image</label><input type="text" id="Image" name="Image" ' . ($dflag ? 'Value="' . $data['Image'] . '"' : '') . ' /><br />
			<label for="Notes" id="noteslabel">Notes</label><textarea id="Notes" name="Notes"> ' . ($dflag ? $data['Notes'] : '') . '</textarea><br />
			<input type="hidden" name="action" value="hosetemplatedb" />';


	echo $output;

}

// DEPRECATED - DO NOT BOTHER.

function loadHoseForm($id){
	// load the hose form. Note, this will ONLY edit the hose data, template etc.
	// are loaded and edited separate
	
	echo '<p>HOSE FORM NOT COMPLETE</p>';
	
	
	
	echo ' 	<label for="AssetNumber">AssetNumber</label><input type="text" id="AssetNumber" name="AssetNumber" /><br />
			<label for="ChipID">ChipID</label><input type="text" id="ChipID" name="ChipID" /><br />
			<label for="OwnerPK">OwnerPK</label><br />
			<label for="locationPK">locationPK</label><br />
			<label for="TemplatePK ">TemplatePK </label><br />
			<label for="MFGDate">MFGDate</label><input type="text" id="MFGDate" name="MFGDate" /><br />
			<label for="EXPDate">EXPDate</label><input type="text" id="EXPDate" name="EXPDate" /><br />
			<label for="CreatedBy">CreatedBy</label><input type="text" id="CreatedBy" name="CreatedBy" /><br />
			<label for="PONumber">PONumber</label><input type="text" id="PONumber" name="PONumber" /><br />
			<label for="CustomerIDNumber">CustomerIDNumber</label><input type="text" id="CustomerIDNumber" name="CustomerIDNumber" /><br />
			<label for="DoRetest">DoRetest</label><input type="checkbox" id="DoRetest" name="DoRetest" /><br />
			<label for="HoseStatus">HoseStatus</label><input type="text" id="HoseStatus" name="HoseStatus" /><br />
			<label for="OverallLength">OverallLength</label><input type="text" id="OverallLength" name="OverallLength" /><br />
			<label for="CutLength">CutLength</label><input type="text" id="CutLength" name="CutLength" /><br />
			<label for="WorkingPres">WorkingPres</label><input type="text" id="WorkingPres" name="WorkingPres" /><br />
			<label for="TestPres">TestPres</label><input type="text" id="TestPres" name="TestPres" /><br />
			<label for="CouplingAPK">CouplingAPK</label><br />
			<label for="CouplingBPK">CouplingBPK</label><br />
			<label for="AttachA">AttachA</label><input type="text" id="AttachA" name="AttachA" /><br />
			<label for="AttachB">AttachB</label><input type="text" id="AttachB" name="AttachB" /><br />
			<label for="Notes" id="noteslabel">Notes</label><textarea id="Notes" name="Notes"></textarea><br />
			<input type="hidden" name="action" value="hosesdb" />';
			
			

	
}
function loadHoseTestForm($id){
	// load the test, as well as image links for the test. will NOT allow data to be edited.
	
	echo '<p>HOSE TEST NOT COMPLETE</p>';
	
	//<label for=""></label><input type="text" id="" name="" /><br />
	
	echo '	<label for="HosePK">HosePK</label><br />
			<label for="OrderNumber">OrderNumber</label><input type="text" id="OrderNumber" name="OrderNumber" /><br />
			<label for="Date">Date</label><input type="text" id="Date" name="Date" /><br />
			<label for="ProofTestType">ProofTestType</label><input type="text" id="ProofTestType" name="ProofTestType" /><br />
			<label for="TargetLoad">TargetLoad</label><input type="text" id="TargetLoad" name="TargetLoad" /><br />
			<label for="MaxLoad">MaxLoad</label><input type="text" id="MaxLoad" name="MaxLoad" /><br />
			<label for="TargetLoadHoldTime">TargetLoadHoldTime</label><input type="text" id="TargetLoadHoldTime" name="TargetLoadHoldTime" /><br />
			<label for="Elongation">Elongation</label><input type="text" id="Elongation" name="Elongation" /><br />
			<label for="TestNumber"></label><input type="text" id="TestNumber" name="TestNumber" /><br />
			<label for="OwnerPK">OwnerPK</label><br />
			<label for="CreatedBy">CreatedBy</label><input type="text" id="CreatedBy" name="CreatedBy" /><br />
			<label for="TestResult">TestResult</label><input type="text" id="TestResult" name="TestResult" /><br />
			<label for="Connectivity">Connectivity</label><input type="text" id="Connectivity" name="Connectivity" /><br />
			<label for="Comments" id="noteslabel">Comments</label><textarea id="Comments" name="Comments"></textarea><br />
			<label for="VisualCover">VisualCover</label><input type="text" id="VisualCover" name="VisualCover" /><br />
			<label for="VisualTube">VisualTube</label><input type="text" id="VisualTube" name="VisualTube" /><br />
			<label for="VisualFitting">VisualFitting</label><input type="text" id="VisualFitting" name="VisualFitting" /><br />
			<label for="VisualCrimp">VisualCrimp</label><input type="text" id="VisualCrimp" name="VisualCrimp" /><br />
			<label for="TestType">TestType</label><input type="text" id="TestType" name="TestType" /><br />
			<input type="hidden" name="action" value="hosetestdb" />';

}