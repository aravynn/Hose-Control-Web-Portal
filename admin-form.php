<?php

/**
 *
 * Order Form Page
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
<div id="admin">
<h2> Control Center </h2>

<h3>Add User</h3>
<?php
	if(isset($_GET['nu'])){
		if($_GET['nu'] == 'u'){
			echo '<em class="error">ERROR: Username exists, please select a different username. </em>';
		}
		if($_GET['nu'] == 'p'){
			echo '<em class="error">ERROR: Password is invalid, please ensure the password is at least 8 characters in length and includes: 
					<ul>
						<li> At least 1 lower case letter</li>
						<li> At least 1 upper case letter</li>
						<li> At least 1 number</li>
					</ul></em>';
		}
		if($_GET['nu'] == 'i'){
			echo '<em class="error">ERROR: Username or password is blank, please enter a valid input. </em>';
		}
		if($_GET['nu'] == 'e'){
			echo '<em class="error">ERROR: Email is not correctly formatted, please enter a correct email. </em>';
		}
		if($_GET['nu'] == 's'){
			echo '<em class="success">User successfully added.</em>';
		}
	}
?>

<form method="post" action="authenticate.php">
	<label for="username">Username</label><input type="text" id="username" name="username" /><br />
	<label for="password">Password</label><input type="password" id="password" name="password" /><br />
	<label for="companyID">Company</label><select id="companyID" name="companyID" >
		<option value="-1">Admin Account</option>
		<?php echo getAllCompanies(); ?>
	</select><br />
	<input type="hidden" name="action" value="AddNewUser" />
	<input type="submit" />
</form>
</div>

<?php 

function getAllCompanies(){ // RETURN HTML STRING
	// create an option array of all companies names. 
	
	$admin = new admin();
	
	$companies = $admin->GetCompanies();
	
	$output ='';
	
	foreach($companies as $c){
		$output .= '<option value="' . $c['PK'] . '">' . $c['Name'] . '</option>';
	}
	
	return $output;
}

?>