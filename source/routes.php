<?php 

$routes = [
   "/registros" => "app\\registros\\Registros_Controller",
   "/registros/@id:[0-9]+" => "app\\registros\\Registros_Controller",
];

?>