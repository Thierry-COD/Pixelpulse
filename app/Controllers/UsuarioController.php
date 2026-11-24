<?php

namespace App\Controllers;

use App\Models\Usuario;
use Core\Request;
use Core\Response;
use Core\Validator;
use PDO;

class UsuarioController
{
    private const ROLES = ['Administrador', 'Editor'];

    private Usuario $usuario;

    public function __construct(PDO $db)
    {
        $this->usuario = new Usuario($db);
    }

    // GET /api/usuarios
    public function index(): void
    {
        Response::success(
            $this->usuario->obtenerTodos(),
            'Usuarios obtenidos correctamente'
        );
    }

    // GET /api/usuarios/{id}
    public function show(int $id): void
    {
        $usuario = $this->usuario->obtenerPorId($id);

        if (!$usuario) {
            Response::error('Usuario no encontrado', 404);
        }

        Response::success($usuario, 'Usuario obtenido correctamente');
    }

    // POST /api/usuarios
    public function store(): void
    {
        // En la creación la clave es obligatoria
        [$datos, $error] = $this->validar(Request::body(), true);

        if ($error) {
            Response::error($error, 400);
        }

        if ($this->usuario->obtenerPorCorreo($datos['correo'])) {
            Response::error('El correo ya está registrado', 409);
        }

        $id = $this->usuario->crear($datos);

        Response::success(
            ['id_usuario' => $id],
            'Usuario registrado correctamente',
            201
        );
    }

    // PUT /api/usuarios/{id}
    public function update(int $id): void
    {
        if (!$this->usuario->obtenerPorId($id)) {
            Response::error('Usuario no encontrado', 404);
        }

        // En la actualización la clave es opcional
        [$datos, $error] = $this->validar(Request::body(), false);

        if ($error) {
            Response::error($error, 400);
        }

        if ($this->usuario->obtenerPorCorreo($datos['correo'], $id)) {
            Response::error('Ese correo ya pertenece a otro usuario', 409);
        }

        $this->usuario->actualizar($id, $datos);

        Response::success(null, 'Usuario actualizado correctamente');
    }

    // DELETE /api/usuarios/{id}  (eliminación lógica: ESTADO = 0)
    public function destroy(int $id): void
    {
        if (!$this->usuario->obtenerPorId($id)) {
            Response::error('Usuario no encontrado', 404);
        }

        $this->usuario->eliminar($id);

        Response::success(null, 'Usuario desactivado correctamente');
    }

    private function validar(array $data, bool $claveObligatoria): array
    {
        if (!Validator::texto($data['nombres'] ?? null, 80)) {
            return [null, 'Los nombres son obligatorios (máximo 80 caracteres)'];
        }

        if (!Validator::texto($data['apellidos'] ?? null, 80)) {
            return [null, 'Los apellidos son obligatorios (máximo 80 caracteres)'];
        }

        if (
            !Validator::texto($data['correo'] ?? null, 100) ||
            !filter_var(trim($data['correo']), FILTER_VALIDATE_EMAIL)
        ) {
            return [null, 'El correo es obligatorio y debe tener un formato válido'];
        }

        if (!Validator::enLista($data['rol'] ?? null, self::ROLES)) {
            return [null, 'El rol debe ser: ' . implode(' o ', self::ROLES)];
        }

        // Clave: obligatoria al crear; al actualizar solo si se envía
        $clave = $data['clave'] ?? null;

        if ($claveObligatoria || ($clave !== null && $clave !== '')) {

            if (!is_string($clave) || mb_strlen($clave) < 6 || strlen($clave) > 72) {
                return [null, 'La clave es obligatoria y debe tener entre 6 y 72 caracteres'];
            }

        } else {
            $clave = null;
        }

        $estado = $data['estado'] ?? 1;

        if (!Validator::booleano($estado)) {
            return [null, 'El estado debe ser 0 o 1'];
        }

        return [[
            'nombres'   => trim($data['nombres']),
            'apellidos' => trim($data['apellidos']),
            'correo'    => strtolower(trim($data['correo'])),
            'clave'     => $clave,
            'rol'       => $data['rol'],
            'estado'    => (int) $estado
        ], null];
    }
}
