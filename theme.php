<?php

/**
 *
 * Miller Theme styling. 
 *
 */
 
 function insert_header($userstatus = false){ 
 	?>
 	
 	<html>
 		<head>
 			<title>Hose Test Viewer<?php echo pagetitle($userstatus); ?> </title>
 			<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
 			 
 			
 			<link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:ital,wght@0,300;0,800;1,300;1,800&display=swap" rel="stylesheet">
 			<link rel="stylesheet" type="text/css" href="css/style.css" />
 		</head> 
 		<body>
 		
 		<div id="allwrap">
 			<header>
 				<div id="innerheader">
 				<img src="img/mepbro-logo.svg" />
 				<div id="titlebar">
 					<h1>Hose Test Viewer</h1>
 				</div>
 				<?php if($userstatus){ echo '<img src="img/menu.svg" id="mobilemenubutton" />'; } ?>
 				<ul id="menubar">
					<?php  if($userstatus){ ?>
						
						
						
						<?php 
						
						if($_SESSION['TYPE'] != 1){ ?>
							<!-- IF AN ADMIN, ADD THE TABLE INSERT PAGES HERE -->
							<li><a href="index.php?p=database">Add/Edit Database</a></li>
							<li><a href="index.php?p=admin">Admin</a></li>
						<?php }  else { ?>
							<li><a href="index.php?p=hoses">Hoses</a></li>
						<?php } ?>
						<li><a href="index.php?p=account">Account</a></li>
						<li><a href="index.php?p=logout">Log Out</a></li>
					<?php }  ?>
 				</ul>
 				</div><!-- /innerheader -->
 			</header>
 			<div id="content">
 	<?php		
 	
 }
 
 function insert_footer(){
 	?> 
 				</div><!-- /content -->
 			
				<footer class="brownbg">
					<em>&copy; MEP Brothers Ltd.</em>
				</footer>
 			</div><!-- /allwrap -->
 		</body>
 	</html>
 	
 	<?php
 }
 
 ?>
 
 <?php
 
 // theme function
 
function pagetitle($userstatus = false){
 	if(!$userstatus){
 		return '';
 	}
 
 	if(!isset($_GET['p'])){
 		$check = '';
 	} else {
 		$check = $_GET['p'];
 		
 		if($_SESSION['TYPE'] == 'Employee'){
 		// this is an employee and we need to prevent access to admin
 			$check = ($check == 'admin' ? 'account' : $check);
 		} 	
 	}
 	
 	switch($check){
 		case 'test':
 			$title = ' - Hose Test';
 			break;
 		case 'account':
 			$title = ' - Edit Account';
 			break;
 		case 'history':
 			$title = ' - Test History';
 			break;
 		case 'admin':
 			$title = ' - Administrator Access';
 			break;
 		default:
 			$title = '';
 			break;
 	} 
 	return $title;
}
