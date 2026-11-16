<?php

declare(strict_types=1);

namespace Controllers;

use Model\Partido;

/**
 * Generador de fixture para:
 * 1. Ligas: todos contra todos (círculo de Berger o manual fecha por fecha).
 * 2. Copas con Grupos: todos contra todos dentro de cada grupo (círculo de Berger).
 *    En grupos el orden y los cruces manuales todavía no se usan; solo "sortear".
 * 3. Copas de Eliminación Directa (Playoff): cruces de la ronda inicial (por orden o manual).
 */
class FixtureApiController extends ApiController
{
    /**
     * POST /api/fases/fixture
     * Parámetros:
     * - id_fase: int (requerido)
     * - sortear: bool (opcional, true para mezclar equipos al azar)
     * - equipos_orden: list<int> (opcional, orden manual de los equipos; liga y eliminatoria)
     * - jornadas_manuales: list<list<array{local: int, visitante: int}>> (opcional, solo liga)
     * - cruces_playoff: list<array{local: int, visitante: int}> (opcional, solo eliminatoria)
     */
    public static function generar(): void
    {
        static::soloPost();
        $input = static::leerInput();

        $idFase = (int) ($input['id_fase'] ?? 0);
        if ($idFase <= 0) {
            static::cortar('Falta el identificador de la fase.', 422);
        }

        $sortear          = filter_var($input['sortear'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $equiposOrden     = isset($input['equipos_orden']) && is_array($input['equipos_orden'])
                            ? array_values(array_map('intval', $input['equipos_orden']))
                            : null;
        $jornadasManuales = isset($input['jornadas_manuales']) && is_array($input['jornadas_manuales'])
                            ? $input['jornadas_manuales']
                            : null;
        $crucesPlayoff    = isset($input['cruces_playoff']) && is_array($input['cruces_playoff'])
                            ? $input['cruces_playoff']
                            : null;

        $db = Partido::conexion();

        try {
            $db->beginTransaction();

            // Bloqueo de concurrencia FOR UPDATE
            $stmt = $db->prepare(
                'SELECT id, id_torneo, nombre, tipo, ida_vuelta, orden FROM fases WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$idFase]);
            $fase = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$fase) {
                static::cortar('Fase no encontrada.', 404);
            }

            $idTorneo  = (int) $fase['id_torneo'];
            $tipoFase  = (string) $fase['tipo'];
            $idaVuelta = (int) $fase['ida_vuelta'] === 1;

            $stmt = $db->prepare('SELECT cantidad_equipos, formato, tamano_grupo FROM torneos WHERE id = ?');
            $stmt->execute([$idTorneo]);
            $torneo = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$torneo) {
                static::cortar('Torneo no encontrado.', 404);
            }
            $cupo = (int) $torneo['cantidad_equipos'];

            // Obtener equipos inscriptos
            $stmt = $db->prepare(
                'SELECT te.id_equipo, te.id_grupo, e.nombre
                 FROM torneo_equipos te
                 INNER JOIN equipos e ON e.id = te.id_equipo
                 WHERE te.id_torneo = ?
                 ORDER BY e.nombre ASC, e.id ASC'
            );
            $stmt->execute([$idTorneo]);
            $inscriptos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // El cupo debe estar exacto: ni faltan ni sobran equipos
            $cantInscriptos = count($inscriptos);
            if ($cantInscriptos < $cupo) {
                $faltan = $cupo - $cantInscriptos;
                static::cortar(
                    "Faltan {$faltan} equipo(s) para completar el cupo ({$cupo}). Inscribilos antes de generar el fixture.",
                    409
                );
            }
            if ($cantInscriptos > $cupo) {
                $sobran = $cantInscriptos - $cupo;
                static::cortar(
                    "Hay {$sobran} equipo(s) de más: {$cantInscriptos} inscriptos para un cupo de {$cupo}. Quitá los sobrantes antes de generar el fixture.",
                    409
                );
            }

            // Verificar si la fase ya tiene partidos cargados
            $stmt = $db->prepare('SELECT COUNT(*) FROM partidos WHERE id_fase = ?');
            $stmt->execute([$idFase]);
            $existentes = (int) $stmt->fetchColumn();
            if ($existentes > 0) {
                static::cortar(
                    "La fase ya tiene {$existentes} partido(s) cargados. Borralos antes de regenerar.",
                    409
                );
            }

            $ahora = date('Y-m-d H:i:s');
            $ins = $db->prepare(
                'INSERT INTO partidos
                    (id_torneo, id_fase, id_grupo, jornada, llave_orden,
                     id_equipo_local, id_equipo_visitante, estado,
                     goles_local, goles_visitante, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, \'programado\', 0, 0, ?, ?)'
            );

            $totalPartidos = 0;
            $totalJornadas = 0;

            // =========================================================================
            // CASO 1: FASE DE LIGA (Todos contra todos)
            // =========================================================================
            if ($tipoFase === 'liga') {
                $idsEquipos = array_map(fn($r) => (int)$r['id_equipo'], $inscriptos);

                if ($jornadasManuales !== null) {
                    $jornadas = static::validarJornadasManuales($jornadasManuales, $idsEquipos, $idaVuelta);
                } else {
                    $lista = $idsEquipos;
                    if ($equiposOrden !== null && !empty($equiposOrden)) {
                        static::validarOrdenEquipos($equiposOrden, $idsEquipos);
                        $lista = $equiposOrden;
                    } elseif ($sortear) {
                        shuffle($lista);
                    }
                    $jornadas = static::calendarioCirculo($lista, $idaVuelta);
                }

                $totalJornadas = count($jornadas);
                foreach ($jornadas as $idx => $cruces) {
                    $jornadaNum = $idx + 1;
                    foreach ($cruces as [$local, $visitante]) {
                        $ins->execute([$idTorneo, $idFase, null, $jornadaNum, null, $local, $visitante, $ahora, $ahora]);
                        $totalPartidos++;
                    }
                }
            }
            // =========================================================================
            // CASO 2: FASE DE GRUPOS EN COPAS
            // =========================================================================
            elseif ($tipoFase === 'grupos') {
                // Obtener grupos de esta fase
                $stmt = $db->prepare('SELECT id, nombre FROM grupos WHERE id_fase = ? ORDER BY nombre ASC');
                $stmt->execute([$idFase]);
                $grupos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                if (empty($grupos)) {
                    static::cortar('No hay grupos creados para esta fase.', 422);
                }

                $tamanoEsperado = (int) $torneo['tamano_grupo'];
                $equiposPorGrupo = [];

                foreach ($grupos as $g) {
                    $gid = (int) $g['id'];
                    $equiposPorGrupo[$gid] = [];
                }

                foreach ($inscriptos as $e) {
                    if ($e['id_grupo'] === null || !isset($equiposPorGrupo[(int)$e['id_grupo']])) {
                        static::cortar('Hay equipos inscriptos que todavía no tienen grupo asignado.', 409);
                    }
                    $equiposPorGrupo[(int)$e['id_grupo']][] = (int) $e['id_equipo'];
                }

                foreach ($equiposPorGrupo as $gid => $eqs) {
                    if (count($eqs) !== $tamanoEsperado) {
                        static::cortar("Cada grupo debe tener exactamente {$tamanoEsperado} equipos asignados.", 409);
                    }
                }

                // Generar el calendario para cada grupo
                $maxJornadas = 0;
                foreach ($equiposPorGrupo as $gid => $eqs) {
                    $lista = $eqs;
                    if ($sortear) {
                        shuffle($lista);
                    }
                    $jornadasGrupo = static::calendarioCirculo($lista, $idaVuelta);
                    if (count($jornadasGrupo) > $maxJornadas) {
                        $maxJornadas = count($jornadasGrupo);
                    }

                    foreach ($jornadasGrupo as $idx => $cruces) {
                        $jornadaNum = $idx + 1;
                        foreach ($cruces as [$local, $visitante]) {
                            $ins->execute([$idTorneo, $idFase, $gid, $jornadaNum, null, $local, $visitante, $ahora, $ahora]);
                            $totalPartidos++;
                        }
                    }
                }
                $totalJornadas = $maxJornadas;
            }
            // =========================================================================
            // CASO 3: FASE ELIMINATORIA (PLAYOFF)
            // =========================================================================
            elseif ($tipoFase === 'eliminatoria') {
                $ordenFase = (int) $fase['orden'];
                $idsEquipos = array_map(fn($r) => (int)$r['id_equipo'], $inscriptos);

                // Solo se puede generar directamente si es la primera fase (Playoff directo sin grupos)
                if ($ordenFase !== 1) {
                    static::cortar(
                        'Los cruces de fases eliminatorias avanzadas se configuran al finalizar la fase previa.',
                        409
                    );
                }

                // Las rondas siguientes solo cuadran con 2, 4, 8, 16, 32...
                $n = count($idsEquipos);
                if ($n < 2 || ($n & ($n - 1)) !== 0) {
                    static::cortar(
                        "Una eliminatoria necesita 2, 4, 8, 16 o 32 equipos; hay {$n} inscriptos.",
                        422
                    );
                }

                $cruces = [];
                if ($crucesPlayoff !== null && !empty($crucesPlayoff)) {
                    // Cruces manuales definidos por el usuario
                    $cruces = static::validarCrucesPlayoff($crucesPlayoff, $idsEquipos);
                } else {
                    $lista = $idsEquipos;
                    if ($equiposOrden !== null && !empty($equiposOrden)) {
                        static::validarOrdenEquipos($equiposOrden, $idsEquipos);
                        $lista = $equiposOrden;
                    } elseif ($sortear) {
                        shuffle($lista);
                    }
                    // Armar pares 1 vs 2, 3 vs 4... (la cantidad ya es par)
                    for ($i = 0; $i < count($lista); $i += 2) {
                        $cruces[] = [$lista[$i], $lista[$i + 1]];
                    }
                }

                $totalJornadas = $idaVuelta ? 2 : 1;
                $llave = 1;
                foreach ($cruces as [$local, $visitante]) {
                    // Partido de ida
                    $ins->execute([$idTorneo, $idFase, null, 1, $llave, $local, $visitante, $ahora, $ahora]);
                    $totalPartidos++;

                    // Si la fase eliminatoria es de ida y vuelta
                    if ($idaVuelta) {
                        $ins->execute([$idTorneo, $idFase, null, 2, $llave, $visitante, $local, $ahora, $ahora]);
                        $totalPartidos++;
                    }
                    $llave++;
                }
            }

            $db->commit();
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            static::responderErrorBD($e, 'No se pudo generar el fixture.');
        }

        static::json([
            'ok'   => true,
            'data' => [
                'tipo_fase'  => $tipoFase,
                'jornadas'   => $totalJornadas,
                'partidos'   => $totalPartidos,
                'sorteado'   => $sortear,
                'ida_vuelta' => $idaVuelta,
            ],
        ], 201);
    }

    /**
     * POST /api/fases/fixture/borrar
     *
     * Solo se borra un fixture "limpio": todos los partidos en estado programado y sin
     * alineaciones ni eventos. Las claves foráneas de partido_jugadores y eventos_partido
     * son ON DELETE CASCADE, así que borrar un partido con datos los destruiría en silencio.
     */
    public static function borrar(): void
    {
        static::soloPost();
        $input = static::leerInput();

        $idFase = (int) ($input['id_fase'] ?? 0);
        if ($idFase <= 0) {
            static::cortar('Falta el identificador de la fase.', 422);
        }

        $db = Partido::conexion();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT id, id_torneo FROM fases WHERE id = ? FOR UPDATE');
            $stmt->execute([$idFase]);
            $fase = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$fase) {
                static::cortar('Fase no encontrada.', 404);
            }

            // Cualquier estado distinto de "programado" (en juego, finalizado, suspendido, cancelado)
            $stmt = $db->prepare("SELECT COUNT(*) FROM partidos WHERE id_fase = ? AND estado <> 'programado'");
            $stmt->execute([$idFase]);
            $noProgramados = (int) $stmt->fetchColumn();
            if ($noProgramados > 0) {
                static::cortar(
                    "No se puede borrar el fixture: hay {$noProgramados} partido(s) que no están en estado programado (jugados, en disputa, suspendidos o cancelados).",
                    409
                );
            }

            // Partidos programados que ya tienen alineación o eventos cargados
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM partidos p
                 WHERE p.id_fase = ?
                   AND (EXISTS (SELECT 1 FROM partido_jugadores pj WHERE pj.id_partido = p.id)
                     OR EXISTS (SELECT 1 FROM eventos_partido ev WHERE ev.id_partido = p.id))'
            );
            $stmt->execute([$idFase]);
            $conDatos = (int) $stmt->fetchColumn();
            if ($conDatos > 0) {
                static::cortar(
                    "No se puede borrar el fixture: {$conDatos} partido(s) ya tienen alineación o eventos cargados y se perderían.",
                    409
                );
            }

            $stmt = $db->prepare('DELETE FROM partidos WHERE id_fase = ?');
            $stmt->execute([$idFase]);
            $borrados = $stmt->rowCount();

            $db->commit();
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            static::responderErrorBD($e, 'No se pudo borrar el fixture.');
        }

        static::json([
            'ok'      => true,
            'message' => "Se eliminaron {$borrados} partido(s) del fixture.",
            'data'    => ['borrados' => $borrados],
        ]);
    }

    /**
     * El orden recibido debe contener exactamente los equipos inscriptos, sin repetidos ni ajenos.
     */
    private static function validarOrdenEquipos(array $orden, array $equiposInscriptos): void
    {
        $a = $orden;
        $b = $equiposInscriptos;
        sort($a);
        sort($b);

        if ($a !== $b) {
            static::cortar('La lista de equipos ordenada no coincide con los inscriptos.', 422);
        }
    }

    /**
     * Valida y normaliza un arreglo de cruces libres para liga
     */
    private static function validarJornadasManuales(array $jornadasInput, array $equiposInscriptos, bool $idaVuelta): array
    {
        if (empty($jornadasInput)) {
            static::cortar('El fixture manual debe contener al menos una jornada.', 422);
        }

        $mapaInscriptos = array_flip($equiposInscriptos);
        $crucesRegistrados = [];
        $jornadasNormalizadas = [];

        foreach ($jornadasInput as $indiceFecha => $partidosFecha) {
            if (!is_array($partidosFecha) || empty($partidosFecha)) {
                static::cortar('La jornada ' . ($indiceFecha + 1) . ' no contiene partidos.', 422);
            }

            $equiposEnEstaJornada = [];
            $crucesEstaJornada = [];

            foreach ($partidosFecha as $p) {
                $local = (int) ($p['local'] ?? ($p[0] ?? 0));
                $visit = (int) ($p['visitante'] ?? ($p[1] ?? 0));

                if ($local <= 0 || $visit <= 0 || $local === $visit) {
                    static::cortar('Cruce no válido en jornada ' . ($indiceFecha + 1) . '.', 422);
                }

                if (!isset($mapaInscriptos[$local]) || !isset($mapaInscriptos[$visit])) {
                    static::cortar("Los equipos {$local} o {$visit} no están inscriptos en este torneo.", 422);
                }

                if (isset($equiposEnEstaJornada[$local]) || isset($equiposEnEstaJornada[$visit])) {
                    static::cortar("Un equipo juega más de una vez en la jornada " . ($indiceFecha + 1) . '.', 422);
                }
                $equiposEnEstaJornada[$local] = true;
                $equiposEnEstaJornada[$visit] = true;

                // Ida y vuelta: A-B y B-A son partidos distintos.
                // Solo ida: A-B y B-A son el mismo cruce, así que la clave no distingue el orden.
                $claveCruce = $idaVuelta
                    ? "{$local}-{$visit}"
                    : min($local, $visit) . '-' . max($local, $visit);
                if (isset($crucesRegistrados[$claveCruce])) {
                    static::cortar("El cruce entre {$local} y {$visit} está repetido en el fixture.", 422);
                }
                $crucesRegistrados[$claveCruce] = true;

                $crucesEstaJornada[] = [$local, $visit];
            }

            $jornadasNormalizadas[] = $crucesEstaJornada;
        }

        return $jornadasNormalizadas;
    }

    /**
     * Valida pares de cruces para eliminatoria directa
     */
    private static function validarCrucesPlayoff(array $crucesInput, array $equiposInscriptos): array
    {
        $mapa = array_flip($equiposInscriptos);
        $usados = [];
        $cruces = [];

        foreach ($crucesInput as $c) {
            $local = (int) ($c['local'] ?? ($c[0] ?? 0));
            $visit = (int) ($c['visitante'] ?? ($c[1] ?? 0));

            if ($local <= 0 || $visit <= 0 || $local === $visit) {
                static::cortar('Cruce de playoff no válido.', 422);
            }
            if (!isset($mapa[$local]) || !isset($mapa[$visit])) {
                static::cortar("Equipos no válidos para el playoff.", 422);
            }
            if (isset($usados[$local]) || isset($usados[$visit])) {
                static::cortar("Un equipo no puede figurar en más de una llave de playoff.", 422);
            }
            $usados[$local] = true;
            $usados[$visit] = true;
            $cruces[] = [$local, $visit];
        }

        if (count($usados) !== count($equiposInscriptos)) {
            static::cortar('Todos los equipos inscriptos deben estar asignados a una llave.', 422);
        }

        return $cruces;
    }

    /**
     * Todos contra todos con local/visitante equilibrado.
     *
     * Usa el esquema del círculo (el equipo fijo alterna de condición en cada fecha y
     * en los demás cruces se alterna según la distancia en el círculo). En la ida ningún
     * equipo juega más de 2 partidos seguidos de local o de visitante. En ida y vuelta se
     * elige el orden de la vuelta para que esa regla también se cumpla en el empalme.
     */
    private static function calendarioCirculo(array $ids, bool $idaVuelta): array
    {
        $ida = static::calendarioIda(array_values($ids));

        if (!$idaVuelta) {
            return $ida;
        }

        // La vuelta repite todos los cruces con la localía invertida
        $espejo = array_map(
            static fn (array $cruces): array => array_map(
                static fn (array $c): array => [$c[1], $c[0]],
                $cruces
            ),
            $ida
        );

        // Se prueba el orden clásico, luego invertido y luego rotaciones, y se usa
        // el primero que no deje a ningún equipo con más de 2 seguidos de la misma condición.
        $candidatos = [$espejo, array_reverse($espejo)];
        $fechas = count($espejo);
        for ($s = 1; $s < $fechas; $s++) {
            $candidatos[] = array_merge(array_slice($espejo, $s), array_slice($espejo, 0, $s));
        }

        foreach ($candidatos as $vuelta) {
            $calendario = array_merge($ida, $vuelta);
            if (static::rachaMaxima($calendario) <= 2) {
                return $calendario;
            }
        }

        return array_merge($ida, $espejo);
    }

    /**
     * Una sola rueda. Con cantidad impar se agrega un descanso (null) que se omite.
     */
    private static function calendarioIda(array $equipos): array
    {
        if (count($equipos) < 2) {
            return [];
        }

        if (count($equipos) % 2 === 1) {
            $equipos[] = null;
        }

        $n = count($equipos);
        $m = $n - 1;          // equipos que giran; el último queda fijo
        $ida = [];

        for ($ronda = 0; $ronda < $m; $ronda++) {
            $cruces = [];

            // Cruce del equipo fijo: alterna de local a visitante en cada fecha
            $a = $equipos[$ronda];
            $b = $equipos[$m];
            $par = ($ronda % 2 === 0) ? [$a, $b] : [$b, $a];
            if ($par[0] !== null && $par[1] !== null) {
                $cruces[] = $par;
            }

            // Resto de cruces: simétricos respecto al equipo que "descansa" en el círculo
            for ($k = 1; $k < intdiv($n, 2); $k++) {
                $x = $equipos[($ronda + $k) % $m];
                $y = $equipos[(($ronda - $k) % $m + $m) % $m];
                $par = ($k % 2 === 1) ? [$x, $y] : [$y, $x];
                if ($par[0] !== null && $par[1] !== null) {
                    $cruces[] = $par;
                }
            }

            $ida[] = $cruces;
        }

        return $ida;
    }

    /**
     * Mayor cantidad de partidos seguidos de local o de visitante de cualquier equipo.
     */
    private static function rachaMaxima(array $calendario): int
    {
        $ultima = [];   // equipo => última condición (true = local)
        $actual = [];   // equipo => largo de la racha en curso
        $maxima = 0;

        foreach ($calendario as $cruces) {
            foreach ($cruces as [$local, $visitante]) {
                foreach ([[$local, true], [$visitante, false]] as [$equipo, $esLocal]) {
                    if (isset($ultima[$equipo]) && $ultima[$equipo] === $esLocal) {
                        $actual[$equipo]++;
                    } else {
                        $actual[$equipo] = 1;
                    }
                    $ultima[$equipo] = $esLocal;
                    $maxima = max($maxima, $actual[$equipo]);
                }
            }
        }

        return $maxima;
    }

    private static function cortar(string $mensaje, int $codigo): never
    {
        $db = Partido::conexion();
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        static::json(['ok' => false, 'message' => $mensaje], $codigo);
    }
}