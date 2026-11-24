<?php

namespace App\Models;

use PDO;

class Resena
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Consulta base: la reseña con el título del juego y el nombre del autor
    private const BASE = "
        SELECT
            R.IDRESENA   AS id_resena,
            R.TITULO     AS titulo,
            R.CONTENIDO  AS contenido,
            R.PUNTUACION AS puntuacion,
            R.ESTADO     AS estado,
            R.FECHA      AS fecha,
            R.IDJUEGO    AS id_juego,
            J.TITULO     AS juego,
            R.IDUSUARIO  AS id_usuario,
            CONCAT(U.NOMBRES, ' ', U.APELLIDOS) AS autor
        FROM TBL_RESENA R
        INNER JOIN TBL_JUEGO J
            ON J.IDJUEGO = R.IDJUEGO
        INNER JOIN TBL_USUARIO U
            ON U.IDUSUARIO = R.IDUSUARIO
    ";

    public function obtenerTodos(): array
    {
        $sql = self::BASE . " ORDER BY R.FECHA DESC, R.IDRESENA DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = self::BASE . " WHERE R.IDRESENA = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function crear(array $data): int
    {
        $sql = "
            INSERT INTO TBL_RESENA
                (TITULO, CONTENIDO, PUNTUACION, ESTADO, FECHA, IDJUEGO, IDUSUARIO)
            VALUES
                (:titulo, :contenido, :puntuacion, :estado, :fecha, :id_juego, :id_usuario)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titulo'     => $data['titulo'],
            ':contenido'  => $data['contenido'],
            ':puntuacion' => $data['puntuacion'],
            ':estado'     => $data['estado'],
            ':fecha'      => $data['fecha'],
            ':id_juego'   => $data['id_juego'],
            ':id_usuario' => $data['id_usuario']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE TBL_RESENA
            SET TITULO     = :titulo,
                CONTENIDO  = :contenido,
                PUNTUACION = :puntuacion,
                ESTADO     = :estado,
                FECHA      = :fecha,
                IDJUEGO    = :id_juego,
                IDUSUARIO  = :id_usuario
            WHERE IDRESENA = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':titulo'     => $data['titulo'],
            ':contenido'  => $data['contenido'],
            ':puntuacion' => $data['puntuacion'],
            ':estado'     => $data['estado'],
            ':fecha'      => $data['fecha'],
            ':id_juego'   => $data['id_juego'],
            ':id_usuario' => $data['id_usuario'],
            ':id'         => $id
        ]);
    }

    /** Eliminación física: ninguna otra tabla depende de las reseñas. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM TBL_RESENA WHERE IDRESENA = :id");

        return $stmt->execute([':id' => $id]);
    }
}
