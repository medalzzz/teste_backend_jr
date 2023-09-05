<?php
namespace app\error_handler;

use \Flight;

class ErrorHandler{
   /**
   * Formats exceptions as json and stops script if exception happens
   * 
   * @return string
   */
   public static function handleException(Throwable $exception) : void {
      Flight::response()->status(500); //internal error
      
      echo json_encode([
         "code" => $exception->getCode(),
         "msg" => $exception->getMessage(),
         "file" => $exception->getFile(),
         "line" => $exception->getLine()
      ]);
   }
    
   public static function handleError(int $errno, string $errmsg, string $errfile, int $errline) : bool {
      throw new ErrorException($errmsg, 0, $errno, $errfile, $errline);
   }
}