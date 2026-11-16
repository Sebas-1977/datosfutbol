<?php

declare(strict_types=1);

namespace Controllers;

use Model\Destacado;
use MVC\Router;

class DestacadoPublicoController
{
    public static function index(Router $router): void
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $d  = $id ? Destacado::activo((int) $id) : null;

        if (!$d) {
            http_response_code(404);
        }

        $router->render('publico/destacado', [
            'titulo'      => $d['titulo'] ?? 'Destacado no encontrado',
            'descripcion' => $d['bajada'] ?? 'DatosFutbol',
            'd'           => $d,
        ], 'layout-publico');
    }
}