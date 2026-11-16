<?php

declare(strict_types=1);

namespace Controllers;

use Model\Torneo;
use MVC\Router;

class DestacadoAdminController
{
    public static function index(Router $router): void
    {
        $idTorneo = isset($_GET['torneo']) ? (int) $_GET['torneo'] : 0;
        $torneo = $idTorneo > 0 ? Torneo::find($idTorneo) : null;
        if (!$torneo) {
            header('Location: /admin/torneos');
            exit;
        }

        $router->render('admin/destacados', [
            'titulo'       => 'Destacados | Administración',
            'idTorneo'     => $idTorneo,
            'nombreTorneo' => $torneo->nombre,
        ]);
    }
}