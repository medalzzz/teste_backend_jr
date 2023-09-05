<?php
namespace app\database;

use PDO;

class Connection{
   /**
   * Connects to database
   * 
   * @return PDO|false
   */
   public function getConnection(){
      try{
         return new \PDO(
            'sqlite:../data/db.sq3',
            '',
            '',
            [
               \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, //disables bool, int, float conversion
               \PDO::ATTR_EMULATE_PREPARES => false,
               \PDO::ATTR_STRINGIFY_FETCHES => false, //throws exception if conn fails
            ]
         );
      }
      catch(PDOException $e){
         return false;
         // echo "Connection failed: " . $e->getMessage();
      }
   }
}