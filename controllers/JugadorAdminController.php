<?php

declare(strict_types=1);

namespace Controllers;

use MVC\Router;

class JugadorAdminController
{
    public static function index(Router $router): void
    {
        $router->render('admin/jugadores', ['titulo' => 'Jugadores | Administración']);
    }
}