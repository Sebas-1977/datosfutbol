<?php

declare(strict_types=1);

namespace Controllers;

use MVC\Router;

class ArbitroAdminController
{
    public static function index(Router $router): void
    {
        $router->render('admin/arbitros', ['titulo' => 'Árbitros | Administración']);
    }
}