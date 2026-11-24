<?php

namespace App\Models;

use PDO;

class Plataforma
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
                IDPLATAFORMA     AS id_plataforma,
                NOMBREPLATAFORMA AS nombre,
                TIPO             AS tipo,
                FABRICANTE       AS fabricante
            FROM TBL_PLATAFORMA
            ORDER BY NOMBREPLATAFORMA
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT
                IDPLATAFORMA     AS id_plataforma,
                NOMBREPLATAFORMA AS nombre,
                TIPO             AS tipo,
                FABRICANTE       AS fabricante
            FROM TBL_PLATAFORMA
            WHERE IDPLATAFORMA = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function obtenerPorNombre(string $nombre, ?int $excluirId = null): array|false
    {
        $sql = "SELECT IDPLATAFORMA AS id_plataforma FROM TBL_PLATAFORMA WHERE NOMBREPLATAFORMA = :nombre";
        $parametros = [':nombre' => $nombre];

        if ($excluirId !== null) {
            $sql .= " AND IDPLATAFORMA <> :id";
            $parametros[':id'] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetch();
    }

    public function crear(array $data): int
    {
        $sql = "
            INSERT INTO TBL_PLATAFORMA (NOMBREPLATAFORMA, TIPO, FABRICANTE)
            VALUES (:nombre, :tipo, :fabricante)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'     => $data['nombre'],
            ':tipo'       => $data['tipo'],
            ':fabricante' => $data['fabricante']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE TBL_PLATAFORMA
            SET NOMBREPLATAFORMA = :nombre,
                TIPO             = :tipo,
                FABRICANTE       = :fabricante
            WHERE IDPLATAFORMA = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre'     => $data['nombre'],
            ':tipo'       => $data['tipo'],
            ':fabricante' => $data['fabricante'],
            ':id'         => $id
        ]);
    }

    public function contarJuegos(int $id): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM TBL_JUEGO WHERE IDPLATAFORMA = :id"
        );
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetch()['total'];
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM TBL_PLATAFORMA WHERE IDPLATAFORMA = :id");

        return $stmt->execute([':id' => $id]);
    }
}
