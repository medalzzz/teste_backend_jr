<?php 
namespace app\registros;

use \app\database\Connection;
use PDO;
use \Flight;

class Registros_Model{
   private PDO $con;
   
   public function __construct(){
      $con = new Connection(); 
      $this->con = $con->getConnection();
   }

   /**
   * Returns multiple entries from database.
   * 
   * @return string
   */
   public function getRegistros() : string{
      $bindings = [];

      $sql = "SELECT * FROM registros";

      if(isset($_GET)){
         $sql .= " WHERE 1";

         if(isset($_GET["deleted"]) && $_GET["deleted"] !== null && $_GET["deleted"] !== ""){ 
            $sql .= " AND deleted = :deleted"; 
            $bindings[':deleted'] = $_GET['deleted'];
         }
   
         if(isset($_GET["type"]) && $_GET["type"] !== null && $_GET["type"] !== ""){ 
            $sql .= " AND type LIKE :type"; 
            $bindings[':type'] = "%".$_GET['type']."%"; 
         }
      }

      //ORDER BY
      if(isset($_GET["order"]) && $_GET["order"] !== null && $_GET["order"] !== ""){
         $allowed_columns = ["id", "type", "message", "is_identified", "whistleblower_name", "whistleblower_birth", "deleted", "created_at"];
         $allowed_dir = ["ASC", "DESC"];

         $order_by = in_array($_REQUEST["order"], $allowed_columns) ? $_REQUEST["order"] : 'id';
         $direction = (isset($_REQUEST["order_dir"]) && in_array(strtoupper($_REQUEST["order_dir"]), $allowed_dir)) ? $_REQUEST["order_dir"] : 'ASC';
         
         $sql .= " ORDER BY $order_by $direction";
      }

      //LIMIT && OFFSET
      if((isset($_GET["limit"]) && $_GET["limit"] !== null && $_GET["limit"] !== "")){
         $sql .= " LIMIT {$_REQUEST["limit"]}";
         (isset($_GET["offset"]) && $_GET["offset"] !== null && $_GET["offset"] !== "") ? $sql .= ", {$_REQUEST["offset"]}" : "";
      }

      $sql_count_filtro = "SELECT COUNT(id) AS total FROM ($sql)";
      $sql_total = "SELECT COUNT(id) AS total FROM registros";
      
      // default query
      $stmt = $this->con->prepare($sql);
      $stmt->execute($bindings);
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      //results with filters
      $stmt_count_filtro = $this->con->prepare($sql_count_filtro);
      $stmt_count_filtro->execute($bindings);
      $filteredRecords = $stmt_count_filtro->fetch(PDO::FETCH_ASSOC);
      $filteredRecords = $filteredRecords["total"];

      //results without filters
      $stmt_total = $this->con->prepare($sql_total);
      $stmt_total->execute();
      $totalRecords = $stmt_total->fetch(PDO::FETCH_ASSOC);
      $totalRecords = $totalRecords["total"];

      $response = [
         'recordsTotal' => $totalRecords, // total number of records without any filters
         'recordsFiltered' => $filteredRecords, // total number of records after applying filters
         'data' => $data // the array of records fetched
      ];

      return json_encode($response);
   }

   /**
   * Returns an entry from database.
   * 
   * @return string
   */
   public function getRegistro(string $id) : string{
      $bindings = [];

      $sql = "SELECT * FROM registros WHERE id = :id";
      $bindings[':id'] = $id;

      if(isset($_GET)){
         if(isset($_GET["deleted"]) && $_GET["deleted"] !== null && $_GET["deleted"] !== ""){ 
            $sql .= " AND deleted = :deleted"; 
            $bindings[':deleted'] = $_GET['deleted'];
         }
   
         if(isset($_GET["type"]) && $_GET["type"] !== null && $_GET["type"] !== ""){ 
            $sql .= " AND type LIKE :type"; 
            $bindings[':type'] = "%".$_GET['type']."%"; 
         }
      }

      $stmt = $this->con->prepare($sql);
      $stmt->execute($bindings);
      $data = $stmt->fetch(PDO::FETCH_ASSOC);

      if($data) return json_encode($data);
      
      Flight::response()->status(404);
      return json_encode(["message" => "Resource not found"]);
   }

   /**
   * Inserts a new entry inside the database.
   * 
   * @return string
   */
   public function insertRegistro() : string{
      $created_at = date("Y-m-d H:i:s");
      
      $sql = "INSERT INTO registros
               (type, 
               message, 
               is_identified,
               whistleblower_name, 
               whistleblower_birth, 
               created_at,
               deleted) 
               VALUES (?, ?, ?, ?, ?, ?, ?)";
      $stmt = $this->con->prepare($sql);
      $stmt->execute([$_POST["type"], $_POST["message"], $_POST["is_identified"], $_POST["whistleblower_name"], $_POST["whistleblower_birth"], $created_at, $_POST["deleted"]]);
      $id = $this->con->lastInsertId();

      Flight::response()->status(201); //created

      $sql = "SELECT * FROM registros WHERE id = ?";
      $stmt = $this->con->prepare($sql);
      $stmt->execute([$id]);
      $data = $stmt->fetch(PDO::FETCH_ASSOC);
      return json_encode($data);
   }

   /**
   * Updates an entry in the database.
   * 
   * @return string
   */
   public function updateRegistro(string $id) : string{
      $bindings = [];
      $update_clauses = [];

      if (isset($_POST["type"]) && $_POST["type"] !== null && $_POST["type"] !== "") {
         $update_clauses[] = "type = :type";
         $bindings[':type'] = $_POST["type"];
      }

      if (isset($_POST["message"]) && $_POST["message"] !== null && $_POST["message"] !== "") {
         $update_clauses[] = "message = :message";
         $bindings[':message'] = $_POST["message"];
      }
      
      if (isset($_POST["is_identified"]) && $_POST["is_identified"] !== null && $_POST["is_identified"] !== "") {
         $update_clauses[] = "is_identified = :is_identified";
         $bindings[':is_identified'] = $_POST["is_identified"];
      }

      if (isset($_POST["whistleblower_name"]) && $_POST["whistleblower_name"] !== null && $_POST["whistleblower_name"] !== "") {
         $update_clauses[] = "whistleblower_name = :whistleblower_name";
         $bindings[':whistleblower_name'] = $_POST["whistleblower_name"];
      }

      if(isset($_POST["deleted"]) && $_POST["deleted"] !== "" && $_POST["deleted"] !== null){ 
         $update_clauses[] = "deleted = :deleted";
         $bindings[':deleted'] = $_POST["deleted"];
      }

      $update_clauses = implode(", ", $update_clauses);
      $sql = "UPDATE registros SET $update_clauses WHERE id = :id";
      $bindings[":id"] = $id;

      $stmt = $this->con->prepare($sql);
      $stmt->execute($bindings);

      // return json_encode(["message" => "Resource $id updated", "rows" => $stmt->rowCount()]);

      $sql = "SELECT * FROM registros WHERE id = ?";
      $stmt = $this->con->prepare($sql);
      $stmt->execute([$id]);
      $data = $stmt->fetch(PDO::FETCH_ASSOC);
      return json_encode($data);
   }

   /**
   * Deletes an entry in the database.
   * 
   * @return string
   */
   public function deleteRegistro(string $id){
      $sql = "DELETE FROM registros WHERE id = ?";
      $stmt = $this->con->prepare($sql);
      $stmt->execute([$id]);

      //returns the id of the deleted row if if exists
      if($stmt->rowCount() > 0) return json_encode(["message" => "Resource $id deleted"]);

      //returns an error message if resource with this id was not found
      Flight::response()->status(404);
      return json_encode(["message" => "Resource not found"]);
   }
}