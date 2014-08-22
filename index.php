<?php

#require 'C:\wamp\www\hello_php\vendor\Slim\Slim\Slim\Slim.php';
require('vendor/autoload.php');	
require_once 'DbHandler.php';	

\Slim\Slim::registerAutoloader ();
	
$app = new \Slim\Slim ();

$account_id = null;

function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($headers['authorization'])) {
        $db = new DbHandler();
 
        // get the api key
        $api_key = $headers['authorization'];
        // validating api key
		$temp = $db->getAccountId($api_key);
        if (!$temp) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoResponse(401, $response);
            $app->stop();
        } else {
            
            // get user primary key id
            global $account_id;
			$account_id	= $temp;
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoResponse(400, $response);
        $app->stop();
    }
}
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
        echoResponse(400, $response);
        $app->stop();
    }
}


$app->get ( '/test', 'authenticate', function () use ($app) {
	
	$response = array ();	
	$response ["error"] = false;
	
	$db = new DbHandler ();
	$result = $db->isAccountExists("adnan@gmail.com");
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
		$response["message"] = "Sorry, there is already a user with this email. ";
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
		array_push ( $response["accounts"], $tmp );
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

$app->post('/post','authenticate', function () use ($app) {
	
	verifyRequiredParams(array('title', 'body'));
	$response = array();
	$title = $app->request->post('title');
	$body = $app->request->post('body');
	
	global $account_id;
    $db = new DbHandler();
	
	$post_id = $db->createPost($account_id, $title, $body);
	
	if ($post_id != NULL) {
                $response["error"] = false;
                $response["message"] = "post created successfully";
                $response["task_id"] = $post_id;
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create post. Please try again";
            }
            echoResponse(201, $response);

});

$app->get('/post',function () use ($app) {
	
	
	$response = array();
	
    $db = new DbHandler();
	
	$result = $db->getAllPosts();
	
	if ($result != NULL) {
		$response ["error"] = false;
		$response ["posts"] = array ();
                while ( $post = pg_fetch_row($result) ) {
				
					$tmp = array ();
					$tmp ["id"] = $post [0];
					$tmp ["title"] = $post [1];
					$tmp ["body"] = $post [2];
					array_push ( $response ["posts"], $tmp );
			}
	
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create post. Please try again";
        }
    echoResponse(200, $response);

});

$app->run ();
	?>