<?php

declare(strict_types=1);

namespace Controllers;

use Model\Grupo;

class GrupoApiController extends ApiController
{
    protected static string $modelo = Grupo::class;
    protected static array $camposPermitidos = ['id_fase', 'nombre'];
    protected static array $filtros = ['id_fase'];
    protected static string $orden = 'nombre ASC';
}