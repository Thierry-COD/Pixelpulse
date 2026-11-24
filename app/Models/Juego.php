<?php

namespace App\Models;

use PDO;

class Juego
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Consulta base: trae el juego con el nombre de su género y su plataforma
    private const BASE = "
        SELECT
            J.IDJUEGO          AS id_juego,
            J.TITULO           AS titulo,
            J.DESARROLLADOR    AS desarrollador,
            J.FECHALANZAMIENTO AS fecha_lanzamiento,
            J.PRECIO           AS precio,
            J.ESTADO           AS estado,
            J.IDGENERO         AS id_genero,
            G.NOMBREGENERO     AS genero,
            J.IDPLATAFORMA     AS id_plataforma,
            P.NOMBREPLATAFORMA AS plataforma
        FROM TBL_JUEGO J
        INNER JOIN TBL_GENERO G
            ON G.IDGENERO = J.IDGENERO
        INNER JOIN TBL_PLATAFORMA P
            ON P.IDPLATAFORMA = J.IDPLATAFORMA
    ";

    /** Lista solo los juegos activos. */
    public function obtenerTodos(): array
    {
        $sql = self::BASE . " WHERE J.ESTADO = 1 ORDER BY J.TITULO";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = self::BASE . " WHERE J.IDJUEGO = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function crear(array $data): int
    {
        $sql = "
            INSERT INTO TBL_JUEGO
                (TITULO, DESARROLLADOR, FECHALANZAMIENTO, PRECIO, ESTADO, IDGENERO, IDPLATAFORMA)
            VALUES
                (:titulo, :desarrollador, :fecha, :precio, :estado, :id_genero, :id_plataforma)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titulo'        => $data['titulo'],
            ':desarrollador' => $data['desarrollador'],
            ':fecha'         => $data['fecha_lanzamiento'],
            ':precio'        => $data['precio'],
            ':estado'        => $data['estado'],
            ':id_genero'     => $data['id_genero'],
            ':id_plataforma' => $data['id_plataforma']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE TBL_JUEGO
            SET TITULO           = :titulo,
                DESARROLLADOR    = :desarrollador,
                FECHALANZAMIENTO = :fecha,
                PRECIO           = :precio,
                ESTADO           = :estado,
                IDGENERO         = :id_genero,
                IDPLATAFORMA     = :id_plataforma
            WHERE IDJUEGO = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':titulo'        => $data['titulo'],
            ':desarrollador' => $data['desarrollador'],
            ':fecha'         => $data['fecha_lanzamiento'],
            ':precio'        => $data['precio'],
            ':estado'        => $data['estado'],
            ':id_genero'     => $data['id_genero'],
            ':id_plataforma' => $data['id_plataforma'],
            ':id'            => $id
        ]);
    }

    /** Eliminación lógica: el juego tiene reseñas y noticias asociadas. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE TBL_JUEGO SET ESTADO = 0 WHERE IDJUEGO = :id");

        return $stmt->execute([':id' => $id]);
    }
}
