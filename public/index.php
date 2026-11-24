<?php

// =====================================================
// PUNTO DE ENTRADA DE LA API (todas las peticiones pasan por aquí)
// =====================================================

use App\Middleware\CorsMiddleware;
use Config\Database;
use Core\Env;
use Core\Response;
use Core\Router;

// -----------------------------------------------------
// AUTOLOAD
// Si existe vendor/ (Composer) lo usa; si no, usa uno manual.
// Así el proyecto funciona aunque no ejecutes "composer install".
// -----------------------------------------------------
$autoload = __DIR__ . '/../vendor/autoload.php';

if (is_file($autoload)) {

    require_once $autoload;

} else {

    spl_autoload_register(function (string $clase): void {

        $prefijos = [
            'App\\'    => __DIR__ . '/../app/',
            'Config\\' => __DIR__ . '/../config/',
            'Core\\'   => __DIR__ . '/../core/',
        ];

        foreach ($prefijos as $prefijo => $carpeta) {

            if (str_starts_with($clase, $prefijo)) {

                $archivo = $carpeta
                    . str_replace('\\', '/', substr($clase, strlen($prefijo)))
                    . '.php';

                if (is_file($archivo)) {
                    require_once $archivo;
                }
            }
        }
    });
}

// -----------------------------------------------------
// VARIABLES DE ENTORNO (.env)
// -----------------------------------------------------
Env::cargar(__DIR__ . '/../.env');

// Zona horaria de Perú (para que date() use la fecha correcta)
date_default_timezone_set('America/Lima');

$debug = Env::get('APP_DEBUG', 'false') === 'true';

// -----------------------------------------------------
// CORS
// -----------------------------------------------------
CorsMiddleware::handle();

try {

    // -------------------------------------------------
    // CONEXIÓN A LA BASE DE DATOS
    // -------------------------------------------------
    $db = (new Database())->connect();

    // -------------------------------------------------
    // ROUTER + RUTAS
    // -------------------------------------------------
    $router = new Router();

    require_once __DIR__ . '/../routes/api.php';

    // -------------------------------------------------
    // URI sin la carpeta base (ej: /pixelpulse-api/public)
    // -------------------------------------------------
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }

    if ($uri === '' || $uri === '/index.php') {
        $uri = '/';
    }

    // -------------------------------------------------
    // EJECUTAR
    // -------------------------------------------------
    $router->dispatch($_SERVER['REQUEST_METHOD'], $uri);

} catch (PDOException $e) {

    error_log('PDOException: ' . $e->getMessage());

    // 23000 = violación de restricción (clave duplicada o clave foránea)
    if ($e->getCode() === '23000') {
        Response::error(
            'La operación no se puede realizar: el dato está duplicado o relacionado con otros registros',
            409
        );
    }

    Response::json([
        'success' => false,
        'message' => 'Error interno del servidor',
        'detalle' => $debug ? $e->getMessage() : null
    ], 500);

} catch (Throwable $e) {

    error_log('Error: ' . $e->getMessage());

    Response::json([
        'success' => false,
        'message' => 'Error interno del servidor',
        'detalle' => $debug ? $e->getMessage() : null
    ], 500);
}
