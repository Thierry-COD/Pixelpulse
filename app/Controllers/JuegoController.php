<?php

namespace App\Controllers;

use App\Models\Genero;
use App\Models\Juego;
use App\Models\Plataforma;
use Core\Request;
use Core\Response;
use Core\Validator;
use PDO;

class JuegoController
{
    private Juego $juego;
    private Genero $genero;
    private Plataforma $plataforma;

    public function __construct(PDO $db)
    {
        $this->juego      = new Juego($db);
        $this->genero     = new Genero($db);
        $this->plataforma = new Plataforma($db);
    }

    // GET /api/juegos
    public function index(): void
    {
        Response::success(
            $this->juego->obtenerTodos(),
            'Juegos obtenidos correctamente'
        );
    }

    // GET /api/juegos/{id}
    public function show(int $id): void
    {
        $juego = $this->juego->obtenerPorId($id);

        if (!$juego) {
            Response::error('Juego no encontrado', 404);
        }

        Response::success($juego, 'Juego obtenido correctamente');
    }

    // POST /api/juegos
    public function store(): void
    {
        [$datos, $error] = $this->validar(Request::body());

        if ($error) {
            Response::error($error, 400);
        }

        $id = $this->juego->crear($datos);

        Response::success(
            ['id_juego' => $id],
            'Juego registrado correctamente',
            201
        );
    }

    // PUT /api/juegos/{id}
    public function update(int $id): void
    {
        if (!$this->juego->obtenerPorId($id)) {
            Response::error('Juego no encontrado', 404);
        }

        [$datos, $error] = $this->validar(Request::body());

        if ($error) {
            Response::error($error, 400);
        }

        $this->juego->actualizar($id, $datos);

        Response::success(null, 'Juego actualizado correctamente');
    }

    // DELETE /api/juegos/{id}  (eliminación lógica: ESTADO = 0)
    public function destroy(int $id): void
    {
        if (!$this->juego->obtenerPorId($id)) {
            Response::error('Juego no encontrado', 404);
        }

        $this->juego->eliminar($id);

        Response::success(null, 'Juego desactivado correctamente');
    }

    private function validar(array $data): array
    {
        if (!Validator::texto($data['titulo'] ?? null, 120)) {
            return [null, 'El título es obligatorio (máximo 120 caracteres)'];
        }

        if (!Validator::textoOpcional($data['desarrollador'] ?? null, 100)) {
            return [null, 'El desarrollador no puede superar los 100 caracteres'];
        }

        // Fecha opcional, formato AAAA-MM-DD
        $fecha = $data['fecha_lanzamiento'] ?? null;

        if ($fecha === '') {
            $fecha = null;
        }

        if ($fecha !== null && !Validator::fecha($fecha)) {
            return [null, 'La fecha de lanzamiento debe tener el formato AAAA-MM-DD'];
        }

        // OJO: el precio 0 es válido (juegos gratuitos), por eso NO se usa empty()
        if (!isset($data['precio']) || !Validator::numero($data['precio'], 0, 999999.99)) {
            return [null, 'El precio es obligatorio y debe ser un número mayor o igual a 0'];
        }

        if (!Validator::entero($data['id_genero'] ?? null)) {
            return [null, 'El id_genero es obligatorio y debe ser un número entero'];
        }

        if (!Validator::entero($data['id_plataforma'] ?? null)) {
            return [null, 'El id_plataforma es obligatorio y debe ser un número entero'];
        }

        // Respetar las claves foráneas: el género y la plataforma deben existir
        if (!$this->genero->obtenerPorId((int) $data['id_genero'])) {
            return [null, 'El género indicado no existe'];
        }

        if (!$this->plataforma->obtenerPorId((int) $data['id_plataforma'])) {
            return [null, 'La plataforma indicada no existe'];
        }

        $estado = $data['estado'] ?? 1;

        if (!Validator::booleano($estado)) {
            return [null, 'El estado debe ser 0 o 1'];
        }

        $desarrollador = trim((string) ($data['desarrollador'] ?? ''));

        return [[
            'titulo'            => trim($data['titulo']),
            'desarrollador'     => $desarrollador !== '' ? $desarrollador : null,
            'fecha_lanzamiento' => $fecha,
            'precio'            => round((float) $data['precio'], 2),
            'estado'            => (int) $estado,
            'id_genero'         => (int) $data['id_genero'],
            'id_plataforma'     => (int) $data['id_plataforma']
        ], null];
    }
}
