<?php

/** 
 *
 * Class definition: User. 
 * Will authenticate users and create a persistent session
 * Most code should be behind the scenes. 
 *
 */
 
 if(count(get_included_files()) ==1){
 	 http_response_code(403);
 	 exit("ERROR 403: Direct access not permitted.");
}
 
 class user {
 	
 	private $maxtime = 36000; // 1 hour time limit.
 	
 	public $UserID; // user ID or PK from DB
 	
 	public $Username;
 	public $UserType;
	public $CompanyID;
 	
 	
 	function __construct() {
		if(!isset($_SESSION)) session_start();
	}
	
	public function Logout(){
		session_unset();     // unset $_SESSION variable for the run-time 
		session_destroy();
	}
	
	private function FilterString($i){
		return filter_var($i, FILTER_SANITIZE_STRING);
	}
	
	public function NewUser($username, $password, $CompanyID, $UserType){
		// create a new user, probably only used by admin functions. 
		
		$newSalt = $this->GenerateSalt();
		
		error_log($newSalt);
		
 		$hash = $this->generatePassword($password, $newSalt);
		
		$sql = new sqlControl();
		$sql->sqlCommand('INSERT INTO Users (User, Pass, Salt, UserType, CompanyID) VALUES(:User, :Pass, :Salt, :Type, :CompanyID)', 
			array(
				':User' => $username, 
				':Pass' => $hash, 
				':Salt' => $newSalt, 
				':Type' => intval($UserType),
				':CompanyID' => intval($CompanyID)), 
			false);
			
		// return the created ID.
		
		return $sql->lastInsert();
		
		$sql = ''; // this never fires. 
	} 

	public function LoadUserByID($id){
		// load users information into object memory. 
		$this->UserID = $id; // set UserID for this user. 
		
		$sql = new sqlControl();
		$sql->sqlCommand('SELECT User, UserType, CompanyID FROM Users WHERE PK = :PK', array(':PK' => $this->UserID), false);
		
		$results = $sql->returnResults(); // get the results for the user. 
		
		$this->Username = $this->FilterString($results['User']);
		$this->UserType = $this->FilterString($results['UserType']);
		$this->CompanyID = $this->FilterString($results['CompanyID']);

	}
	
	public function LoadUserByName($user){
		// overload function, get a user's id by username, then load user by ID
		$sql = new sqlControl();
		$sql->sqlCommand('SELECT PK FROM Users WHERE User = :User', array(':User' => $user), false);
		
		$results = $sql->returnResults(); // get the results for the user. 
		
		$this->LoadUserByID($this->FilterString($results['PK']));
		
		$sql = '';
		
	}

 	public function ResetPassword($oldpass, $newpass){
 		// reset user password and save to DB.
 		
 		if($oldpass == '' || $newpass == ''){
 			return false;
 		}
 		
 		if($this->authenticateUser($_SESSION['USER'], $oldpass, true) == 0){
 		
			// generate information to save to the DB. 
			$newSalt = $this->GenerateSalt();
			$hash = $this->generatePassword($newpass, $newSalt);
		
			// upload data to the DB. 
			$sql = new sqlControl();
			$sql->sqlCommand('UPDATE Users SET Pass = :Pass, Salt = :Salt WHERE PK = :PK', array(':Pass' => $hash, ':Salt' => $newSalt, ':PK' => $this->UserID), false);
			
			$sql->logAction($_SESSION['USER'], "User updated their password");
			$sql = '';
			return true;
		} else {
			$sql = new sqlControl();
			$sql->logAction($_SESSION['USER'], "User attempted to update their password, but entered one incorrectly.");
			$sql = '';
			return false;
		}
		
 	}
 	
 	public function CheckPasswordStrength($password){
 		// check password for the main essentials of password strength. 
 		// password should have: 
 		// at least 8 characters
 		// 1 capital
 		// 1 lower case
 		// 1 number
 		// 1 special character
 		$error = array();
 		
 		if (strlen($password) < 8) {
 		//	error_log('too short');
 			$error[] = 'Length';
 		}
 		if (!preg_match("#[0-9]+#", $password)) {
 		//	error_log('no numbers');
 			$error[] = 'Number';
        }
      	  
      	if (!preg_match("#[A-Z]+#", $password)) {
        //	error_log('no capitals');
        	$error[] = 'Capital';
        } 
        
        if (!preg_match("#[a-z]+#", $password)) {
        //	error_log('no letters');
        	$error[] = 'Letter';
        }
     	/*   
     	if (!preg_match('/[^a-zA-Z\d]/', $password)) {
        	error_log('no special character');
        	$error[] = 'Special';
        } */
        
        
        // return an array of any found errors.
        return $error;
    
 		
 	}
 	
 	private function GenerateSalt(){
 		// create a new salt. 
 		return hash("sha1", bin2hex(random_bytes(16))); // 32 byte salt will be used.
 	}

	private function hash_compare($a, $b) { 
        
        if (!is_string($a) || !is_string($b)) { 
            return false; 
        } 
        
        $len = strlen($a); 
        if ($len !== strlen($b)) { 
            return false; 
        } 

        $status = 0; 
        for ($i = 0; $i < $len; $i++) { 
            $status |= ord($a[$i]) ^ ord($b[$i]); 
        } 
        return $status === 0; 
    } 
	
	private function generatePassword($pass, $salt){
		// generate password using pass, salt and pepper. 
		
		return hash("sha1", $pass . $salt);
		
	}
	
	private function logAttempt($passfail){
		$sql = new sqlControl();
		$sql->sqlCommand('INSERT INTO AccessLog (IP, Response) VALUES (:IP, :response)', array( ':IP' => $_SERVER['REMOTE_ADDR'], ':response' => ($passfail ? 'true' : 'false')), false);
		$sql = '';
	}
	
	public function authenticateUser($username, $password, $retest = false){
		// take the username and password and authenticate the user. 
		// if valid, create the new session variable and return true,
		// otherwise, return false and exit. 
		
		//error_log($username . ' ' . $password);
		
		if($password == '' || $username == ''){
			// The login data is non existent. 
			$this->logAttempt(false); 
			return -1;
		}
		
		$sql = new sqlControl(); // create the SQL object.
		$sql->sqlCommand("SELECT Pass, Salt, UserType FROM Users WHERE User = :User LIMIT 1", array(":User" => $username), false);	
		
		$returnvars = $sql->returnResults();
		
		error_log($returnvars['UserType']);
		
		// if there is a cookie, this is pointless.
		if($this->CheckForLoginCookie()){ 
			// Do we need this?
			if(!$retest){
				$sql->logAction($username, "User attempted login, but cookie already existed");
				return -1;
			}
		}
		
		
		// check for too many access attempts. 
		$sql->sqlCommand('SELECT PK FROM AccessLog WHERE IP = :IP AND RESPONSE = "false" AND timestamp > NOW() - INTERVAL 1 MINUTE', array( ':IP' => $_SERVER['REMOTE_ADDR']), false);
		$attempts = count($sql->returnAllResults());
		
		if($attempts > 3){
				$this->logAttempt(false);
				$sql->logAction($username, "User attempted login, too many attempts were made from this IP");
				return -2;
		} else {
			// we can attempt, they haven't gone overboard.
			
			$a = $this->generatePassword($this->FilterString($password), $this->FilterString($returnvars['Salt']));
			$b = $this->FilterString($returnvars['Pass']);
			
			if(!$this->hash_compare($a, $b)){
				// failed to match, fail the test.
				
				$this->logAttempt(false);
				$sql->logAction($username, "User attempted login, but user password was wrong.");
				return -1;
			} else {
				// success, generate the authentication cookie. 
				
				$this->logAttempt(true);
				$this->GenerateLoginCookie($this->FilterString($username), $this->FilterString($returnvars['UserType']), $this->FilterString($returnvars['Status']));
				$sql->logAction($username, "User successfully logged in.");
				return 0;
			}
		}
			
		$sql = ''; // destroy SQL.
		
	}
		
	private function GenerateLoginCookie($username, $usertype, $status){
		if (!isset($_SESSION['LAST_ACTIVITY'])) {
			$_SESSION['LAST_ACTIVITY'] = time();
			$_SESSION['USER'] = $this->FilterString($username);
			$_SESSION['TYPE'] = $this->FilterString($usertype);
			$_SESSION['STATUS'] = $this->FilterString($status);
			$_SESSION['SITE'] = 'MEPUL';
		} else if (time() - $_SESSION['LAST_ACTIVITY'] > $this->maxtime) {
			// session started more than 5 minutes ago
			session_regenerate_id(true);    // change session ID for the current session and invalidate old session ID
			$_SESSION['LAST_ACTIVITY'] = time();
		}
	}
	
	private function CheckForLoginCookie(){
		if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $this->maxtime)) {		
			// last request was more than 5 minutes ago
			session_unset();     // unset $_SESSION variable for the run-time 
			session_destroy();   // destroy session data in storage	
			return false;	
		} elseif ( !isset($_SESSION['LAST_ACTIVITY']) ) {
			return false;
		} elseif ( $_SESSION['SITE'] != 'MEPUL' ) { 
			return false;	
		} else {
			session_regenerate_id(true);  
			$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
			
			// load the user information.
			$this->LoadUserByName($_SESSION['USER']);
			return true;
		}
	}
	
	public function CheckLoggedIn(){
		// check if a user is logged in on internal pages. 
		// boot them if not. 
		return $this->CheckForLoginCookie();
		
	}
 }