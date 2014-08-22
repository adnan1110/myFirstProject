<?php 

Class DbHandler {

	private $conn;

	function __construct(){
		require_once 'DbConnect.php';
		
		$db = new DbConnect();
		
		$this->conn = $db->connect();
	}
	
	/**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }
	
	public function createAccount($name, $email, $password){
		require_once 'PassHash.php';
		$response = array();
		
		if(!$this->isAccountExists($email)){
			
			$password_hash = PassHash::hash($password);
			
			$api_key = $this->generateApiKey();
			
			$params = array();
			$params[0] = $name; 
			$params[1] = $email; 
			$params[2] = $password_hash;
			$params[3] = $api_key; 
			$params[4] = 1; 
			
			$result = pg_query_params($this->conn,"INSERT INTO accounts 
			(name, email, password_hash, api_key, status) values 
			($1, $2, $3, $4, $5);", $params);
			
			if(!$result){
				return 1; 
			}
			
			return 0;  
		}
		return 2; 
	}
	
	public function isAccountExists($email) {
		$params = array();
		$params["email"] = $email;
        $result = pg_query_params($this->conn,"SELECT id from accounts WHERE email = $1", $params);
        if($result == false){
			echo 'error occured' . pg_last_error($this->conn);
			return NULL;
		} else{ 
			return pg_num_rows($result) > 0; 
        }
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

	public function checkLogin($email, $password){
		require_once 'PassHash.php';
		
		$params = array(); 
		$params[0] = $email; 
		$result = pg_query_params($this->conn, "SELECT password_hash FROM accounts WHERE email 
		= $1;", $params); 
		
		if(pg_num_rows($result) > 0){
			$row = pg_fetch_assoc($result); 
			$password_hash = $row["password_hash"];
			if(PassHash::check_password($password_hash, $password))
				return TRUE; 
			else 
				return FALSE;
		} else 
			return FALSE;
		
	}
	
	
	
	public function getAccountId($api_key){
		
		$params = array();
		$params[0] = $api_key;
		$query = pg_query_params($this->conn, "SELECT id FROM accounts WHERE api_key = $1;",$params);
		$row = pg_fetch_assoc($query); 
		if($row != null){
			return $row["id"];
		}
		
		return null;
	}
	
	public function getAccountByEmail($email){
		$params = array(); 
		$params[0] = $email;
		$result = pg_query_params($this->conn,"SELECT name, email, api_key, status, 
		created_at FROM accounts WHERE email = $1;",$params);
		return pg_fetch_assoc($result);
	}
	
	
	public function createAccountPost($account_id,$post_id){
		$params = array();
		$params[0] = $account_id;
		$params[1] = $post_id;
		$query = pg_query_params($this->conn, "INSERT INTO account_posts(account_id, post_id) values ($1, $2 ) RETURNING id;", $params);
		
		return pg_fetch_assoc($query);
	}
	
	public function createPost($account_id,$title, $body){
		$params =  array(); 
		$params[0] = $title;  
		$params[1] = $body;
		$query = pg_query_params($this->conn,"INSERT INTO posts (title, body) VALUES ($1, $2) RETURNING id;", $params);
		
		if($query != null)
		{
			$row = pg_fetch_assoc($query);
			$new_post_id = $row["id"];
			$result = $this->createAccountPost($account_id, $new_post_id);
			
			if($result != null){
				return $new_post_id;
			
			} else {
				return null;
				}
		} else {
			return null;
		}
	}
	
	public function getAllPosts(){
		$result = pg_query($this->conn,"SELECT * FROM posts;");
		
		if($result == false){
		
			echo 'error occured' . pg_last_error($this->conn);
			return NULL;
		} else{
			
			return $result;
		}
	
	
	}
	
}
?>