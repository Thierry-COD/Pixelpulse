<?php

namespace Core;

class Request
{
    /**
     * Devuelve el cuerpo JSON de la petición como arreglo.
     * Si el cuerpo no está vacío pero no es un JSON válido, responde 400.
     */
    public static function body(): array
    {
        $entrada = trim(file_get_contents('php://input'));

        if ($entrada === '') {
            return [];
        }

        $datos = json_decode($entrada, true);

        if (!is_array($datos)) {
            Response::error('El cuerpo de la petición debe ser un JSON válido', 400);
        }

        return $datos;
    }
}
