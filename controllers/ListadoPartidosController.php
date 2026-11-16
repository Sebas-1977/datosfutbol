<?php

namespace Controllers;

use Model\Partido;
use Model\Torneo;
use Model\Destacado;
use MVC\Router;

class ListadoPartidosController
{
    public static function index(Router $router): void
    {
        $torneos  = Partido::torneosConPartidos();
        $idsValid = array_map('intval', array_column($torneos, 'id'));

        $idTorneo = (int) ($_GET['torneo'] ?? 0);
        if (!in_array($idTorneo, $idsValid, true)) {
            $idTorneo = $idsValid[0] ?? 0;
        }

        $jornadas = $idTorneo ? Partido::jornadasDeTorneo($idTorneo) : [];

        $jornada = (int) ($_GET['jornada'] ?? 0);
        if (!in_array($jornada, $jornadas, true)) {
            $jornada = Partido::jornadaActual($idTorneo) ?? ($jornadas[0] ?? 0);
        }

        $pos = array_search($jornada, $jornadas, true);

        // Destacados filtrados al torneo activo (+ generales); excluye los de otros torneos
        $publicos    = Torneo::publicos();
        $idsPublicos = array_map('intval', array_column($publicos, 'id'));
        $destacados  = $idTorneo && in_array($idTorneo, $idsPublicos, true)
            ? Destacado::portadaDeTorneo($idTorneo, 6)
            : Destacado::portada(6, $idsPublicos);

        $router->render('publico/partidos', [
            'titulo'      => 'Partidos',
            'descripcion' => 'Calendario y resultados por jornada de DatosFutbol.',
            'torneos'     => $torneos,
            'idTorneo'    => $idTorneo,
            'jornadas'    => $jornadas,
            'jornada'     => $jornada,
            'anterior'    => ($pos !== false && $pos > 0) ? $jornadas[$pos - 1] : null,
            'siguiente'   => ($pos !== false && isset($jornadas[$pos + 1])) ? $jornadas[$pos + 1] : null,
            'partidos'    => $idTorneo && $jornada ? Partido::partidosDeJornada($idTorneo, $jornada) : [],
            'destacados'  => $destacados,
        ], 'layout-publico');
    }
}
