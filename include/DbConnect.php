<?php 
	
class DbConnect{
	private $conn; 
	
	function __construct(){
		
	}
	
	function connect(){
		
		
		
		$this->conn = pg_connect("host=ec2-54-204-27-119.compute-1.amazonaws.com
									port=5432 dbname=dcq6ksm9g8u25r user=wdmjobthmsoujw
									password=KJELWkBCuiOxGc1pkwLR1KCsmX")
									or die ("could not connect to database");
		if($this->conn == false){
			echo pg_last_error($this->conn);
		}
		
		
		
		return $this->conn;
	}


}

?>