<?php

declare(strict_types=1);

namespace Controllers;

use Model\ActiveRecord;
use Model\Fase;

class FaseApiController extends ApiController
{
    protected static string $modelo = Fase::class;

    // Solo estos campos se editan; el resto sale de la configuración del torneo
    protected static array $camposPermitidos = ['nombre', 'ida_vuelta'];
    protected static array $filtros = ['id_torneo'];
    protected static string $orden = 'orden ASC, id ASC';

    // POST: solo edita fases existentes
    public static function guardar(): void
    {
        static::soloPost();

        $input = static::leerInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            static::json([
                'ok' => false,
                'message' => 'Las fases se generan solas al crear el torneo. Cambiá su configuración para regenerarlas.',
            ], 409);
        }

        // sincronizar() ignora el nombre vacío al editar, así que se controla acá
        if (array_key_exists('nombre', $input) && trim((string) $input['nombre']) === '') {
            static::json([
                'ok' => false,
                'alertas' => ['error' => ['El nombre de la fase es obligatorio.']],
            ], 422);
        }

        parent::guardar();
    }

    // POST: las fases solo se quitan regenerando o borrando el torneo
    public static function eliminar(): void
    {
        static::soloPost();

        static::json([
            'ok' => false,
            'message' => 'Las fases no se eliminan a mano: se regeneran al cambiar la configuración del torneo.',
        ], 409);
    }

    protected static function bloqueoEliminar(ActiveRecord $registro): ?string
    {
        return 'Las fases no se eliminan a mano.';
    }
}