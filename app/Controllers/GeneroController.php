<?php

namespace App\Controllers;

use App\Models\Genero;
use Core\Request;
use Core\Response;
use Core\Validator;
use PDO;

class GeneroController
{
    private Genero $genero;

    public function __construct(PDO $db)
    {
        $this->genero = new Genero($db);
    }

    // GET /api/generos
    public function index(): void
    {
        Response::success(
            $this->genero->obtenerTodos(),
            'Géneros obtenidos correctamente'
        );
    }

    // GET /api/generos/{id}
    public function show(int $id): void
    {
        $genero = $this->genero->obtenerPorId($id);

        if (!$genero) {
            Response::error('Género no encontrado', 404);
        }

        Response::success($genero, 'Género obtenido correctamente');
    }

    // POST /api/generos
    public function store(): void
    {
        [$datos, $error] = $this->validar(Request::body());

        if ($error) {
            Response::error($error, 400);
        }

        if ($this->genero->obtenerPorNombre($datos['nombre'])) {
            Response::error('Ya existe un género con ese nombre', 409);
        }

        $id = $this->genero->crear($datos);

        Response::success(
            ['id_genero' => $id],
            'Género registrado correctamente',
            201
        );
    }

    // PUT /api/generos/{id}
    public function update(int $id): void
    {
        if (!$this->genero->obtenerPorId($id)) {
            Response::error('Género no encontrado', 404);
        }

        [$datos, $error] = $this->validar(Request::body());

        if ($error) {
            Response::error($error, 400);
        }

        if ($this->genero->obtenerPorNombre($datos['nombre'], $id)) {
            Response::error('Ya existe otro género con ese nombre', 409);
        }

        $this->genero->actualizar($id, $datos);

        Response::success(null, 'Género actualizado correctamente');
    }

    // DELETE /api/generos/{id}
    public function destroy(int $id): void
    {
        if (!$this->genero->obtenerPorId($id)) {
            Response::error('Género no encontrado', 404);
        }

        $total = $this->genero->contarJuegos($id);

        if ($total > 0) {
            Response::error(
                "No se puede eliminar: hay {$total} juego(s) registrados con este género",
                409
            );
        }

        $this->genero->eliminar($id);

        Response::success(null, 'Género eliminado correctamente');
    }

    /**
     * Valida los datos recibidos.
     * Devuelve [datosLimpios, null] si todo está bien, o [null, mensajeDeError].
     */
    private function validar(array $data): array
    {
        if (!Validator::texto($data['nombre'] ?? null, 50)) {
            return [null, 'El nombre es obligatorio (máximo 50 caracteres)'];
        }

        if (!Validator::textoOpcional($data['descripcion'] ?? null, 200)) {
            return [null, 'La descripción no puede superar los 200 caracteres'];
        }

        $descripcion = trim((string) ($data['descripcion'] ?? ''));

        return [[
            'nombre'      => trim($data['nombre']),
            'descripcion' => $descripcion !== '' ? $descripcion : null
        ], null];
    }
}
