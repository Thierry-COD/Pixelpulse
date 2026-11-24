<?php

namespace Config;

use Core\Env;
use PDO;
use PDOException;

/**
 * Crea la conexión PDO. Los datos salen del archivo .env.
 * Funciona con MySQL (DB_DRIVER=mysql) o SQL Server (DB_DRIVER=sqlsrv).
 */
class Database
{
    public function connect(): PDO
    {
        $driver = Env::get('DB_DRIVER', 'mysql');
        $host   = Env::get('DB_HOST', 'localhost');
        $port   = Env::get('DB_PORT', '');
        $nombre = Env::get('DB_NAME', 'PA3_PIXELPULSE');
        $user   = Env::get('DB_USER', '');
        $pass   = Env::get('DB_PASS', '');

        // Usuario vacío = null (autenticación de Windows en SQL Server)
        $user = ($user === '') ? null : $user;
        $pass = ($pass === '') ? null : $pass;

        try {

            if ($driver === 'sqlsrv') {

                $servidor = ($port !== '') ? "{$host},{$port}" : $host;

                $dsn = "sqlsrv:Server={$servidor};Database={$nombre};TrustServerCertificate=1";

            } else {

                $puerto = ($port !== '') ? ";port={$port}" : '';

                $dsn = "mysql:host={$host}{$puerto};dbname={$nombre};charset=utf8mb4";
            }

            $conexion = new PDO($dsn, $user, $pass);

            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Esta opción solo existe en el driver de MySQL
            if ($driver !== 'sqlsrv') {
                $conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            }

            return $conexion;

        } catch (PDOException $e) {

            // El detalle real va al log del servidor, NO a la respuesta
            error_log('Error de conexión: ' . $e->getMessage());

            throw new PDOException('Error al conectar con la base de datos.');
        }
    }
}
