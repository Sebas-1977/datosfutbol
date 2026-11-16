<?php

namespace Controllers;

use Model\EstadisticaPublica;
use Model\Partido;
use MVC\Router;

class EstadisticasPublicoController
{
    private const VISTAS = ['general', 'jugadores', 'clubes'];

    public static function index(Router $router): void
    {
        $torneos  = Partido::torneosConPartidos();
        $idsValid = array_map('intval', array_column($torneos, 'id'));

        $idTorneo = (int) ($_GET['id_torneo'] ?? 0);
        if (!in_array($idTorneo, $idsValid, true)) {
            $idTorneo = $idsValid[0] ?? 0;
        }

        $vista = (string) ($_GET['vista'] ?? 'general');
        if (!in_array($vista, self::VISTAS, true)) {
            $vista = 'general';
        }

        $jugadores = [];
        $clubes    = [];

        if ($idTorneo) {
            // En la vista general solo el líder (los empates del primer puesto se muestran todos).
            $limite = $vista === 'general' ? 3 : 0;

            if ($vista !== 'clubes') {
                foreach (array_keys(EstadisticaPublica::JUGADORES) as $clave) {
                    $jugadores[$clave] = EstadisticaPublica::jugadores($idTorneo, $clave, $limite);
                }
            }
            if ($vista !== 'jugadores') {
                foreach (array_keys(EstadisticaPublica::CLUBES) as $clave) {
                    $clubes[$clave] = EstadisticaPublica::clubes($idTorneo, $clave, $limite);
                }
            }
        }

        $router->render('publico/estadisticas', [
            'titulo'      => 'Estadísticas',
            'descripcion' => 'Goleadores, asistencias, tarjetas y rankings de clubes de DatosFutbol.',
            'torneos'     => $torneos,
            'idTorneo'    => $idTorneo,
            'vista'       => $vista,
            'jugadores'   => $jugadores,
            'clubes'      => $clubes,
        ], 'layout-publico');
    }
}