<?php

/**
 *
 * Class Definition for SQL connector. 
 * use PDO for all connection strings. 
 * Will require: insert, update, delete
 *
 */
 
// prevent direct access
if(count(get_included_files()) ==1){
 	 http_response_code(403);
 	 exit("ERROR 403: Direct access not permitted.");
}
 
class sqlControl {
// will send an ARRAY, with Key-Value pairs for all applicable things we need, which the database will parse
// then send it to SQL. no direct sql should be written anywhere in the code, only created, referenced, and released.
	private $dbh;
	private $STH;

	function __construct(){
		// load the SQL system
		global $sqlCFG;
		
		try {  
			$this->dbh = new PDO('mysql:host=localhost;dbname=' . $sqlCFG['db'], $sqlCFG['user'], $sqlCFG['pass']);
		}  
		catch(PDOException $e) {  
			echo $e->getMessage();  
		} 
	}
	
	public function sqlCommand($stmt, $value, $log = false){// $type='none'){ <-- for when we need to add REPLACE functionality.
		// this will be used for general constructs. note that there should be almost no sql
		// statements that are outside of classes, mostly everything should be static calls within functions.
	
		if($log){
			error_log($stmt);
		}
	
		foreach($value as $key => &$val){
			$val = $this->VarFilter($val);
			if($log){
				error_log($key . '=>' . $val);
			}
		}
		
		// unset the last variable
		unset($val);
		
		// all values now filtered for special characters. we'll run a second filter before release.
		// this will allow for simple comprehension and release for all information.
	
		$this->STH = $this->dbh->prepare($stmt);
	
		$this->STH->execute($value);
		
		if($log){
			$errlog = $this->STH->errorInfo();
		
			error_log("Log 0: " . $errlog[0]);
			error_log("Log 1: " . $errlog[1]);
			error_log("Log 2: " . $errlog[2]);
		}
	
	}
	
	public function VarFilter($var){
		return filter_var($var, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	
	}
	
	public function lastInsert(){
		return $this->dbh->lastInsertId();
		//$this->STH = $this->dbh->query("SELECT LAST_INSERT_ID()");
		//return $this->STH->fetchColumn();
	}	
	
	public function returnResults(){
		// if there is results, (depending of case) it will find them.
		
		return $this->STH->fetch();	
	}
	
	public function returnAllResults(){
		// if there is results, (depending of case) it will find them.
		return $this->STH->fetchAll();	
	}
	
	public function logAction($UserID, $text){
		// make a log entry of an action taken. should interact at every authenticate function call.
		
		//error_log($UserID);
		
		$stmt = "INSERT INTO UserLog (Username, Action) VALUES (:u, :a)";
		$vals = array( ":u" => $UserID, ":a" => $text);
		$this->sqlCommand($stmt, $vals);
	}
	
	function __destruct(){
		// destroy the SQL system
		$this->dbh = '';
	}

}

?>