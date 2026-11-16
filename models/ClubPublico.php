<?php

declare(strict_types=1);

namespace Model;

/** Consultas de solo lectura de clubes para el sitio público. */
class ClubPublico
{
    private static function filas(string $sql, array $params = []): array
    {
        $st = Partido::conexion()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Clubes del torneo con su cancha local y cantidad de jugadores. */
    public static function listado(int $idTorneo): array
    {
        return static::filas(
            'SELECT e.id, e.nombre, e.escudo, e.ciudad, c.nombre AS cancha,'
            . ' (SELECT COUNT(*) FROM jugadores_equipo je'
            . '   WHERE je.id_equipo = e.id AND je.id_torneo = ?) AS cantidad'
            . ' FROM equipos e'
            . ' LEFT JOIN canchas c ON c.id = e.id_cancha_local'
            . ' WHERE e.id IN (SELECT id_equipo_local FROM partidos WHERE id_torneo = ?)'
            . '    OR e.id IN (SELECT id_equipo_visitante FROM partidos WHERE id_torneo = ?)'
            . '    OR e.id IN (SELECT id_equipo FROM jugadores_equipo WHERE id_torneo = ?)'
            . ' ORDER BY e.nombre',
            [$idTorneo, $idTorneo, $idTorneo, $idTorneo]
        );
    }

    /** Iniciales sin artículos: "Los Osos Grises" => "OG", "Los Pibes" => "PI". */
    public static function iniciales(string $nombre): string
    {
        $palabras = preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $utiles = array_values(array_filter(
            $palabras,
            fn (string $p) => !in_array(mb_strtolower($p), ['el', 'la', 'los', 'las'], true)
        )) ?: $palabras;

        if (count($utiles) === 1) {
            return mb_strtoupper(mb_substr($utiles[0], 0, 2));
        }

        return mb_strtoupper(implode('', array_map(
            fn (string $p) => mb_substr($p, 0, 1),
            array_slice($utiles, 0, 2)
        )));
    }

        /** Un club con su cancha local, o null si no existe. */
    public static function equipo(int $idEquipo): ?array
    {
        $filas = static::filas(
            'SELECT e.*, c.nombre AS cancha, c.capacidad AS cancha_capacidad'
            . ' FROM equipos e'
            . ' LEFT JOIN canchas c ON c.id = e.id_cancha_local'
            . ' WHERE e.id = ?',
            [$idEquipo]
        );

        return $filas[0] ?? null;
    }

    /** Partidos del club en el torneo, en orden cronológico (sin fecha al final). */
    public static function partidos(int $idTorneo, int $idEquipo): array
    {
        return static::filas(
            'SELECT p.id, p.jornada, p.fecha_hora, p.estado,'
            . ' p.goles_local, p.goles_visitante,'
            . ' p.id_equipo_local, p.id_equipo_visitante,'
            . ' l.nombre AS local, v.nombre AS visitante,'
            . ' l.escudo AS escudo_local, v.escudo AS escudo_visitante'
            . ' FROM partidos p'
            . ' INNER JOIN equipos l ON l.id = p.id_equipo_local'
            . ' INNER JOIN equipos v ON v.id = p.id_equipo_visitante'
            . ' WHERE p.id_torneo = ?'
            . '   AND (p.id_equipo_local = ? OR p.id_equipo_visitante = ?)'
            . ' ORDER BY p.fecha_hora IS NULL, p.fecha_hora, p.id',
            [$idTorneo, $idEquipo, $idEquipo]
        );
    }

    /** PJ, G, E, P, GF y GC a partir de los partidos finalizados. */
    public static function estadisticas(int $idTorneo, int $idEquipo): array
    {
        $fila = static::filas(
            'SELECT COUNT(*) AS pj,'
            . ' COALESCE(SUM(gf > gc), 0) AS g,'
            . ' COALESCE(SUM(gf = gc), 0) AS e,'
            . ' COALESCE(SUM(gf < gc), 0) AS p,'
            . ' COALESCE(SUM(gf), 0) AS gf,'
            . ' COALESCE(SUM(gc), 0) AS gc'
            . ' FROM ('
            . '   SELECT IF(id_equipo_local = ?, goles_local, goles_visitante) AS gf,'
            . '          IF(id_equipo_local = ?, goles_visitante, goles_local) AS gc'
            . '   FROM partidos'
            . "   WHERE id_torneo = ? AND estado = 'finalizado'"
            . '     AND (id_equipo_local = ? OR id_equipo_visitante = ?)'
            . ' ) t',
            [$idEquipo, $idEquipo, $idTorneo, $idEquipo, $idEquipo]
        )[0] ?? [];

        return array_map('intval', $fila + ['pj' => 0, 'g' => 0, 'e' => 0, 'p' => 0, 'gf' => 0, 'gc' => 0]);
    }

    /** Amarillas y rojas (la doble amarilla cuenta como roja). */
    public static function tarjetas(int $idTorneo, int $idEquipo): array
    {
        $fila = static::filas(
            'SELECT COALESCE(SUM(ev.tipo = \'tarjeta_amarilla\'), 0) AS amarillas,'
            . ' COALESCE(SUM(ev.tipo IN (\'tarjeta_roja\', \'doble_amarilla\')), 0) AS rojas'
            . ' FROM eventos_partido ev'
            . ' INNER JOIN partidos p ON p.id = ev.id_partido'
            . ' WHERE p.id_torneo = ? AND ev.id_equipo = ?',
            [$idTorneo, $idEquipo]
        )[0] ?? [];

        return array_map('intval', $fila + ['amarillas' => 0, 'rojas' => 0]);
    }

    /** Tablas (grupo o liga) del torneo donde aparece el club, con todas sus filas. */
    public static function clasificacion(int $idTorneo, int $idEquipo): array
    {
        $res = [];
        foreach (Clasificacion::deTorneo($idTorneo) as $fase) {
            foreach ($fase['tablas'] as $tabla) {
                foreach ($tabla['filas'] as $f) {
                    if ((int) $f['id'] === $idEquipo) {
                        $res[] = [
                            'fase'   => $fase['fase'],
                            'titulo' => $tabla['titulo'],
                            'filas'  => $tabla['filas'],
                        ];
                        break;
                    }
                }
            }
        }

        return $res;
    }
}