<?php

namespace Core;

class Router
{
    /** Lista de rutas registradas. */
    private array $routes = [];

    public function get(string $route, callable $action): void
    {
        $this->add('GET', $route, $action);
    }

    public function post(string $route, callable $action): void
    {
        $this->add('POST', $route, $action);
    }

    public function put(string $route, callable $action): void
    {
        $this->add('PUT', $route, $action);
    }

    public function delete(string $route, callable $action): void
    {
        $this->add('DELETE', $route, $action);
    }

    private function add(string $method, string $route, callable $action): void
    {
        // /api/juegos/  ->  /api/juegos
        if ($route !== '/') {
            $route = rtrim($route, '/');
        }

        $this->routes[] = [
            'method' => $method,
            'route'  => $route,
            'action' => $action
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);

        // Solo el PATH (sin ?parametros), decodificado y sin "/" final
        $uri = rawurldecode(parse_url($uri, PHP_URL_PATH) ?? '/');

        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        // ¿La URL existe con otro método? (para responder 405 y no 404)
        $urlExiste = false;

        foreach ($this->routes as $route) {

            // /api/juegos/{id}  ->  #^/api/juegos/(\d+)$#
            $patron = preg_quote($route['route'], '#');
            $patron = str_replace('\{id\}', '(\d+)', $patron);
            $patron = '#^' . $patron . '$#';

            if (!preg_match($patron, $uri, $coincidencias)) {
                continue;
            }

            $urlExiste = true;

            if ($route['method'] !== $method) {
                continue;
            }

            // Dejamos solo los parámetros (el id) y llamamos al controlador
            array_shift($coincidencias);

            call_user_func_array($route['action'], $coincidencias);

            return;
        }

        if ($urlExiste) {
            Response::error('Método no permitido para esta ruta', 405);
        }

        Response::error('Ruta no encontrada', 404);
    }
}
