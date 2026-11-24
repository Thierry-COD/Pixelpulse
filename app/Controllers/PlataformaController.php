<?php

namespace App\Controllers;

use App\Models\Plataforma;
use Core\Request;
use Core\Response;
use Core\Validator;
use PDO;

class PlataformaController
{
    private const TIPOS = ['PC', 'Consola', 'Portatil', 'Movil'];

    private Plataforma $plataforma;

    public function __construct(PDO $db)
    {
        $this->plataforma = new Plataforma($db);
    }

    // GET /api/plataformas
    public function index(): void
    {
        Response::success(
            $this->plataforma->obtenerTodos(),
            'Plataformas obtenidas correctamente'
        );
    }

    // GET /api/plataformas/{id}
    public function show(int $id): void
    {
        $plataforma = $this->plataforma->obtenerPorId($id);

        if (!$plataforma) {
            Response::error('Plataforma no encontrada', 404);
        }

        Response::success($plataforma, 'Plataforma obtenida correctamente');
    }

    // POST /api/plataformas
    public function store(): void
    {
        [$datos, $error] = $this->validar(Request::body());

        if ($error) {
            Response::error($error, 400);
        }

        if ($this->plataforma->obtenerPorNombre($datos['nombre'])) {
            Response::error('Ya existe una plataforma con ese nombre', 409);
        }

        $id = $this->plataforma->crear($datos);

        Response::success(
            ['id_plataforma' => $id],
            'Plataforma registrada correctamente',
            201
        );
    }

    // PUT /api/plataformas/{id}
    public function update(int $id): void
    {
        if (!$this->plataforma->obtenerPorId($id)) {
            Response::error('Plataforma no encontrada', 404);
        }

        [$datos, $error] = $this->validar(Request::body());

        if ($error) {
            Response::error($error, 400);
        }

        if ($this->plataforma->obtenerPorNombre($datos['nombre'], $id)) {
            Response::error('Ya existe otra plataforma con ese nombre', 409);
        }

        $this->plataforma->actualizar($id, $datos);

        Response::success(null, 'Plataforma actualizada correctamente');
    }

    // DELETE /api/plataformas/{id}
    public function destroy(int $id): void
    {
        if (!$this->plataforma->obtenerPorId($id)) {
            Response::error('Plataforma no encontrada', 404);
        }

        $total = $this->plataforma->contarJuegos($id);

        if ($total > 0) {
            Response::error(
                "No se puede eliminar: hay {$total} juego(s) registrados en esta plataforma",
                409
            );
        }

        $this->plataforma->eliminar($id);

        Response::success(null, 'Plataforma eliminada correctamente');
    }

    private function validar(array $data): array
    {
        if (!Validator::texto($data['nombre'] ?? null, 50)) {
            return [null, 'El nombre es obligatorio (máximo 50 caracteres)'];
        }

        if (!Validator::enLista($data['tipo'] ?? null, self::TIPOS)) {
            return [null, 'El tipo debe ser: ' . implode(', ', self::TIPOS)];
        }

        if (!Validator::textoOpcional($data['fabricante'] ?? null, 50)) {
            return [null, 'El fabricante no puede superar los 50 caracteres'];
        }

        $fabricante = trim((string) ($data['fabricante'] ?? ''));

        return [[
            'nombre'     => trim($data['nombre']),
            'tipo'       => $data['tipo'],
            'fabricante' => $fabricante !== '' ? $fabricante : null
        ], null];
    }
}
