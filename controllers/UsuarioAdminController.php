<?php

declare(strict_types=1);

namespace Controllers;

use Classes\Auth;
use MVC\Router;

class UsuarioAdminController
{
    public static function index(Router $router): void
    {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $router->render('admin/sin-permiso', ['titulo' => 'Sin permiso | Administración']);
            return;
        }

        $router->render('admin/usuarios', ['titulo' => 'Usuarios | Administración']);
    }
}