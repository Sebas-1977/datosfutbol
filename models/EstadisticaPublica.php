<?php

declare(strict_types=1);

namespace Model;

/** Rankings de solo lectura para la sección Estadísticas del sitio público. */
class EstadisticaPublica
{
    /** clave => [título, tipos de evento]. Valores fijos, no vienen del usuario. */
    public const JUGADORES = [
        'goles'        => ['Goles',             ['gol', 'gol_penal']],
        'asistencias'  => ['Asistencias',       ['gol', 'gol_penal']],
        'amarillas'    => ['Tarjetas amarillas', ['tarjeta_amarilla']],
        'rojas'        => ['Tarjetas rojas',    ['tarjeta_roja', 'doble_amarilla']],
        'autogoles'    => ['Autogoles',         ['autogol']],
        'penales'      => ['Penales fallados',  ['penal_fallado']],
    ];

    public const CLUBES = [
        'victorias' => 'Victorias',
        'gf'        => 'Goles a favor',
        'gc'        => 'Goles en contra',
        'tarjetas'  => 'Tarjetas',
    ];

    private static function filas(string $sql, array $params = []): array
    {
        $st = Partido::conexion()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Ranking de jugadores:
     * [['posicion', 'id_jugador', 'nombre', 'apellido', 'seudonimo', 'foto', 'id_equipo', 'club', 'escudo', 'valor']].
     * $limite = 0 devuelve la lista completa.
     */
    public static function jugadores(int $idTorneo, string $clave, int $limite = 0): array
    {
        if (!isset(self::JUGADORES[$clave])) {
            return [];
        }

        $tipos = self::JUGADORES[$clave][1];
        $marcas = implode(',', array_fill(0, count($tipos), '?'));

        // Las asistencias se cuentan por id_asistente; el resto por id_jugador.
        $columna = $clave === 'asistencias' ? 'ev.id_asistente' : 'ev.id_jugador';

        $filas = static::filas(
            "SELECT j.id AS id_jugador, j.nombre, j.apellido, j.seudonimo, j.foto, e.id AS id_equipo, e.nombre AS club, e.escudo,"
            . ' COUNT(*) AS valor'
            . ' FROM eventos_partido ev'
            . ' INNER JOIN partidos p ON p.id = ev.id_partido'
            . " INNER JOIN jugadores j ON j.id = {$columna}"
            . ' INNER JOIN equipos e ON e.id = ev.id_equipo'
            . " WHERE p.id_torneo = ? AND ev.tipo IN ({$marcas})"
            . ($clave === 'asistencias' ? ' AND ev.id_asistente IS NOT NULL' : '')
            . ' GROUP BY j.id, j.nombre, j.apellido, j.seudonimo, j.foto, e.id, e.nombre, e.escudo'
            . ' ORDER BY valor DESC, j.apellido, j.nombre',
            array_merge([$idTorneo], $tipos)
        );

        return static::posicionar($filas, $limite);
    }

    /** Ranking de clubes: [['posicion', 'id_equipo', 'club', 'escudo', 'valor']]. */
    public static function clubes(int $idTorneo, string $clave, int $limite = 0): array
    {
        if (!isset(self::CLUBES[$clave])) {
            return [];
        }

        if ($clave === 'tarjetas') {
            $filas = static::filas(
                'SELECT e.id AS id_equipo, e.nombre AS club, e.escudo, COUNT(*) AS valor'
                . ' FROM eventos_partido ev'
                . ' INNER JOIN partidos p ON p.id = ev.id_partido'
                . ' INNER JOIN equipos e ON e.id = ev.id_equipo'
                . " WHERE p.id_torneo = ? AND ev.tipo IN ('tarjeta_amarilla','tarjeta_roja','doble_amarilla')"
                . ' GROUP BY e.id, e.nombre, e.escudo ORDER BY valor DESC, e.nombre',
                [$idTorneo]
            );

            return static::posicionar($filas, $limite);
        }

        // Una fila por equipo y partido finalizado: (gf, gc).
        $base = 'SELECT e.id AS id_equipo, e.nombre AS club, e.escudo, t.gf, t.gc FROM equipos e INNER JOIN ('
            . ' SELECT id_equipo_local AS eq, goles_local AS gf, goles_visitante AS gc FROM partidos'
            . "  WHERE id_torneo = ? AND estado = 'finalizado'"
            . ' UNION ALL'
            . ' SELECT id_equipo_visitante, goles_visitante, goles_local FROM partidos'
            . "  WHERE id_torneo = ? AND estado = 'finalizado'"
            . ') t ON t.eq = e.id';

        $expresion = match ($clave) {
            'victorias' => 'SUM(gf > gc)',
            'gf'        => 'SUM(gf)',
            default     => 'SUM(gc)', // 'gc'
        };
        $orden = $clave === 'gc' ? 'ASC' : 'DESC';

        $filas = static::filas(
            "SELECT id_equipo, club, escudo, {$expresion} AS valor FROM ({$base}) x"
            . " GROUP BY id_equipo, club, escudo ORDER BY valor {$orden}, club",
            [$idTorneo, $idTorneo]
        );

        return static::posicionar($filas, $limite);
    }

    /** Agrega 'posicion' (empates comparten número: 1, 1, 3) y recorta al límite. */
    private static function posicionar(array $filas, int $limite): array
    {
        $anterior = null;
        $pos = 0;
        foreach ($filas as $i => &$f) {
            $f['valor'] = (int) $f['valor'];
            if ($f['valor'] !== $anterior) {
                $pos = $i + 1;
                $anterior = $f['valor'];
            }
            $f['posicion'] = $pos;
        }
        unset($f);

        return $limite > 0 ? array_slice($filas, 0, $limite) : $filas;
    }
}