<?php

namespace Core;

use DateTime;

/**
 * Validaciones reutilizables. Cada método responde true/false.
 * Los controladores los usan para armar mensajes de error claros.
 */
class Validator
{
    /** Texto obligatorio, no vacío y de hasta $max caracteres. */
    public static function texto(mixed $valor, int $max): bool
    {
        return is_string($valor)
            && trim($valor) !== ''
            && mb_strlen(trim($valor)) <= $max;
    }

    /** Texto opcional: puede faltar, ser null o vacío; si viene, respeta $max. */
    public static function textoOpcional(mixed $valor, int $max): bool
    {
        return $valor === null
            || $valor === ''
            || (is_string($valor) && mb_strlen(trim($valor)) <= $max);
    }

    /** Entero positivo (acepta 4 o "4"). Sirve para llaves foráneas. */
    public static function entero(mixed $valor): bool
    {
        return !is_bool($valor)
            && filter_var($valor, FILTER_VALIDATE_INT) !== false
            && (int) $valor > 0;
    }

    /** Número entre $min y $max (incluidos). El 0 es válido si $min = 0. */
    public static function numero(mixed $valor, float $min, float $max): bool
    {
        return is_numeric($valor)
            && (float) $valor >= $min
            && (float) $valor <= $max;
    }

    /** Fecha con formato AAAA-MM-DD y que exista en el calendario. */
    public static function fecha(mixed $valor): bool
    {
        if (!is_string($valor)) {
            return false;
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $valor);

        return $fecha !== false && $fecha->format('Y-m-d') === $valor;
    }

    /** El valor debe ser exactamente uno de la lista (CHECK de la tabla). */
    public static function enLista(mixed $valor, array $lista): bool
    {
        return is_string($valor) && in_array($valor, $lista, true);
    }

    /** Booleano: acepta 0, 1, "0", "1", true, false. */
    public static function booleano(mixed $valor): bool
    {
        return in_array($valor, [0, 1, '0', '1', true, false], true);
    }
}
