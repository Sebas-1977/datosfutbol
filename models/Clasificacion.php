<?php

declare(strict_types=1);

namespace Model;

class Clasificacion
{
    private const PUNTOS_VICTORIA = 3;
    private const PUNTOS_EMPATE   = 1;

    /**
     * Devuelve las fases del torneo con sus tablas:
     * [ ['fase' => string, 'tipo' => 'liga'|'grupos'|'eliminatoria',
     *    'tablas' => [ ['titulo' => ?string, 'filas' => [...]] ] ] ]
     */
    public static function deTorneo(int $idTorneo): array
    {
        $st = Partido::conexion()->prepare(
            'SELECT p.id_fase, p.id_grupo, p.llave_orden, p.estado,'
            . ' p.id_equipo_local, p.id_equipo_visitante,'
            . ' p.goles_local, p.goles_visitante,'
            . ' el.nombre AS local, ev.nombre AS visitante,'
            . ' el.escudo AS escudo_local, ev.escudo AS escudo_visitante,'
            . ' f.nombre AS fase, g.nombre AS grupo'
            . ' FROM partidos p'
            . ' INNER JOIN equipos el ON el.id = p.id_equipo_local'
            . ' INNER JOIN equipos ev ON ev.id = p.id_equipo_visitante'
            . ' LEFT JOIN fases f ON f.id = p.id_fase'
            . ' LEFT JOIN grupos g ON g.id = p.id_grupo'
            . ' WHERE p.id_torneo = ?'
            . ' ORDER BY p.id_fase, p.id_grupo'
        );
        $st->execute([$idTorneo]);
        $partidos = $st->fetchAll(\PDO::FETCH_ASSOC);

        $porFase = [];
        foreach ($partidos as $p) {
            $porFase[(int) $p['id_fase']][] = $p;
        }

        $fases = [];
        foreach ($porFase as $lista) {
            $nombre = $lista[0]['fase'] ?? 'Fase';

            if (self::hayValor($lista, 'llave_orden')) {
                $fases[] = ['fase' => $nombre, 'tipo' => 'eliminatoria', 'tablas' => []];
                continue;
            }

            if (self::hayValor($lista, 'id_grupo')) {
                $porGrupo = [];
                foreach ($lista as $p) {
                    $porGrupo[(int) $p['id_grupo']][] = $p;
                }
                $tablas = [];
                foreach ($porGrupo as $listaGrupo) {
                    $tablas[] = [
                        'titulo' => $listaGrupo[0]['grupo'] ?? 'Grupo',
                        'filas'  => self::calcular($listaGrupo),
                    ];
                }
                $fases[] = ['fase' => $nombre, 'tipo' => 'grupos', 'tablas' => $tablas];
                continue;
            }

            $fases[] = [
                'fase'   => $nombre,
                'tipo'   => 'liga',
                'tablas' => [['titulo' => null, 'filas' => self::calcular($lista)]],
            ];
        }

        return $fases;
    }

    /** Primera tabla con filas del torneo (para la mini tabla de la portada). */
    public static function principal(int $idTorneo): ?array
    {
        foreach (self::deTorneo($idTorneo) as $fase) {
            foreach ($fase['tablas'] as $tabla) {
                if ($tabla['filas']) {
                    return $tabla + ['fase' => $fase['fase']];
                }
            }
        }
        return null;
    }

   /** Tabla ordenada a partir de una lista de partidos. */
public static function calcular(array $partidos): array
{
    $equipos = [];
    $escudos = [];
    foreach ($partidos as $p) {
        $l = (int) $p['id_equipo_local'];
        $v = (int) $p['id_equipo_visitante'];
        $equipos[$l] = $p['local'];
        $equipos[$v] = $p['visitante'];
        $escudos[$l] = $p['escudo_local'] ?? null;
        $escudos[$v] = $p['escudo_visitante'] ?? null;
    }

    $filas = self::acumular($equipos, $partidos);

    foreach ($filas as $id => &$f) {
        $f['escudo'] = $escudos[$id] ?? null;
    }
    unset($f);

    return self::ordenar($filas, $partidos);
}

    private static function hayValor(array $lista, string $clave): bool
    {
        foreach ($lista as $p) {
            if ($p[$clave] !== null) {
                return true;
            }
        }
        return false;
    }

    /** Suma estadísticas de los partidos finalizados. $equipos = [id => nombre]. */
    private static function acumular(array $equipos, array $partidos): array
    {
        $filas = [];
        foreach ($equipos as $id => $nombre) {
            $filas[$id] = [
                'id' => $id, 'nombre' => $nombre,
                'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0,
                'gf' => 0, 'gc' => 0, 'dg' => 0, 'pts' => 0,
            ];
        }

        foreach ($partidos as $p) {
            if ($p['estado'] !== 'finalizado') {
                continue;
            }
            $l = (int) $p['id_equipo_local'];
            $v = (int) $p['id_equipo_visitante'];
            if (!isset($filas[$l], $filas[$v])) {
                continue;
            }
            $gl = (int) $p['goles_local'];
            $gv = (int) $p['goles_visitante'];

            foreach ([[$l, $gl, $gv], [$v, $gv, $gl]] as [$id, $gf, $gc]) {
                $filas[$id]['pj']++;
                $filas[$id]['gf'] += $gf;
                $filas[$id]['gc'] += $gc;
                if ($gf > $gc) {
                    $filas[$id]['pg']++;
                    $filas[$id]['pts'] += self::PUNTOS_VICTORIA;
                } elseif ($gf === $gc) {
                    $filas[$id]['pe']++;
                    $filas[$id]['pts'] += self::PUNTOS_EMPATE;
                } else {
                    $filas[$id]['pp']++;
                }
            }
        }

        foreach ($filas as &$f) {
            $f['dg'] = $f['gf'] - $f['gc'];
        }
        unset($f);

        return $filas;
    }

    private static function comparar(array $a, array $b): int
    {
        return ([$b['pts'], $b['dg'], $b['gf']] <=> [$a['pts'], $a['dg'], $a['gf']])
            ?: strcmp($a['nombre'], $b['nombre']);
    }

    /** Puntos, diferencia y goles a favor; los que siguen empatados se resuelven por enfrentamiento directo. */
    private static function ordenar(array $filas, array $partidos): array
    {
        $filas = array_values($filas);
        usort($filas, [self::class, 'comparar']);

        $res = [];
        $n = count($filas);
        $clave = fn(array $f) => [$f['pts'], $f['dg'], $f['gf']];

        for ($i = 0; $i < $n; ) {
            $j = $i;
            while ($j + 1 < $n && $clave($filas[$j + 1]) === $clave($filas[$i])) {
                $j++;
            }
            $bloque = array_slice($filas, $i, $j - $i + 1);
            if (count($bloque) > 1) {
                $bloque = self::desempatarDirecto($bloque, $partidos);
            }
            array_push($res, ...$bloque);
            $i = $j + 1;
        }

        return $res;
    }

    private static function desempatarDirecto(array $bloque, array $partidos): array
    {
        $ids = array_column($bloque, 'id');

        $entreEllos = array_filter($partidos, fn($p) =>
            in_array((int) $p['id_equipo_local'], $ids, true)
            && in_array((int) $p['id_equipo_visitante'], $ids, true)
        );

        $equipos = array_column($bloque, 'nombre', 'id');
        $mini    = self::acumular($equipos, $entreEllos);

        usort($bloque, function ($a, $b) use ($mini) {
            return self::comparar($mini[$a['id']], $mini[$b['id']]);
        });

        return $bloque;
    }
}