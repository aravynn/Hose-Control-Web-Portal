<?php

/**
 *
 * Admin Class functions
 * Will manage: 
 * users
 * items
 * 
 *
 */
 // prevent direct access
if(count(get_included_files()) ==1){
 	 http_response_code(403);
 	 exit("ERROR 403: Direct access not permitted.");
}
 
 
 class admin {
 	function __construct(){
		if(!isset($_SESSION)) session_start();
 	}
 	
 	private function CheckPrivilage(){
 		// check users privilage level. return a numerical value to determine 
 		// what level of control required to access function.
 		return $_SESSION['TYPE'];
 			
 	}
 	
 	public function GetCompanies(){
 		// get all companies from the companies table, and return. 
 		$sql = new sqlControl();
 		
 		$sql->sqlCommand('SELECT PK, CompanyName as Name FROM Companies', array(), false);
		
		return $sql->returnAllResults(); // get the results for the companies. 
 		
 	}
 	
 	public function AddUser($username, $password, $CompanyID){
 		$sql = new sqlControl();
 		if($this->CheckPrivilage() != 0){ 
 			$sql->logAction($_SESSION['USER'], "User attempted to create new user, but did not have permission.");
 			return -100; 
 		}
 		
 		// return results: 
 		// -100 bad access.
 		// 0 OK
 		// -1 bad username
 		// -2 bad password
 		// -3 invalid entries
 		
 		$UserType = 0;
 		
 		if($CompanyID > -1){
 			$UserType = 1;
 		} 
 		
 		if($username == '' || $password == ''){
 			// this is not a valid entry. 
 			$sql->logAction($_SESSION['USER'], "User attempted to create a new user, but had incomplete information. 
 			Username: " . $username);
 			return -3; // 
 		}
 		
 		
		$sql->sqlCommand('SELECT PK FROM Users WHERE User = :User', array(':User' => $username), false);
		
		$results = count($sql->returnAllResults()); // get the results for the user. 
 		
 		if($results > 0){
 			$sql->logAction($_SESSION['USER'], "User attempted to create a new user, but had chose an existing username. 
 			Username: " . $username);
 			return -1; // this username exists, get a new one! 
 		}
 		
 		
 		// add user to file.
 		$newUser = new user();
 		
 		if(count($newUser->CheckPasswordStrength($password)) > 0){
 		$sql->logAction($_SESSION['USER'], "User attempted to create a new user, but set an insecure password. 
 			Username: " . $username);
 			return -2;
 		}
 		
 		
 		$ID = $newUser->NewUser($username, $password, $CompanyID, $UserType);
 		
 		$sql->logAction($_SESSION['USER'], "User Successfully created: 
 			Username: " . $username );
 		return 0;
	 }
	 
	private function addToQueue($table, $PK, $type){ // VOID RETURN
		// add the information to the queue, for other users will also need to add this as well. 
		// obviously, do not add an insert for the current user. 
		$sql = new sqlControl();	
		
		// get a list of current user installations 
		$sql->sqlCommand('SELECT User FROM Users WHERE UserType = 0', array(), false);
		
		$return = $sql->returnAllResults(); 
		
		// for each user, add a queue
		foreach($return as $r){
			$sql->sqlCommand('INSERT INTO DataSync (TableName, TablePK, Type, User) VALUES (:TableName, :TablePK, :Type, :User)',
								array(':TableName' => $table, ':TablePK' => $PK, ':Type' => $type, ':User' => $r['User']), false);
		}
	} 


	public function getLocationsByCompany($id){
		// return shorthand list of locations for dropdowns. 
		$sql = new sqlControl();

		$sql->sqlCommand("SELECT PK, ShipName FROM Locations WHERE CompanyPK = :PK", array(":PK" => $id), false);
	 
		return $sql->returnAllResults();

	}

	public function getCompanyByID($id){
		// return the company data, contact data, contact points for user at ID. 
		$sql = new sqlControl();

		// main company data first.
		$sql->sqlCommand("SELECT * FROM Companies WHERE PK = :PK LIMIT 1", array(":PK" => $id), false);
		
		$dataret = $sql->returnResults(); // get the copany main data. 

		// next, get the contact. 
		$sql->sqlCommand("SELECT PK, Name FROM Contacts WHERE CompanyPK = :PK LIMIT 1", array(":PK" => $id), false);
		
		$dataret['Contact'] = $sql->returnResults(); // we'll need PK's for the next part. 

		// get the contact points for the contact. 
		$sql->sqlCommand("SELECT PK, List, Type FROM ContactPoint WHERE ContactPK = :PK", array(":PK" => $dataret['Contact']['PK']), false);

		$dat = $sql->returnAllResults(); // we'll need a fair amount for the next part. 

		$dataret['ContactPoint'] = array(); // define blank array

		foreach($dat as $d){
			// enter each as it's type with name/PK. 
			$dataret['ContactPoint'][] = array('PK' => $d['PK'], $d['Type'] => $d['List']);

		}


		return $dataret; // return array for use in the main function. 

	}

	public function getFittingTemplates(){ // RETURN ARRAY
		// get all fitting templates.
		$sql = new sqlControl();

		$sql->sqlCommand("SELECT PK, Name FROM FittingTemplates", array(), false);
	 
		return $sql->returnAllResults();
	}

	public function getFittingTemplateByID($id){ // RETURN ARRAY
		// get the fitting template, and return to the function. 
		$sql = new sqlControl();

		$sql->sqlCommand("SELECT * FROM FittingTemplates WHERE PK = :PK LIMIT 1", array(":PK" => $id), false);
	 
		return $sql->returnResults();
	}

	public function insertFittingTemplate($name, $part, $desc, $attach){
		// insert a new fitting template
		// check authentication
		if($this->CheckPrivilage() == 0){
			// perform this update. 
			$sql = new sqlControl();

			$sql->sqlCommand("INSERT INTO FittingTemplates (Name, PartNumber, Description, AttachMethod) VALUES (:Name, :PartNumber, :Description, :AttachMethod)", 
			array(":Name" => $name, ":PartNumber" => $part, ":Description" => $desc, ":AttachMethod" => $attach), false);
			
			$pk = $sql->lastInsert();

			$this->addToQueue("FittingTemplates", $pk, 0);

			$sql->logAction($_SESSION['USER'], "User added new fitting template: " . $part);			
		} else {
			$sql->logAction($_SESSION['USER'], "User attempted to add fitting template, but did not have permission.");
		}
	}

	public function updateFittingTemplate($id, $name, $part, $desc, $attach){
		// update an existing fitting template. 
		// check authentication. 
		if($this->CheckPrivilage() == 0){
			$sql = new sqlControl();
			
			$sql->sqlCommand("UPDATE FittingTemplates SET Name = :Name, PartNumber = :PartNumber, Description = :Description, AttachMethod = :AttachMethod WHERE PK = :PK", 
			array(":Name" => $name, ":PartNumber" => $part, ":Description" => $desc, ":AttachMethod" => $attach, ':PK' => $id), false);
			
			$this->addToQueue("FittingTemplates", $id, 1);

			$sql->logAction($_SESSION['USER'], "User updated fitting template: " . $part);	
		} else {
			$sql->logAction($_SESSION['USER'], "User attempted to update fitting template: " . $part . " but did not have permission");	
		}
	}

	public function getHoseTemplateByID($id){
		// return the hose template information for ID
		$sql = new sqlControl();

		$sql->sqlCommand("SELECT * FROM HoseTemplates WHERE PK = :PK LIMIT 1", array(":PK" => $id), false);
	 
		return $sql->returnResults();
	}

	public function updateHoseTemplate(
		$PK, $PartNumber, $Name, $Manufacturer, $Description, $DistributorRef,
		$InnerDiameter, $OverallLength, $CutLength, $WorkingPres, $TestPres,  
		$TestTime, $CouplingAPK, $CouplingBPK, $Image, $Notes )
	{
		// update existing hose template
		// check authentication
		if($this->CheckPrivilage() == 0){
			$sql = new sqlControl();
				
			$retarr = array(
				":PK" => $PK,
				":PartNumber" => $PartNumber,
				":Name" => $Name,
				":Manufacturer" => $Manufacturer,
				":Description"  => $Description,
				":DistributorRef" => $DistributorRef,
				":InnerDiameter"  => $InnerDiameter,
				":OverallLength"  => $OverallLength,
				":CutLength" => $CutLength,
				":WorkingPres" => $WorkingPres,
				":TestPres" => $TestPres,
				":TestTime" => $TestTime,
				":CouplingAPK" => $CouplingAPK,
				":CouplingBPK" => $CouplingBPK,
				":Image" => $Image,
				":Notes" => $Notes
			);

			$sql->sqlCommand("UPDATE HoseTemplates SET PartNumber = :PartNumber, Name = :Name, Manufacturer = :Manufacturer, Description = :Description, DistributorRef = :DistributorRef, InnerDiameter = :InnerDiameter, OverallLength = :OverallLength, CutLength = :CutLength, WorkingPres = :WorkingPres, TestPres = :TestPres, TestTime = :TestTime, CouplingAPK = :CouplingAPK, CouplingBPK = :CouplingBPK, Image = :Image, Notes = :Notes WHERE PK = :PK", $retarr, false);
				
			$this->addToQueue("HoseTemplates", $PK, 1);

			$sql->logAction($_SESSION['USER'], "User updated hose template: " . $PartNumber);	
		} else {
			$sql->logAction($_SESSION['USER'], "User attempted to update hose template: " . $PartNumber . " but did not have permission.");	
		}
	}

	public function insertHoseTemplate(
		$PartNumber, $Name, $Manufacturer, $Description, $DistributorRef,
		$InnerDiameter, $OverallLength, $CutLength, $WorkingPres, $TestPres,  
		$TestTime, $CouplingAPK, $CouplingBPK, $Image, $Notes )
		{
		// insert new hose template
		// check authentication. 

		if($this->CheckPrivilage() == 0){
			// perform this update. 
			$sql = new sqlControl();
			
			$retarr = array(
				":PartNumber" => $PartNumber,
				":Name" => $Name,
				":Manufacturer" => $Manufacturer,
				":Description"  => $Description,
				":DistributorRef" => $DistributorRef,
				":InnerDiameter"  => $InnerDiameter,
				":OverallLength"  => $OverallLength,
				":CutLength" => $CutLength,
				":WorkingPres" => $WorkingPres,
				":TestPres" => $TestPres,
				":TestTime" => $TestTime,
				":CouplingAPK" => $CouplingAPK,
				":CouplingBPK" => $CouplingBPK,
				":Image" => $Image,
				":Notes" => $Notes
			);

			$sql->sqlCommand("INSERT INTO HoseTemplates ( PartNumber, Name, Manufacturer, Description, DistributorRef, InnerDiameter, OverallLength, CutLength, WorkingPres, TestPres, TestTime, CouplingAPK, CouplingBPK, Image, Notes) VALUES ( :PartNumber, :Name, :Manufacturer, :Description, :DistributorRef, :InnerDiameter, :OverallLength, :CutLength, :WorkingPres, :TestPres, :TestTime, :CouplingAPK, :CouplingBPK, :Image, :Notes )", $retarr, false);
			
			$pk = $sql->lastInsert();

			$this->addToQueue("HoseTemplates", $pk, 0);

			$sql->logAction($_SESSION['USER'], "User added new hose template: " . $PartNumber);	
		} else {
			$sql->logAction($_SESSION['USER'], "User attempted to add hose template but did not have permissions.");	
		}
	}

	public function updateCompany($PK, $CompanyName, $ContactName, $ContactPK, $Phone1, 
		$Phone1PK, $Phone2, $Phone2PK, $Email1, $Email1PK, $Email2, $Email2PK, $BillAddr1, 
		$BillAddr2, $BillCity, $BillProvince, $BillPostal, $BillCountry, $Notes){
		// update existing company for ID. 
		// Authenticate User
		if($this->CheckPrivilage() == 0){
			$sql = new sqlControl();
			
			$retarr = array(
				':PK' => $PK,
				':CompanyName' => $CompanyName, 
				':BillAddr1' => $BillAddr1, 
				':BillAddr2' => $BillAddr2, 
				':BillCity' => $BillCity, 
				':BillProvince' => $BillProvince, 
				':BillPostal' => $BillPostal, 
				':BillCountry' => $BillCountry, 
				':Notes' => $Notes 
			);

			// update main company
			$sql->sqlCommand("UPDATE Companies SET CompanyName = :CompanyName, BillAddr1 = :BillAddr1, BillAddr2 = :BillAddr2, BillCity = :BillCity, BillProvince = :BillProvince, BillPostal = :BillPostal, BillCountry = :BillCountry, Notes = :Notes WHERE PK = :PK", $retarr, false);
			
			// update contact name
			$sql->sqlCommand("UPDATE Contacts SET Name = :Name WHERE PK = :PK", array(':Name' => $ContactName, ':PK' => $ContactPK), false);

			// update contact points. 
			if($Phone1PK !== 'false'){ 
				$sql->sqlCommand("UPDATE ContactPoint SET List = :List WHERE PK = :PK", array(':List' => $Phone1, ':PK' => $Phone1PK), false); 
			} elseif($Phone1 != ''){ 
				$sql->sqlCommand("INSERT INTO ContactPoint (ContactPK, List, Type) VALUES (:ContactPK, :List, :Type)", array(':ContactPK' => $ContactPK, ':List' => $Phone1, ':Type' => 'phone'), false); 
				$last = $sql->lastInsert();
				$this->addToQueue("ContactPoint", $last, 0);
			}

			if($Phone2PK !== 'false'){ 
				$sql->sqlCommand("UPDATE ContactPoint SET List = :List WHERE PK = :PK", array(':List' => $Phone2, ':PK' => $Phone2PK), false); 
			} elseif($Phone2 != ''){ 
				$sql->sqlCommand("INSERT INTO ContactPoint (ContactPK, List, Type) VALUES (:ContactPK, :List, :Type)", array(':ContactPK' => $ContactPK, ':List' => $Phone2, ':Type' => 'phone'), false); 
				$last = $sql->lastInsert();
				$this->addToQueue("ContactPoint", $last, 0);
			}

			if($Email1PK !== 'false'){ 
				$sql->sqlCommand("UPDATE ContactPoint SET List = :List WHERE PK = :PK", array(':List' => $Email1, ':PK' => $Email1PK), false); 
			} elseif($Email1 != ''){ 
				$sql->sqlCommand("INSERT INTO ContactPoint (ContactPK, List, Type) VALUES (:ContactPK, :List, :Type)", array(':ContactPK' => $ContactPK, ':List' => $Email1, ':Type' => 'email'), false); 
				$last = $sql->lastInsert();
				$this->addToQueue("ContactPoint", $last, 0);
			}

			if($Email2PK !== 'false'){ 
				$sql->sqlCommand("UPDATE ContactPoint SET List = :List WHERE PK = :PK", array(':List' => $Email2, ':PK' => $Email2PK), false); 
			} elseif($Email2 != ''){ 
				$sql->sqlCommand("INSERT INTO ContactPoint (ContactPK, List, Type) VALUES (:ContactPK, :List, :Type)", array(':ContactPK' => $ContactPK, ':List' => $Email2, ':Type' => 'email'), false); 
				$last = $sql->lastInsert();
				$this->addToQueue("ContactPoint", $last, 0);
			}


			$billarr = array(
				':CompanyPK' => $PK,
				':ShipAddr1' => $BillAddr1, 
				':ShipAddr2' => $BillAddr2, 
				':ShipCity' => $BillCity, 
				':ShipProvince' => $BillProvince, 
				':ShipPostal' => $BillPostal, 
				':ShipCountry' => $BillCountry
			);
			
			// update billing location.
			$sql->sqlCommand("SELECT PK FROM Locations WHERE CompanyPK = :CompanyPK AND ShipName = 'Billing'", array(':CompanyPK' => $PK), false);
			
			$pk = $sql->returnResults();

			$sql->sqlCommand("UPDATE Locations SET ShipAddr1 = :ShipAddr1, ShipAddr2 = :ShipAddr2, ShipCity = :ShipCity, ShipProvince = :ShipProvince, ShipPostal = :ShipPostal, ShipCountry = :ShipCountry WHERE CompanyPK = :CompanyPK AND ShipName = 'Billing'", $billarr, false);
			
			// finally, add all sync lines
			$this->addToQueue("Companies", $PK, 1);
			$this->addToQueue("Contacts", $ContactPK, 1);
			$this->addToQueue("ContactPoint", $Phone1PK, 1);
			$this->addToQueue("ContactPoint", $Phone2PK, 1);
			$this->addToQueue("ContactPoint", $Email1PK, 1);
			$this->addToQueue("ContactPoint", $Email2PK, 1);
			$this->addToQueue("Locations", $pk['PK'], 1);

			$sql->logAction($_SESSION['USER'], "User updated company: " . $CompanyName);	
		} else {
			$sql->logAction($_SESSION['USER'], "User attempted to update company: " . $CompanyName . " but did not have permissions.");
		}
	}

	public function insertCompany($CompanyName, $ContactName, $ContactPK, $Phone1, $Phone1PK, 
		$Phone2, $Phone2PK, $Email1, $Email1PK, $Email2, $Email2PK, $BillAddr1, $BillAddr2, 
		$BillCity, $BillProvince, $BillPostal, $BillCountry, $Notes){
		// Insert a new company. 
		// Authenticate User
		if($this->CheckPrivilage() == 0){
			// insert main company
			$sql = new sqlControl();

			$retarr = array(
				':CompanyName' => $CompanyName, 
				':BillAddr1' => $BillAddr1, 
				':BillAddr2' => $BillAddr2, 
				':BillCity' => $BillCity, 
				':BillProvince' => $BillProvince, 
				':BillPostal' => $BillPostal, 
				':BillCountry' => $BillCountry, 
				':Notes' => $Notes 
			);

			$sql->sqlCommand("INSERT INTO Companies (CompanyName, BillAddr1, BillAddr2, BillCity, BillProvince, BillPostal, BillCountry, Notes) VALUES (:CompanyName, :BillAddr1, :BillAddr2, :BillCity, :BillProvince, :BillPostal, :BillCountry, :Notes)", $retarr, false);

			$cpk = $sql->lastInsert();

			// insert contact 
			$sql->sqlCommand("INSERT INTO Contacts (Name, CompanyPK) VALUES (:Name, :PK)", array(':Name' => $ContactName, ':PK' => $cpk), false);

			$custpk = $sql->lastInsert();

			// insert contact points. 
			if($Phone1 != ''){ 
				$sql->sqlCommand("INSERT INTO ContactPoint (ContactPK, List, Type) VALUES (:ContactPK, :List, :Type)", array(':ContactPK' => $custpk, ':List' => $Phone1, ':Type' => 'phone'), false); 
				$last = $sql->lastInsert();
				$this->addToQueue("ContactPoint", $last, 0);
			}

			if($Phone2 != ''){ 
				$sql->sqlCommand("INSERT INTO ContactPoint (ContactPK, List, Type) VALUES (:ContactPK, :List, :Type)", array(':ContactPK' => $custpk, ':List' => $Phone2, ':Type' => 'phone'), false); 
				$last = $sql->lastInsert();
				$this->addToQueue("ContactPoint", $last, 0);
			}
			if($Email1 != ''){ 
				$sql->sqlCommand("INSERT INTO ContactPoint (ContactPK, List, Type) VALUES (:ContactPK, :List, :Type)", array(':ContactPK' => $custpk, ':List' => $Email1, ':Type' => 'email'), false); 
				$last = $sql->lastInsert();
				$this->addToQueue("ContactPoint", $last, 0);
			}
			if($Email2 != ''){ 
				$sql->sqlCommand("INSERT INTO ContactPoint (ContactPK, List, Type) VALUES (:ContactPK, :List, :Type)", array(':ContactPK' => $custpk, ':List' => $Email2, ':Type' => 'email'), false); 
				$last = $sql->lastInsert();
				$this->addToQueue("ContactPoint", $last, 0);
			}

			// insert billing location
			$billarr = array(
				':CompanyPK' => $cpk,
				':ShipAddr1' => $BillAddr1, 
				':ShipAddr2' => $BillAddr2, 
				':ShipCity' => $BillCity, 
				':ShipProvince' => $BillProvince, 
				':ShipPostal' => $BillPostal, 
				':ShipCountry' => $BillCountry,
				':ShipName' => 'Billing'
			);
			$sql->sqlCommand("INSERT INTO Locations (CompanyPK, ShipAddr1, ShipAddr2, ShipCity, ShipProvince, ShipPostal, ShipCountry, ShipName) VALUES (:CompanyPK, :ShipAddr1, :ShipAddr2, :ShipCity, :ShipProvince, :ShipPostal, :ShipCountry, :ShipName)", $billarr, false);
			$last = $sql->lastInsert();

			// finally, add all sync lines. 
			$this->addToQueue("Companies", $cpk, 0);
			$this->addToQueue("Contacts", $custpk, 0);
			$this->addToQueue("Locations", $last, 0);

			$sql->logAction($_SESSION['USER'], "User added new company: " . $CompanyName);	
		} else {
			$sql->logAction($_SESSION['USER'], "User attempted to add new company but did not have permissions.");	
		}
	}	
}