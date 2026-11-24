<?php

namespace App\Middleware;

use Core\Env;

class CorsMiddleware
{
    public static function handle(): void
    {
        // Origen permitido (se configura en .env)
        header('Access-Control-Allow-Origin: ' . Env::get('CORS_ORIGIN', '*'));

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        header('Content-Type: application/json; charset=UTF-8');

        // Respuesta a las solicitudes "preflight" del navegador
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
