<?php

declare(strict_types=1);

namespace Controllers;

use MVC\Router;

class TorneoAdminController
{
    public static function index(Router $router): void
    {
        $router->render('admin/torneos', [
            'titulo'   => 'Torneos | Administración',
            'idTorneo' => (int) ($_GET['torneo'] ?? 0),
        ]);
    }
}