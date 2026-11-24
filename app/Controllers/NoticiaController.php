<?php

namespace App\Controllers;

use App\Models\Juego;
use App\Models\Noticia;
use App\Models\Usuario;
use Core\Request;
use Core\Response;
use Core\Validator;
use PDO;

class NoticiaController
{
    private const CATEGORIAS = ['Lanzamientos', 'Industria', 'Indie', 'Movil', 'Eventos'];

    private Noticia $noticia;
    private Juego $juego;
    private Usuario $usuario;

    public function __construct(PDO $db)
    {
        $this->noticia = new Noticia($db);
        $this->juego   = new Juego($db);
        $this->usuario = new Usuario($db);
    }

    // GET /api/noticias
    public function index(): void
    {
        Response::success(
            $this->noticia->obtenerTodos(),
            'Noticias obtenidas correctamente'
        );
    }

    // GET /api/noticias/{id}
    public function show(int $id): void
    {
        $noticia = $this->noticia->obtenerPorId($id);

        if (!$noticia) {
            Response::error('Noticia no encontrada', 404);
        }

        Response::success($noticia, 'Noticia obtenida correctamente');
    }

    // POST /api/noticias
    public function store(): void
    {
        [$datos, $error] = $this->validar(Request::body());

        if ($error) {
            Response::error($error, 400);
        }

        // Si no envía fecha, se usa la de hoy
        $datos['fecha'] = $datos['fecha'] ?? date('Y-m-d');

        $id = $this->noticia->crear($datos);

        Response::success(
            ['id_noticia' => $id],
            'Noticia registrada correctamente',
            201
        );
    }

    // PUT /api/noticias/{id}
    public function update(int $id): void
    {
        $existente = $this->noticia->obtenerPorId($id);

        if (!$existente) {
            Response::error('Noticia no encontrada', 404);
        }

        [$datos, $error] = $this->validar(Request::body());

        if ($error) {
            Response::error($error, 400);
        }

        // Si no envía fecha, se conserva la que ya tenía
        $datos['fecha'] = $datos['fecha'] ?? $existente['fecha'];

        $this->noticia->actualizar($id, $datos);

        Response::success(null, 'Noticia actualizada correctamente');
    }

    // DELETE /api/noticias/{id}
    public function destroy(int $id): void
    {
        if (!$this->noticia->obtenerPorId($id)) {
            Response::error('Noticia no encontrada', 404);
        }

        $this->noticia->eliminar($id);

        Response::success(null, 'Noticia eliminada correctamente');
    }

    private function validar(array $data): array
    {
        if (!Validator::texto($data['titulo'] ?? null, 150)) {
            return [null, 'El título es obligatorio (máximo 150 caracteres)'];
        }

        if (!Validator::texto($data['resumen'] ?? null, 300)) {
            return [null, 'El resumen es obligatorio (máximo 300 caracteres)'];
        }

        if (!Validator::enLista($data['categoria'] ?? null, self::CATEGORIAS)) {
            return [null, 'La categoría debe ser: ' . implode(', ', self::CATEGORIAS)];
        }

        $fecha = $data['fecha'] ?? null;

        if ($fecha === '') {
            $fecha = null;
        }

        if ($fecha !== null && !Validator::fecha($fecha)) {
            return [null, 'La fecha debe tener el formato AAAA-MM-DD'];
        }

        $publicada = $data['publicada'] ?? 1;

        if (!Validator::booleano($publicada)) {
            return [null, 'El campo publicada debe ser 0 o 1'];
        }

        if (!Validator::entero($data['id_usuario'] ?? null)) {
            return [null, 'El id_usuario es obligatorio y debe ser un número entero'];
        }

        if (!$this->usuario->obtenerPorId((int) $data['id_usuario'])) {
            return [null, 'El usuario indicado no existe'];
        }

        // El juego es opcional (hay noticias generales), pero si viene debe existir
        $idJuego = $data['id_juego'] ?? null;

        if ($idJuego === '') {
            $idJuego = null;
        }

        if ($idJuego !== null) {

            if (!Validator::entero($idJuego)) {
                return [null, 'El id_juego debe ser un número entero'];
            }

            if (!$this->juego->obtenerPorId((int) $idJuego)) {
                return [null, 'El juego indicado no existe'];
            }

            $idJuego = (int) $idJuego;
        }

        return [[
            'titulo'     => trim($data['titulo']),
            'resumen'    => trim($data['resumen']),
            'categoria'  => $data['categoria'],
            'fecha'      => $fecha,
            'publicada'  => (int) $publicada,
            'id_usuario' => (int) $data['id_usuario'],
            'id_juego'   => $idJuego
        ], null];
    }
}
