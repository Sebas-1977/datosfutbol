<?php

declare(strict_types=1);

namespace Controllers;

use Model\Partido;
use MVC\Router;

class ResumenController
{
    public static function index(Router $router): void
    {
        $id = (int) ($_GET['id_partido'] ?? 0);
        $partido = $id > 0 ? Partido::find($id) : null;

        if (!$partido) {
            http_response_code(404);
        }

        $router->render('resumen/index', [
            'titulo'  => 'Resumen del partido',
            'partido' => $partido,
        ]);
    }
}