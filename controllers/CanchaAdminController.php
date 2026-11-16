<?php

declare(strict_types=1);

namespace Controllers;

use MVC\Router;

class CanchaAdminController
{
    public static function index(Router $router): void
    {
        $router->render('admin/canchas', ['titulo' => 'Canchas | Administración']);
    }
}