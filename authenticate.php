<?php

/** 
 *
 * POST functionality will be accessed here, then returned back to the main page. 
 *
 */
 
 if(empty($_POST)){
 	http_response_code(405);
 	exit("ERROR 405: Method Not Found");
 }
 
 require_once("config.php");
 
if($_POST['action'] != 'login'){
	checkUser();
 }
 
 switch($_POST['action']){
 	case 'login':
 		// login attempt goes here. 
 		
 		$user = new user();
 		$res = $user->authenticateUser($_POST['username'], $_POST['password']);
 		
 		if($res == 0){
 			// redirect back to the correct page. 
 			header('location:index.php?p=hoses');
 		} elseif($res == -1) {
 			header('location:index.php?r=error');
 		} else {
 			header('location:index.php?r=error-too-many');
 		}
 		break;
 	case 'UpdatePassword':
 		$user = new user();
 		$user->LoadUserByName($_SESSION['USER']);
 		
 		if(count($user->CheckPasswordStrength($_POST['newpassword'])) > 0){
 			error_log("pass strength");
 			header('location:index.php?p=account&r=password-strength');
 			break;
 		}
 			
 		
 		if($user->ResetPassword($_POST['oldpassword'], $_POST['newpassword'])){
 			header('location:index.php?p=account');
 		} else {
 			header('location:index.php?p=account&r=incorrect-password');
 		}
 		break;
 	
 	case 'AddNewUser': // create new user via admin
 		$admin = new admin();
 		
 		error_log ("CompanyID: " . $_POST['companyID']);
 		
 		switch($admin->AddUser($_POST['username'], $_POST['password'], $_POST['companyID'])){
 			case 0: 
 				header('location:index.php?p=admin&nu=s');
 				break;
 			case -1: // -1 bad username
 				header('location:index.php?p=admin&nu=u');
 				break;
 			case -2: // -2 bad password
 				header('location:index.php?p=admin&nu=p');
 				break;
 			case -3: // -3 invalid entries
 				header('location:index.php?p=admin&nu=i');
 				break;
 			case -4: // -4 bad email
 				header('location:index.php?p=admin&nu=e');
 				break;
 			default: 
 				break;
 		}
 		
 		$admin = '';		
 		break;
	case 'fittingtemplatedb': // add or update database information. 
		$admin = new admin();

		if($_POST['dbid'] !== 'false'){
			// this is an update. 
			$admin->updateFittingTemplate($_POST['dbid'], $_POST['Name'], $_POST['PartNumber'], $_POST['Description'], $_POST['AttachMethod']);
		} else {
			// this is an insert. 
			$admin->insertFittingTemplate( $_POST['Name'], $_POST['PartNumber'], $_POST['Description'], $_POST['AttachMethod']);
		}

		header('location:index.php?p=database&r=o');

		break;
	case 'hosetemplatedb':
		
		$admin = new admin();

		if($_POST['dbid'] !== 'false'){
			// this is an update. 
			$admin->updateHoseTemplate($_POST['dbid'], $_POST['PartNumber'], 
			$_POST['Name'], $_POST['Manufacturer'], $_POST['Description'], 
			$_POST['DistributorRef'], $_POST['InnerDiameter'], 
			$_POST['OverallLength'], $_POST['CutLength'], $_POST['WorkingPres'], 
			$_POST['TestPres'], $_POST['TestTime'], $_POST['CouplingAPK'], 
			$_POST['CouplingBPK'], $_POST['Image'], $_POST['Notes']);
		} else {
			// this is an insert. 
			$admin->insertHoseTemplate($_POST['PartNumber'], 
			$_POST['Name'], $_POST['Manufacturer'], $_POST['Description'], 
			$_POST['DistributorRef'], $_POST['InnerDiameter'], 
			$_POST['OverallLength'], $_POST['CutLength'], $_POST['WorkingPres'], 
			$_POST['TestPres'], $_POST['TestTime'], $_POST['CouplingAPK'], 
			$_POST['CouplingBPK'], $_POST['Image'], $_POST['Notes']);
		}

		header('location:index.php?p=database&r=o');
		
		break;
	case 'companydb':

		$admin = new admin();

		if($_POST['dbid'] !== 'false'){
			// this is an update. 
			$admin->updateCompany($_POST['dbid'], $_POST['CompanyName'], $_POST['ContactName'], 
			$_POST['ContactPK'], $_POST['Phone1'], $_POST['Phone1PK'], $_POST['Phone2'], 
			$_POST['Phone2PK'], $_POST['Email1'], $_POST['Email1PK'], $_POST['Email2'], 
			$_POST['Email2PK'], $_POST['BillAddr1'], $_POST['BillAddr2'], $_POST['BillCity'], 
			$_POST['BillProvince'], $_POST['BillPostal'], $_POST['BillCountry'], $_POST['Notes']);
		} else {
			// this is an insert. 
			$admin->insertCompany($_POST['CompanyName'], $_POST['ContactName'], $_POST['ContactPK'], 
			$_POST['Phone1'], $_POST['Phone1PK'], $_POST['Phone2'], $_POST['Phone2PK'], 
			$_POST['Email1'], $_POST['Email1PK'], $_POST['Email2'], $_POST['Email2PK'], 
			$_POST['BillAddr1'], $_POST['BillAddr2'], $_POST['BillCity'], $_POST['BillProvince'], 
			$_POST['BillPostal'], $_POST['BillCountry'], $_POST['Notes']);
		}

		header('location:index.php?p=database&r=o');

		break;
 	default:
 		break;
 }
 
 
 function checkUser(){
 	// check for persistent user allowability. 
 	$user = new user();
 	if(!$user->CheckLoggedIn()){
 		// Not logged in. Get OUT!
 		header('location:index.php');
 		die();
 		
 	} 
 }
 

 	
 ?>