<?php

namespace Core;

/**
 * Lee el archivo .env y guarda sus valores en memoria.
 * Así los datos sensibles (clave de la BD, usuario...) NO viven dentro del código.
 */
class Env
{
    private static array $valores = [];

    public static function cargar(string $ruta): void
    {
        if (!is_file($ruta)) {
            return;
        }

        $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            // Ignorar comentarios y líneas sin "="
            if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
                continue;
            }

            [$clave, $valor] = explode('=', $linea, 2);

            self::$valores[trim($clave)] = trim($valor, " \t\"'");
        }
    }

    public static function get(string $clave, ?string $porDefecto = null): ?string
    {
        return self::$valores[$clave] ?? $porDefecto;
    }
}
