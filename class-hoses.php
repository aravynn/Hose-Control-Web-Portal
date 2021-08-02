<?php 

/**
 *
 * Hose Class functions
 * Will manage: 
 * Displaying Hoses
 * Hose Data
 * 
 *
 */

// prevent direct access
if(count(get_included_files()) ==1){
 	 http_response_code(403);
 	 exit("ERROR 403: Direct access not permitted.");
}

class Hose {
	function __construct(){
	}
	
	function GetHoseData($ID){ // RETURNS ARRAY
		// take the given ID of the hose, and get all of the associated data. 
		// this will get ALL associated data and return it as an array, from the DB
		
		$sql = new sqlControl();
		
		// get the main hose information
		$sql->sqlCommand('SELECT * FROM Hoses WHERE PK = :pk LIMIT 1', array(':pk' => $ID), false);
		$hoseret = $sql->returnResults();
		
		// using the hose information, get the template information
		$sql->sqlCommand('SELECT * FROM HoseTemplates WHERE PK = :pk LIMIT 1', array(':pk' => $hoseret['TemplatePK']), false);
		$template = $sql->returnResults();
		
		foreach($template as $k => $a){
			// add each value to the final hose information, making sure to not overwrite values.
			if(!isset($hoseret[$k])){
				$hoseret[$k] = $a;
			}
		}
		
		// get the coupling information
		$sql->sqlCommand('SELECT * FROM FittingTemplates WHERE PK = :pk LIMIT 1', array(':pk' => $hoseret['CouplingAPK']), false);
		$hoseret['CouplingA'] = $sql->returnResults();
		
		$sql->sqlCommand('SELECT * FROM FittingTemplates WHERE PK = :pk LIMIT 1', array(':pk' => $hoseret['CouplingBPK']), false);
		$hoseret['CouplingB'] = $sql->returnResults();
		
		$sql->sqlCommand('SELECT CompanyName FROM Companies WHERE PK = :pk LIMIT 1', array(':pk' => $hoseret['OwnerPK']), false);
		$hoseret['Company'] = $sql->returnResults();
		$hoseret['Company'] = $hoseret['Company']['CompanyName']; // reduce this.
		return $hoseret;
	}
	
	function GetHoseImage($id){
		// get the hose image based on the template data. 
		$sql = new sqlControl();
		$sql->sqlCommand('SELECT Image FROM HoseTemplates WHERE PK = :pk LIMIT 1', array(':pk' => $id), false);
		$tempret = $sql->returnResults();

		return $tempret['Image'];
	}

	function GetTestData($ID){ // RETURNS ARRAY
		// Get the hose test data. Note that we'll only get the required data and nothing else.
		$sql = new sqlControl();
		
		// get the main hose information
		$sql->sqlCommand('SELECT * FROM HoseTests WHERE PK = :pk LIMIT 1', array(':pk' => $ID), false);
		$testret = $sql->returnResults();
		
		// get the test data.
		$sql->sqlCommand('SELECT Temperature, Pressure, IntervalNumber FROM TestData WHERE testPK = :pk', array(':pk' => $testret['PK']), false);
		
		
		$data = $sql->returnAllResults();
		
		$testret['Data'] = array();
		
		foreach($data as $d){
			// for each element, add the values to the data element.
			$testret['Data'][$d['IntervalNumber']] = array('Temperature' => $d['Temperature'],
															'Pressure' => $d['Pressure']);
		}
		
		// get any imags associated with the test. 
		$sql->sqlCommand('SELECT Image FROM TestImages WHERE TestPK = :pk', array(':pk' => $ID), false);
		$img = $sql->returnAllResults();
		
		$testret['Images'] = array();

		foreach($img as $k => $im){
			
			$testret['Images'][] = $im['Image'];
			
		}

		return $testret;
	}
	
	function GetHosesForOwner($ID, $limit = false, $offset = false){ // RETURNS 1D ARRAY
	
		// get a list of PK's for the hoses created by the owner. 
		// this will return an array of ID's
		$sql = new sqlControl();
		
		$stmt = 'SELECT PK FROM Hoses WHERE OwnerPK = :pk';
		$arr = array(':pk' => $ID);
		
		if($limit > 0 && is_numeric($limit)){
 			$stmt .= ' LIMIT ' . filter_var($limit, FILTER_SANITIZE_NUMBER_INT);
 			
 			if($offset > 0 && is_numeric($offset)){
 				$stmt .= ' OFFSET ' . filter_var($offset, FILTER_SANITIZE_NUMBER_INT);
 			}
 		}
		
		
		$sql->sqlCommand($stmt, $arr, false);
		
		$results = $sql->returnAllResults(); // get the results for the user. 
		
		$return = array();
		foreach($results as $k => $a){
			
				$return[] = $a['PK'];
			
			
		}
		
		return $return;
		
	}
	
	function GetTestsForHose($ID){ // RETURNS iD ARRAY
		// get a list of PK's for the test associated with a hose.
		$sql = new sqlControl();
		$sql->sqlCommand('SELECT PK FROM HoseTests WHERE HosePK = :pk', array(':pk' => $ID), false);
		
		$results = $sql->returnAllResults(); // get the results for the user. 
		
		$return = array();
		foreach($results as $k => $a){
			
				$return[] = $a['PK'];
			
		}
		
		return $return;
		
	}
	
	function DaypochToDate($days){ //RETURN STRING
	
		$year = floor($days / 365.25);
		
		$days -= floor($year * 365.25);
		
		//$feb = ;
		
		$mlist = array(31, ($year % 4 == 0 ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		
		$month = 1;
		
		
		
		for($i = 0; $i < 12; $i++){
			
			if($days > $mlist[$i]){
				
				$days -= $mlist[$i];
				
			} else {
				
				$month += $i;
				break;
				
			}
			
		}
		
		return $month . '/' . $days . '/' . $year;
		
	
	}	
	
}