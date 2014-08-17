<?php

require 'C:\wamp\www\hello_php\vendor\Slim\Slim\Slim\Slim.php';
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




$app->get ( '/test', function (){
	$response = array ();
		
	$response ["error"] = false;
	$response ["message"] = "it works!!!";
	
	
	
	echoResponse( 200, $response );
});


$app->get ( '/databasetest', function (){
	$response = array ();
	
	$response ["error"] = false;
	$response ["message"] = "it works!!!";
	
	$db = new DbConnect();
		
	$conn = $db->connect();
	
	echoResponse( 200, $response );
});


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
		
		array_push ( $response ["accounts"], $tmp );
	}

	echoResponse ( 200, $response );
});

$app->run ();
	?>