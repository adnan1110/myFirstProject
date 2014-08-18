<?php

#require 'C:\wamp\www\hello_php\vendor\Slim\Slim\Slim\Slim.php';
require('vendor/autoload.php');	
require_once 'DbHandler.php';	

\Slim\Slim::registerAutoloader ();
	
$app = new \Slim\Slim ();
function echoResponse($status_code, $response) {
	$app = \Slim\Slim::getInstance ();
	// Http response code
	$app->status ( $status_code );
	
	// setting response content type to json
	$app->contentType ( 'application/json' );
	
	echo json_encode ( $response );
}

function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoResponse(400, $response);
        $app->stop();
    }
}

function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}


$app->get ( '/test', function (){
	$response = array ();
		
	$response ["error"] = false;
	
	$db = new DbHandler ();
	$result = $db->isAccountExists();
	if($result)
		$response ["message"] = "it works!!!" . $result;
	else 
		$response ["message"] = "it works!!! it didn't find it " . $result;
	echoResponse( 200, $response );
});

#create a new account
$app->post('/register', function() use ($app){

	verifyRequiredParams(array('name', 'email', 'password'));
	$response = array();
	
	$name = $app->request->post('name');
	$email = $app->request->post('email');
	$password = $app->request->post('password');
	
	validateEmail($email);
	
	 $db = new DbHandler();
     $result = $db->createAccount($name, $email, $password);

	if ($result == 0) {
		$response["error"] = false;
		$response["message"] = "You are successfully registered";
		echoResponse(201, $response);
	} else if ($result == 1) {
		$response["error"] = true;
		$response["message"] = "Oops! An error occurred while registereing";
		echoRespnse(200, $response);
	} else if ($result == 2) {
		$response["error"] = true;
		$response["message"] = "Sorry, this email already existed";
		echoResponse(200, $response);
	}

});

$app->get ( '/databasetest', function (){
	$response = array ();
	
	$response ["error"] = false;
	$response ["message"] = "it works!!!";
	
	$db = new DbConnect();
		
	$conn = $db->connect();
	
	echoResponse( 200, $response );
});

#retrieve all accounts
$app->get ('/accounts', function () {
	
	$response = array();
		
	$db = new DbHandler ();
	
	$result = $db->getAllAccounts ();
	
	$response ["error"] = false;
	$response ["accounts"] = array ();
	
	while ( $account = pg_fetch_row($result) ) {
		$tmp = array ();
		$tmp ["id"] = $account [0];
		$tmp ["name"] = $account [1];
		$tmp ["email"] = $account [2];
		$tmp ["password_hash"] = $account[3];
		$tmp ["status"] = $account[4]; 
		$tmp ["created_at"] = $account[5];
		$tmp ["api_key"] = $account[6];
		array_push ( $response ["accounts"], $tmp );
	}

	echoResponse ( 200, $response );
});

# check login
$app->post('/login', function() use ($app) {
	verifyRequiredParams(array('email', 'password'));
	
	$email = $app->request()->post('email');
    $password = $app->request()->post('password');
    $response = array();
	
	$db = new DbHandler();
	
	if($db->checkLogin($email,$password))
	{
		$user = $db->getAccountByEmail($email);
		if($user != NULL){
			$response["error"] = false;
            $response['name'] = $user['name'];
            $response['email'] = $user['email'];
			$response['apiKey'] = $user['api_key'];
            $response['createdAt'] = $user['created_at'];
		
		} else{
			$response['error'] = true;
			$response['message'] = "An error occurred. Please try again";
		}
	} else { 
		$response['error'] = true;
        $response['message'] = 'Login failed. Incorrect credentials';
	}
	
	echoResponse(200, $response);

});

$app->run ();
	?>