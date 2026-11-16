<?php

namespace Controllers;

use Model\Clasificacion;
use Model\Partido;
use Model\Torneo;
use MVC\Router;
use Model\Destacado;

class PortadaController
{
    public static function index(Router $router): void
    {
        $torneos = Partido::torneosConPartidos();

        // Si llega ?torneo=ID en la URL, pre-seleccionamos ese torneo
        $idsValidos = array_map('intval', array_column($torneos, 'id'));
        $idTorneo   = (int) ($_GET['torneo'] ?? 0);
        if (!in_array($idTorneo, $idsValidos, true)) {
            $idTorneo = $idsValidos[0] ?? 0;
        }

        $mini = $idTorneo ? Clasificacion::principal($idTorneo) : null;

        // Torneos marcados como públicos
        $publicos    = Torneo::publicos();
        $idsPublicos = array_map('intval', array_column($publicos, 'id'));

        $destacados = Destacado::portada(9, $idsPublicos);

        $router->render('publico/portada', [
            'titulo'          => 'Portada',
            'descripcion'     => 'Resultados y próximos partidos de DatosFutbol.',
            'resultados'      => Partido::resultadosRecientes(10),
            'proximos'        => Partido::proximosPartidos(6),
            'mini'            => $mini,
            'destacados'      => $destacados,
            'torneosPublicos' => $publicos,
            'torneos'         => $torneos,
            'idTorneo'        => $idTorneo,
        ], 'layout-publico');
    }
}
