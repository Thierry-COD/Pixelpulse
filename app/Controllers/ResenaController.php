<?php

namespace App\Controllers;

use App\Models\Juego;
use App\Models\Resena;
use App\Models\Usuario;
use Core\Request;
use Core\Response;
use Core\Validator;
use PDO;

class ResenaController
{
    private const ESTADOS = ['Borrador', 'Publicada'];

    private Resena $resena;
    private Juego $juego;
    private Usuario $usuario;

    public function __construct(PDO $db)
    {
        $this->resena  = new Resena($db);
        $this->juego   = new Juego($db);
        $this->usuario = new Usuario($db);
    }

    // GET /api/resenas
    public function index(): void
    {
        Response::success(
            $this->resena->obtenerTodos(),
            'Reseñas obtenidas correctamente'
        );
    }

    // GET /api/resenas/{id}
    public function show(int $id): void
    {
        $resena = $this->resena->obtenerPorId($id);

        if (!$resena) {
            Response::error('Reseña no encontrada', 404);
        }

        Response::success($resena, 'Reseña obtenida correctamente');
    }

    // POST /api/resenas
    public function store(): void
    {
        [$datos, $error] = $this->validar(Request::body());

        if ($error) {
            Response::error($error, 400);
        }

        // Si no envía fecha, se usa la de hoy
        $datos['fecha'] = $datos['fecha'] ?? date('Y-m-d');

        $id = $this->resena->crear($datos);

        Response::success(
            ['id_resena' => $id],
            'Reseña registrada correctamente',
            201
        );
    }

    // PUT /api/resenas/{id}
    public function update(int $id): void
    {
        $existente = $this->resena->obtenerPorId($id);

        if (!$existente) {
            Response::error('Reseña no encontrada', 404);
        }

        [$datos, $error] = $this->validar(Request::body());

        if ($error) {
            Response::error($error, 400);
        }

        // Si no envía fecha, se conserva la que ya tenía
        $datos['fecha'] = $datos['fecha'] ?? $existente['fecha'];

        $this->resena->actualizar($id, $datos);

        Response::success(null, 'Reseña actualizada correctamente');
    }

    // DELETE /api/resenas/{id}
    public function destroy(int $id): void
    {
        if (!$this->resena->obtenerPorId($id)) {
            Response::error('Reseña no encontrada', 404);
        }

        $this->resena->eliminar($id);

        Response::success(null, 'Reseña eliminada correctamente');
    }

    private function validar(array $data): array
    {
        if (!Validator::texto($data['titulo'] ?? null, 120)) {
            return [null, 'El título es obligatorio (máximo 120 caracteres)'];
        }

        if (!Validator::texto($data['contenido'] ?? null, 500)) {
            return [null, 'El contenido es obligatorio (máximo 500 caracteres)'];
        }

        // La tabla exige una puntuación entre 0 y 10 (el 0 es válido)
        if (!isset($data['puntuacion']) || !Validator::numero($data['puntuacion'], 0, 10)) {
            return [null, 'La puntuación es obligatoria y debe estar entre 0 y 10'];
        }

        $estado = $data['estado'] ?? 'Publicada';

        if (!Validator::enLista($estado, self::ESTADOS)) {
            return [null, 'El estado debe ser: ' . implode(' o ', self::ESTADOS)];
        }

        $fecha = $data['fecha'] ?? null;

        if ($fecha === '') {
            $fecha = null;
        }

        if ($fecha !== null && !Validator::fecha($fecha)) {
            return [null, 'La fecha debe tener el formato AAAA-MM-DD'];
        }

        if (!Validator::entero($data['id_juego'] ?? null)) {
            return [null, 'El id_juego es obligatorio y debe ser un número entero'];
        }

        if (!Validator::entero($data['id_usuario'] ?? null)) {
            return [null, 'El id_usuario es obligatorio y debe ser un número entero'];
        }

        // Respetar las claves foráneas
        if (!$this->juego->obtenerPorId((int) $data['id_juego'])) {
            return [null, 'El juego indicado no existe'];
        }

        if (!$this->usuario->obtenerPorId((int) $data['id_usuario'])) {
            return [null, 'El usuario indicado no existe'];
        }

        return [[
            'titulo'     => trim($data['titulo']),
            'contenido'  => trim($data['contenido']),
            'puntuacion' => round((float) $data['puntuacion'], 1),
            'estado'     => $estado,
            'fecha'      => $fecha,
            'id_juego'   => (int) $data['id_juego'],
            'id_usuario' => (int) $data['id_usuario']
        ], null];
    }
}
