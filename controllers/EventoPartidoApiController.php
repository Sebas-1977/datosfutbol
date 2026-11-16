<?php

declare(strict_types=1);

namespace Controllers;

use Model\EventoPartido;

class EventoPartidoApiController extends ApiController
{
    protected static string $modelo = EventoPartido::class;
    protected static array $camposPermitidos = [
        'id_partido', 'id_equipo', 'id_jugador', 'id_jugador_entra',
        'id_asistente', 'tipo', 'minuto', 'minuto_extra', 'detalle',
    ];
    protected static array $filtros = ['id_partido', 'id_equipo'];
    protected static string $orden = 'minuto ASC, minuto_extra ASC, id ASC';

    private static function esGol(?string $tipo): bool
    {
        return in_array($tipo, EventoPartido::GOLES, true);
    }

    // POST: crea o actualiza, y recalcula el marcador si corresponde
    public static function guardar(): void
    {
        static::soloPost();

        $input = static::leerInput();
        $datos = array_intersect_key($input, array_flip(static::$camposPermitidos));
        $id = (int) ($input['id'] ?? 0);
        $tipoAnterior = null;

        if ($id > 0) {
            $registro = EventoPartido::find($id);
            if (!$registro) {
                static::json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
            }
            $tipoAnterior = $registro->tipo;
        } else {
            $registro = new EventoPartido();
        }

        $registro->sincronizar($datos);
        $alertas = $registro->validar();

        if (!empty($alertas)) {
            static::json(['ok' => false, 'alertas' => $alertas], 422);
        }

        try {
            $resultado = $registro->guardar();
        } catch (\PDOException $e) {
            static::json(['ok' => false, 'alertas' => ['error' => ['No se pudo guardar el evento.']]], 422);
        }

        if (is_array($resultado)) {
            $registro->id = (int) $resultado['id'];
        }

        EventoPartido::recalcularMarcador(
            (int) $registro->id_partido,
            static::esGol($tipoAnterior) || static::esGol($registro->tipo)
        );

        static::json(['ok' => true, 'data' => $registro], $id > 0 ? 200 : 201);
    }

    // POST: elimina, con protección de dependencias en el servidor
    public static function eliminar(): void
    {
        static::soloPost();

        $input = static::leerInput();
        $registro = EventoPartido::find((int) ($input['id'] ?? 0));

        if (!$registro) {
            static::json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
        }

        if ($motivo = $registro->motivoNoBorrar()) {
            static::json(['ok' => false, 'message' => $motivo], 409);
        }

        $idPartido = (int) $registro->id_partido;
        $eraGol = static::esGol($registro->tipo);

        try {
            $registro->eliminar();
        } catch (\PDOException $e) {
            static::json(['ok' => false, 'message' => 'No se puede eliminar: tiene registros relacionados.'], 409);
        }

        EventoPartido::recalcularMarcador($idPartido, $eraGol);

        static::json(['ok' => true]);
    }
}