<?php

declare(strict_types=1);

namespace Controllers;

use MVC\Router;

class PartidoController
{
    public static function index(Router $router): void
    {
        $router->render('partidos/index', ['titulo' => 'Partidos']);
    }
}