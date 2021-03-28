<?php

namespace App\Library;

class ErrorHandle
{
    public static function errorForJson($e)
    {
      $message = ['Show only in local mode'];
      if(env('APP_ENV') === 'local') $message = ['file' => $e->getFile(), 'line' => $e->getLine(), 'message' => $e->getMessage()];
      return [
          'status' => 'error_server',
          'message' => $message
      ];
    }
}