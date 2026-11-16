<?php

declare(strict_types=1);

namespace Controllers;

use Model\PartidoJugador;

class PartidoJugadorApiController extends ApiController
{
    protected static string $modelo = PartidoJugador::class;
    protected static array $camposPermitidos = ['id_partido', 'id_equipo', 'id_jugador', 'condicion', 'posicion_campo'];
    protected static array $filtros = ['id_partido', 'id_equipo'];
    protected static string $orden = 'id ASC';
}