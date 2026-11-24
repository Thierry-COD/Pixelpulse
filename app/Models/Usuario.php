<?php

namespace App\Models;

use PDO;

class Usuario
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Columnas que se devuelven. La CLAVE nunca se incluye.
    private const COLUMNAS = "
        IDUSUARIO AS id_usuario,
        NOMBRES   AS nombres,
        APELLIDOS AS apellidos,
        CORREO    AS correo,
        ROL       AS rol,
        ESTADO    AS estado
    ";

    /** Lista solo los usuarios activos. */
    public function obtenerTodos(): array
    {
        $sql = "SELECT " . self::COLUMNAS . " FROM TBL_USUARIO WHERE ESTADO = 1 ORDER BY NOMBRES";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = "SELECT " . self::COLUMNAS . " FROM TBL_USUARIO WHERE IDUSUARIO = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    /** Busca por correo (para evitar duplicados). */
    public function obtenerPorCorreo(string $correo, ?int $excluirId = null): array|false
    {
        $sql = "SELECT IDUSUARIO AS id_usuario FROM TBL_USUARIO WHERE CORREO = :correo";
        $parametros = [':correo' => $correo];

        if ($excluirId !== null) {
            $sql .= " AND IDUSUARIO <> :id";
            $parametros[':id'] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetch();
    }

    public function crear(array $data): int
    {
        $sql = "
            INSERT INTO TBL_USUARIO (NOMBRES, APELLIDOS, CORREO, CLAVE, ROL, ESTADO)
            VALUES (:nombres, :apellidos, :correo, :clave, :rol, :estado)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombres'   => $data['nombres'],
            ':apellidos' => $data['apellidos'],
            ':correo'    => $data['correo'],
            // La clave se guarda con hash, nunca en texto plano
            ':clave'     => password_hash($data['clave'], PASSWORD_DEFAULT),
            ':rol'       => $data['rol'],
            ':estado'    => $data['estado']
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Actualiza los datos. La clave solo cambia si se envía una nueva. */
    public function actualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE TBL_USUARIO
            SET NOMBRES   = :nombres,
                APELLIDOS = :apellidos,
                CORREO    = :correo,
                ROL       = :rol,
                ESTADO    = :estado
        ";

        $parametros = [
            ':nombres'   => $data['nombres'],
            ':apellidos' => $data['apellidos'],
            ':correo'    => $data['correo'],
            ':rol'       => $data['rol'],
            ':estado'    => $data['estado'],
            ':id'        => $id
        ];

        if ($data['clave'] !== null) {
            $sql .= ", CLAVE = :clave";
            $parametros[':clave'] = password_hash($data['clave'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE IDUSUARIO = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($parametros);
    }

    /** Eliminación lógica: el usuario tiene reseñas y noticias asociadas. */
    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE TBL_USUARIO SET ESTADO = 0 WHERE IDUSUARIO = :id");

        return $stmt->execute([':id' => $id]);
    }
}
