<?php

namespace App\Models;

use PDO;

class Genero
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function obtenerTodos(): array
    {
        $sql = "
            SELECT
                IDGENERO     AS id_genero,
                NOMBREGENERO AS nombre,
                DESCRIPCION  AS descripcion
            FROM TBL_GENERO
            ORDER BY NOMBREGENERO
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT
                IDGENERO     AS id_genero,
                NOMBREGENERO AS nombre,
                DESCRIPCION  AS descripcion
            FROM TBL_GENERO
            WHERE IDGENERO = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    /** Busca un género por nombre (para evitar duplicados). */
    public function obtenerPorNombre(string $nombre, ?int $excluirId = null): array|false
    {
        $sql = "SELECT IDGENERO AS id_genero FROM TBL_GENERO WHERE NOMBREGENERO = :nombre";
        $parametros = [':nombre' => $nombre];

        // Al actualizar, ignoramos el propio registro
        if ($excluirId !== null) {
            $sql .= " AND IDGENERO <> :id";
            $parametros[':id'] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetch();
    }

    public function crear(array $data): int
    {
        $sql = "
            INSERT INTO TBL_GENERO (NOMBREGENERO, DESCRIPCION)
            VALUES (:nombre, :descripcion)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'      => $data['nombre'],
            ':descripcion' => $data['descripcion']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE TBL_GENERO
            SET NOMBREGENERO = :nombre,
                DESCRIPCION  = :descripcion
            WHERE IDGENERO = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre'      => $data['nombre'],
            ':descripcion' => $data['descripcion'],
            ':id'          => $id
        ]);
    }

    /** Cuántos juegos usan este género (para proteger la clave foránea). */
    public function contarJuegos(int $id): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM TBL_JUEGO WHERE IDGENERO = :id"
        );
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetch()['total'];
    }

    /** Eliminación física (la tabla no tiene columna de estado). */
    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM TBL_GENERO WHERE IDGENERO = :id");

        return $stmt->execute([':id' => $id]);
    }
}
