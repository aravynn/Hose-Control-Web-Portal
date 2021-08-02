<?php

/**
 *
 * main page index for the site. 
 *
 *
 */
 
 require_once("config.php");
 
  $user = new user();
 
 if(!isset($_GET['p'])){
 	insert_header($user->CheckLoggedIn());
 } else {
	 if($_GET['p'] != 'logout'){
		insert_header($user->CheckLoggedIn());
	 } 
 }

 
 if($user->CheckLoggedIn()){
 	// logged in, load the order form. 
 	
 	//echo "logged in";
 	
 	
 	
 	if(!isset($_GET['p'])){
 		$check = 'order';
 	} else {
 		$check = $_GET['p'];
 		
 		if($_SESSION['TYPE'] == 'Employee'){
 		// this is an employee and we need to prevent access to admin
 			$check = ($check == 'admin' ? 'order' : $check);
 		}
 	
 	}
 	
 	if($_SESSION['STATUS'] == 'Frozen'){
 		echo '<em class="warning"> WARNING: Your account has been temporarily frozen, and you will be unable to create orders. Please contact your administrator.</em>';
 	}
 	
 	switch($check){
 		
 		case 'account':
 			include "account-form.php";
 			break;
 		case 'admin':
 			include "admin-form.php";
 			break;
 		case 'database':
 			include "database-form.php";
 			break;
 		case 'hoses':
 			include "hoses-form.php";
 			break;
 		case 'logout':
 			$user->Logout();
 			header("Location:index.php");
 			break;
 		case 'test':
 		default:
 			include "test-form.php";
 			break;
 	} 
 	
 } else {
 	// not logged in, create the login form 
 	if(isset($_GET['r']) && $_GET['r']){
 		// there is an error, and spit out the phrase. 
 		if($_GET['r'] == 'error'){ echo '<em class="error">ERROR: Incorrect Username/Password</em>'; }	
 		if($_GET['r'] == 'error-too-many'){ echo '<em class="warning">WARNING: Too many login attempts, please wait a minute then try again.</em>'; }	
 	}
 	
 	//echo "not logged in";
?>
 <div id="login">
 	<form method="post" action="authenticate.php">
 		<label for="username">Username</label><input type="text" id="username" name="username" /><br />
 		<label for="password">Password</label><input type="password" id="password" name="password" /><br />
 		<input type="hidden" name="action" value="login" />
 		<input type="submit" value="Login" />
 	</form>
 </div>
 	
<?php 	
 	
 }
 
 
 insert_footer();
 
 ?>