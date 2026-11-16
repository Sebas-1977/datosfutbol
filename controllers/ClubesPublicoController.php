<?php

namespace Controllers;

use Model\ClubPublico;
use Model\Partido;
use MVC\Router;

class ClubesPublicoController
{
    public static function index(Router $router): void
    {
        $torneos  = Partido::torneosConPartidos();
        $idsValid = array_map('intval', array_column($torneos, 'id'));

        $idTorneo = (int) ($_GET['id_torneo'] ?? 0);
        if (!in_array($idTorneo, $idsValid, true)) {
            $idTorneo = $idsValid[0] ?? 0;
        }

        $router->render('publico/clubes', [
            'titulo'      => 'Clubes',
            'descripcion' => 'Clubes que participan en DatosFutbol.',
            'idTorneo'    => $idTorneo,
            'clubes'      => $idTorneo ? ClubPublico::listado($idTorneo) : [],
        ], 'layout-publico');
    }
}