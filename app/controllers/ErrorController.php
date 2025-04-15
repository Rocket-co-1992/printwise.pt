<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;

class ErrorController extends Controller
{
    public function notFound(): string
    {
        // Definir o status HTTP 404
        http_response_code(404);
        
        return $this->render('error/not_found', [
            'message' => 'Página não encontrada'
        ]);
    }
    
    public function forbidden(): string
    {
        // Definir o status HTTP 403
        http_response_code(403);
        
        return $this->render('error/forbidden', [
            'message' => 'Acesso negado'
        ]);
    }
    
    public function serverError(\Exception $exception = null): string
    {
        // Definir o status HTTP 500
        http_response_code(500);
        
        $config = require_once __DIR__ . '/../../config/config.php';
        $debug = $config['debug'] ?? false;
        
        $message = 'Ocorreu um erro no servidor';
        $details = $debug && $exception ? $exception->getMessage() : null;
        $trace = $debug && $exception ? $exception->getTraceAsString() : null;
        
        return $this->render('error/server_error', [
            'message' => $message,
            'details' => $details,
            'trace' => $trace
        ]);
    }
}