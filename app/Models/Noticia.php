<?php

namespace App\Models;

use PDO;

class Noticia
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Consulta base: la noticia con su autor y (si tiene) el juego relacionado.
    // LEFT JOIN porque IDJUEGO puede ser NULL.
    private const BASE = "
        SELECT
            N.IDNOTICIA AS id_noticia,
            N.TITULO    AS titulo,
            N.RESUMEN   AS resumen,
            N.CATEGORIA AS categoria,
            N.FECHA     AS fecha,
            N.PUBLICADA AS publicada,
            N.IDUSUARIO AS id_usuario,
            CONCAT(U.NOMBRES, ' ', U.APELLIDOS) AS autor,
            N.IDJUEGO   AS id_juego,
            J.TITULO    AS juego
        FROM TBL_NOTICIA N
        INNER JOIN TBL_USUARIO U
            ON U.IDUSUARIO = N.IDUSUARIO
        LEFT JOIN TBL_JUEGO J
            ON J.IDJUEGO = N.IDJUEGO
    ";

    public function obtenerTodos(): array
    {
        $sql = self::BASE . " ORDER BY N.FECHA DESC, N.IDNOTICIA DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = self::BASE . " WHERE N.IDNOTICIA = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function crear(array $data): int
    {
        $sql = "
            INSERT INTO TBL_NOTICIA
                (TITULO, RESUMEN, CATEGORIA, FECHA, PUBLICADA, IDUSUARIO, IDJUEGO)
            VALUES
                (:titulo, :resumen, :categoria, :fecha, :publicada, :id_usuario, :id_juego)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titulo'     => $data['titulo'],
            ':resumen'    => $data['resumen'],
            ':categoria'  => $data['categoria'],
            ':fecha'      => $data['fecha'],
            ':publicada'  => $data['publicada'],
            ':id_usuario' => $data['id_usuario'],
            ':id_juego'   => $data['id_juego']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE TBL_NOTICIA
            SET TITULO    = :titulo,
                RESUMEN   = :resumen,
                CATEGORIA = :categoria,
                FECHA     = :fecha,
                PUBLICADA = :publicada,
                IDUSUARIO = :id_usuario,
                IDJUEGO   = :id_juego
            WHERE IDNOTICIA = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':titulo'     => $data['titulo'],
            ':resumen'    => $data['resumen'],
            ':categoria'  => $data['categoria'],
            ':fecha'      => $data['fecha'],
            ':publicada'  => $data['publicada'],
            ':id_usuario' => $data['id_usuario'],
            ':id_juego'   => $data['id_juego'],
            ':id'         => $id
        ]);
    }

    /** Eliminación física: ninguna otra tabla depende de las noticias. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM TBL_NOTICIA WHERE IDNOTICIA = :id");

        return $stmt->execute([':id' => $id]);
    }
}
