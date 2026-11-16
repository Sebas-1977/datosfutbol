<?php

namespace Controllers;

use Model\Clasificacion;
use Model\Partido;
use MVC\Router;

class ClasificacionController
{
    public static function index(Router $router): void
    {
        $torneos  = Partido::torneosConPartidos();
        $idsValid = array_map('intval', array_column($torneos, 'id'));

        $idTorneo = (int) ($_GET['torneo'] ?? 0);
        if (!in_array($idTorneo, $idsValid, true)) {
            $idTorneo = $idsValid[0] ?? 0;
        }

        $router->render('publico/clasificacion', [
            'titulo'      => 'Clasificación',
            'descripcion' => 'Tabla de posiciones de DatosFutbol.',
            'torneos'     => $torneos,
            'idTorneo'    => $idTorneo,
            'fases'       => $idTorneo ? Clasificacion::deTorneo($idTorneo) : [],
        ], 'layout-publico');
    }
}