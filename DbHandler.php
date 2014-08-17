<?php 

Class DbHandler {

	private $conn;

	function __construct(){
		require_once 'DbConnect.php';
		
		$db = new DbConnect();
		
		$this->conn = $db->connect();
	}

	public function getAllAccounts(){
	
		$result = pg_query($this->conn,"SELECT * FROM accounts");
		
		if($result == false){
		
			echo 'error occured' . pg_last_error($this->conn);
			return NULL;
		} else{
			
			return $result;
		}
		
		
	}	
}
?>