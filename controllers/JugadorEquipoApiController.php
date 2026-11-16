<?php

declare(strict_types=1);

namespace Controllers;

use Model\JugadorEquipo;

class JugadorEquipoApiController extends ApiController
{
    protected static string $modelo = JugadorEquipo::class;
    protected static array $camposPermitidos = [
        'id_torneo', 'id_equipo', 'id_jugador', 'numero_camiseta', 'posicion',
    ];
    protected static array $filtros = ['id_torneo', 'id_equipo', 'id_jugador'];
    protected static string $orden = 'numero_camiseta ASC, id ASC';
}