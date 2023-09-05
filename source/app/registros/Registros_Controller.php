<?php 
namespace app\registros;

use \app\registros\Registros_Model;
use \Flight;

class Registros_Controller{
   private Registros_Model $model;
   private array $keys = ["type", "message", "is_identified", "deleted"]; //columns that are allowed to be updated / are required for an insert

   public function __construct(){
      $this->model = new Registros_Model();
   }

   /**
   * Handles the request sent
   * 
   * @return void
   */
   public function request(string $method, ?string $id) : void{
      if($id) $this->resourceHandler($method, $id);
      else $this->collectionHandler($method);
   }

   /**
   * Called when the request has an ID
   * 
   * @return void
   */
   private function resourceHandler(string $method, string $id) : void{
      switch($method){
         case "GET":
            echo $this->model->getRegistro($id);
         break;

         case "PUT":
         case "PATCH":
            if($this->updateValidation()){
               echo $this->model->updateRegistro($id);
            }
            else{
               Flight::response()->status(422); //unprocessible entity
               echo json_encode(array('status' => 'error', "msg" => "At least one of the following parameters should be passed: " . implode(", ", $this->keys)));
            }
         break;

         case "DELETE":
            echo $this->model->deleteRegistro($id);
         break;

         default:
            Flight::response()
               ->status(405) //method not allowed
               ->header('Allow', 'GET, PUT, PATCH, DELETE') //prints allowed http verbs
               ->send();
            exit;
      }
   }

   /**
   * Called when the request doesnt have an ID
   * 
   * @return void
   */
   private function collectionHandler(string $method) : void {
      switch($method){
         case "GET":
            echo $this->model->getRegistros();
         break;

         case "POST":
            if($this->insertValidation()){
               echo $this->model->insertRegistro();
            }
            else{
               Flight::response()->status(422); //unprocessible entity
               echo json_encode(array('status' => 'error', "msg" => "The following parameters must always be passed: " . implode(", ", $this->keys)));
            }
         break;

         default:
            Flight::response()
               ->status(405) //method not allowed
               ->header('Allow', 'GET, POST') //prints allowed http verbs
               ->send();
            exit;
      }
   }

   /**
   * Checks if at least one value was passed
   * 
   * @return bool
   */
   private function updateValidation() : bool{
      foreach ($this->keys as $key) {
         if (isset($_POST[$key]) && !is_null($_POST[$key]) && $_POST[$key] !== "") return true;
      }
      
      return false;
   }

   /**
   * Checks if all the required values have been passed
   * 
   * @return bool
   */
   private function insertValidation() : bool{
      foreach ($this->keys as $key) {
         if (!isset($_POST[$key]) || is_null($_POST[$key]) || $_POST[$key] === "") return false;
      }
      
      return true;
   }
}