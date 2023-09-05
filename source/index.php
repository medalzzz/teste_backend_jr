<?php

// Nesse arquivo constam alguns exemplos. Caso use um framework, sinta-se livre para substituir esse arquivo.

// Caso não consiga utilizar o SQLite (esperamos que consiga), vamos disponibilizar um array com os dados
// para ser utilizado como um "database fake" no arquivo registros.php.

// Exemplo de conexão com o SQLite usando PDO (referência: https://www.php.net/manual/pt_BR/ref.pdo-sqlite.php)

require_once 'vendor/autoload.php';

// declare(strict_types = 1);
error_reporting(E_ALL & ~E_NOTICE);

// UNCOMMENT TO DEBUG
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// sets timezone
date_default_timezone_set('America/Sao_Paulo'); 

//forces a json response in all endpoints
header("Content-type: application/json; charset=UTF-8");

//error handlers
set_error_handler('\app\error_handler\ErrorHandler::handleError');
set_exception_handler('app\error_handler\ErrorHandler::handleException');

//get data sent through json
$jsonPayload = file_get_contents('php://input'); 
if($jsonPayload != "") {
   $data = json_decode($jsonPayload, true);

   if($data !== NULL) {
      if($_SERVER["REQUEST_METHOD"] == "GET") $_GET = array_merge($_GET, $data); // merges json data with $_GET data
      else $_POST = array_merge($_POST, $data); // merges json data with $_POST data
   }
}

//routes array
require_once "routes.php";

//sets base url for flight
Flight::set('flight.base_url', '/');

//bootstrap routes
foreach($routes as $route => $controllerName) {
   Flight::route($route, function($id = NULL) use ($controllerName) {
      $controller = new $controllerName();
      $controller->request($_SERVER["REQUEST_METHOD"], $id);
   });
}

//shows 404 if user requests unavailable route
Flight::map('notFound', function(){
   Flight::response()->status(404); //not found
});
 
Flight::start(); //runs flight framework