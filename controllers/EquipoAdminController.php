<?php

declare(strict_types=1);

namespace Controllers;

use MVC\Router;

class EquipoAdminController
{
    public static function index(Router $router): void
    {
        $router->render('admin/equipos', ['titulo' => 'Equipos | Administración']);
    }
}