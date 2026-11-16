<?php

namespace Controllers;

use Model\Partido;
use Model\PlantelPublico;
use MVC\Router;

class JugadoresPublicoController
{
    public static function index(Router $router): void
    {
        $torneos  = Partido::torneosConPartidos();
        $idsValid = array_map('intval', array_column($torneos, 'id'));

        $idTorneo = (int) ($_GET['id_torneo'] ?? 0);
        if (!in_array($idTorneo, $idsValid, true)) {
            $idTorneo = $idsValid[0] ?? 0;
        }

        $clubes   = $idTorneo ? PlantelPublico::clubesDelTorneo($idTorneo) : [];
        $idsClub  = array_map('intval', array_column($clubes, 'id'));

        $idEquipo = (int) ($_GET['id_equipo'] ?? 0);
        if (!in_array($idEquipo, $idsClub, true)) {
            $idEquipo = 0; // 0 = todos los clubes
        }

        $router->render('publico/jugadores', [
            'titulo'      => 'Jugadores',
            'descripcion' => 'Plantel de cada club de DatosFutbol, por posición.',
            'torneos'     => $torneos,
            'idTorneo'    => $idTorneo,
            'clubes'      => $idEquipo ? array_values(array_filter($clubes, fn ($c) => (int) $c['id'] === $idEquipo)) : $clubes,
            'todosClubes' => $clubes,
            'idEquipo'    => $idEquipo,
            'plantel'     => $idTorneo ? PlantelPublico::jugadoresPorClub($idTorneo, $idEquipo ?: null) : [],
        ], 'layout-publico');
    }
}