<?php

declare(strict_types=1);

namespace Controllers;

use Model\Destacado;

class DestacadoApiController extends ApiController
{
    protected static string $modelo = Destacado::class;
    protected static array $camposPermitidos = [
        'titulo', 'bajada', 'cuerpo', 'tipo', 'id_referencia', 'id_torneo', 'orden', 'activo',
    ];
    protected static array $camposImagen = ['imagen'];
    protected static array $filtros = ['activo', 'id_torneo'];
    protected static string $orden = 'orden ASC, id DESC';

    /** Sin sesión solo se ven los activos (los borradores no se filtran por la API). */
    public static function listar(): void
    {
        if ((int) ($_GET['id_torneo'] ?? 0) < 1) {
            static::json(['ok' => false, 'message' => 'Seleccioná un torneo para ver sus destacados.'], 422);
        }

        if (!static::haySesion()) {
            $_GET['activo'] = '1';
        }
        parent::listar();
    }
}
