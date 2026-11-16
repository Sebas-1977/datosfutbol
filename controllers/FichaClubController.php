<?php

namespace Controllers;

use Model\ClubPublico;
use Model\Partido;
use Model\PlantelPublico;
use MVC\Router;

class FichaClubController
{
    public static function index(Router $router): void
    {
        $torneos = [];
        foreach (Partido::torneosConPartidos() as $t) {
            $torneos[(int) $t['id']] = $t;
        }

        $idTorneo = (int) ($_GET['id_torneo'] ?? 0);
        if (!isset($torneos[$idTorneo])) {
            $idTorneo = (int) (array_key_first($torneos) ?? 0);
        }

        $idEquipo = (int) ($_GET['id_equipo'] ?? 0);
        $club = $idEquipo ? ClubPublico::equipo($idEquipo) : null;

        if (!$club) {
            http_response_code(404);
            $router->render('publico/club', [
                'titulo' => 'Club no encontrado',
                'club'   => null,
            ], 'layout-publico');
            return;
        }

        $router->render('publico/club', [
            'titulo'      => $club['nombre'],
            'descripcion' => "Plantel, partidos y estadísticas de {$club['nombre']} en DatosFutbol.",
            'club'        => $club,
            'torneo'      => $torneos[$idTorneo] ?? null,
            'partidos'    => $idTorneo ? ClubPublico::partidos($idTorneo, $idEquipo) : [],
            'stats'       => $idTorneo ? ClubPublico::estadisticas($idTorneo, $idEquipo) : [],
            'tablas'      => $idTorneo ? ClubPublico::clasificacion($idTorneo, $idEquipo) : [],
            'tarjetas'    => $idTorneo ? ClubPublico::tarjetas($idTorneo, $idEquipo) : [],
            'plantel'     => $idTorneo ? (PlantelPublico::jugadoresPorClub($idTorneo, $idEquipo)[$idEquipo] ?? []) : [],
        ], 'layout-publico');
    }
}