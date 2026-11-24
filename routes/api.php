<?php

use App\Controllers\GeneroController;
use App\Controllers\JuegoController;
use App\Controllers\NoticiaController;
use App\Controllers\PlataformaController;
use App\Controllers\ResenaController;
use App\Controllers\UsuarioController;
use Core\Response;
use Core\Router;

// Registra las 5 rutas CRUD de un módulo:

function registrarCrud(Router $router, string $base, object $controlador): void
{
    $router->get($base,             [$controlador, 'index']);
    $router->get($base . '/{id}',   [$controlador, 'show']);
    $router->post($base,            [$controlador, 'store']);
    $router->put($base . '/{id}',   [$controlador, 'update']);
    $router->delete($base . '/{id}', [$controlador, 'destroy']);
}

// RUTA DE PRUEBA

$router->get('/', function () {
    Response::success(
        [
            'modulos' => [
                '/api/usuarios',
                '/api/generos',
                '/api/plataformas',
                '/api/juegos',
                '/api/resenas',
                '/api/noticias'
            ]
        ],
        'API de PixelPulse funcionando'
    );
});

// ==========================================================
// MÓDULOS (uno por cada tabla principal)
// ==========================================================
registrarCrud($router, '/api/usuarios',    new UsuarioController($db));
registrarCrud($router, '/api/generos',     new GeneroController($db));
registrarCrud($router, '/api/plataformas', new PlataformaController($db));
registrarCrud($router, '/api/juegos',      new JuegoController($db));
registrarCrud($router, '/api/resenas',     new ResenaController($db));
registrarCrud($router, '/api/noticias',    new NoticiaController($db));
