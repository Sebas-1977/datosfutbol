<?php

declare(strict_types=1);

namespace Controllers;

use Model\Torneo;

/**
 * Inscripción de equipos en un torneo y asignación a grupos.
 * Cada acción se guarda al instante (como las fases).
 */
class TorneoEquipoApiController extends ApiController
{
    // GET /api/torneos/equipos?id_torneo=X
    // Todos los equipos con su estado en el torneo, más los grupos y el cupo.
    public static function inscriptos(): void
    {
        $idTorneo = (int) ($_GET['id_torneo'] ?? 0);
        $torneo = static::torneo($idTorneo);
        $db = Torneo::conexion();

        $stmt = $db->prepare(
            "SELECT g.id, g.nombre
             FROM grupos g
             INNER JOIN fases f ON f.id = g.id_fase
             WHERE f.id_torneo = ? AND f.tipo = 'grupos'
             ORDER BY g.nombre ASC, g.id ASC"
        );
        $stmt->execute([$idTorneo]);
        $grupos = array_map(
            static fn (array $g): array => ['id' => (int) $g['id'], 'nombre' => $g['nombre']],
            $stmt->fetchAll(\PDO::FETCH_ASSOC)
        );

        $stmt = $db->prepare(
            'SELECT e.id, e.nombre, e.escudo,
                    (te.id_equipo IS NOT NULL) AS inscripto,
                    te.id_grupo,
                    (SELECT COUNT(*) FROM partidos p
                      WHERE p.id_torneo = ?
                        AND (p.id_equipo_local = e.id OR p.id_equipo_visitante = e.id)) AS total_partidos
             FROM equipos e
             LEFT JOIN torneo_equipos te ON te.id_equipo = e.id AND te.id_torneo = ?
             ORDER BY e.nombre ASC'
        );
        $stmt->execute([$idTorneo, $idTorneo]);

        $equipos = [];
        $inscriptos = 0;
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $e) {
            $inscripto = (int) $e['inscripto'] === 1;
            $inscriptos += $inscripto ? 1 : 0;
            $equipos[] = [
                'id'             => (int) $e['id'],
                'nombre'         => $e['nombre'],
                'escudo'         => $e['escudo'],
                'inscripto'      => $inscripto,
                'id_grupo'       => $e['id_grupo'] === null ? null : (int) $e['id_grupo'],
                'total_partidos' => (int) $e['total_partidos'],
            ];
        }

        static::json([
            'ok'   => true,
            'data' => [
                'cupo'         => (int) $torneo['cantidad_equipos'],
                'tamano_grupo' => $torneo['tamano_grupo'] === null ? null : (int) $torneo['tamano_grupo'],
                'inscriptos'   => $inscriptos,
                'grupos'       => $grupos,
                'equipos'      => $equipos,
            ],
        ]);
    }

    // POST /api/torneos/equipos/inscribir  {id_torneo, id_equipo}
    public static function inscribir(): void
    {
        static::soloPost();
        [$idTorneo, $idEquipo] = static::ids();

        $torneo = static::torneo($idTorneo);
        $db = Torneo::conexion();

        $stmt = $db->prepare('SELECT 1 FROM equipos WHERE id = ?');
        $stmt->execute([$idEquipo]);
        if ($stmt->fetchColumn() === false) {
            static::cortar('El equipo no existe.', 404);
        }

        if (static::inscripcion($idTorneo, $idEquipo) !== null) {
            static::cortar('Ese equipo ya está inscripto en el torneo.', 409);
        }

        $cupo = (int) $torneo['cantidad_equipos'];
        $inscriptos = static::contarInscriptos($idTorneo);
        if ($inscriptos >= $cupo) {
            static::cortar("El torneo ya tiene los {$cupo} equipos del cupo. Quitá uno o ampliá el cupo.", 409);
        }

        try {
            $ins = $db->prepare('INSERT INTO torneo_equipos (id_torneo, id_equipo) VALUES (?, ?)');
            $ins->execute([$idTorneo, $idEquipo]);
        } catch (\PDOException $e) {
            static::responderErrorBD($e, 'No se pudo inscribir el equipo.');
        }

        static::json(['ok' => true, 'data' => ['inscriptos' => $inscriptos + 1]], 201);
    }

    // POST /api/torneos/equipos/quitar  {id_torneo, id_equipo}
    public static function quitar(): void
    {
        static::soloPost();
        [$idTorneo, $idEquipo] = static::ids();

        static::torneo($idTorneo);
        $db = Torneo::conexion();

        if (static::inscripcion($idTorneo, $idEquipo) === null) {
            static::cortar('Ese equipo no está inscripto en el torneo.', 404);
        }

        $partidos = static::contarPartidos($idTorneo, $idEquipo);
        if ($partidos > 0) {
            static::cortar(
                'No se puede quitar el equipo: tiene '
                . $partidos . ($partidos === 1 ? ' partido cargado' : ' partidos cargados') . ' en este torneo.',
                409
            );
        }

        $stmt = $db->prepare('SELECT COUNT(*) FROM jugadores_equipo WHERE id_torneo = ? AND id_equipo = ?');
        $stmt->execute([$idTorneo, $idEquipo]);
        $plantel = (int) $stmt->fetchColumn();
        if ($plantel > 0) {
            static::cortar(
                'No se puede quitar el equipo: tiene '
                . $plantel . ($plantel === 1 ? ' jugador' : ' jugadores')
                . ' en su plantel de este torneo. Quitalos del plantel primero.',
                409
            );
        }

        try {
            $del = $db->prepare('DELETE FROM torneo_equipos WHERE id_torneo = ? AND id_equipo = ?');
            $del->execute([$idTorneo, $idEquipo]);
        } catch (\PDOException $e) {
            static::responderErrorBD($e, 'No se pudo quitar el equipo.');
        }

        static::json(['ok' => true, 'data' => ['inscriptos' => static::contarInscriptos($idTorneo)]]);
    }

    // POST /api/torneos/equipos/grupo  {id_torneo, id_equipo, id_grupo|null}
    public static function asignarGrupo(): void
    {
        static::soloPost();
        [$idTorneo, $idEquipo] = static::ids();
        $input = static::leerInput();

        $torneo = static::torneo($idTorneo);
        $db = Torneo::conexion();

        if ($torneo['tamano_grupo'] === null) {
            static::cortar('Este torneo no tiene grupos.', 409);
        }
        $tamano = (int) $torneo['tamano_grupo'];

        if (static::inscripcion($idTorneo, $idEquipo) === null) {
            static::cortar('Ese equipo no está inscripto en el torneo.', 404);
        }

        $partidos = static::contarPartidos($idTorneo, $idEquipo);
        if ($partidos > 0) {
            static::cortar('No se puede cambiar de grupo: el equipo tiene partidos cargados en este torneo.', 409);
        }

        $g = $input['id_grupo'] ?? null;
        $idGrupo = ($g === null || $g === '') ? null : (int) $g;

        if ($idGrupo !== null) {
            $stmt = $db->prepare(
                "SELECT g.nombre
                 FROM grupos g
                 INNER JOIN fases f ON f.id = g.id_fase
                 WHERE g.id = ? AND f.id_torneo = ? AND f.tipo = 'grupos'"
            );
            $stmt->execute([$idGrupo, $idTorneo]);
            $nombreGrupo = $stmt->fetchColumn();
            if ($nombreGrupo === false) {
                static::cortar('Ese grupo no pertenece al torneo.', 422);
            }

            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM torneo_equipos WHERE id_torneo = ? AND id_grupo = ? AND id_equipo <> ?'
            );
            $stmt->execute([$idTorneo, $idGrupo, $idEquipo]);
            if ((int) $stmt->fetchColumn() >= $tamano) {
                static::cortar("El {$nombreGrupo} ya tiene sus {$tamano} equipos.", 409);
            }
        }

        try {
            $upd = $db->prepare('UPDATE torneo_equipos SET id_grupo = ? WHERE id_torneo = ? AND id_equipo = ?');
            $upd->execute([$idGrupo, $idTorneo, $idEquipo]);
        } catch (\PDOException $e) {
            static::responderErrorBD($e, 'No se pudo asignar el grupo.');
        }

        static::json(['ok' => true, 'data' => ['id_grupo' => $idGrupo]]);
    }

    // ---------- Internos ----------

    /** @return array{0:int,1:int} id_torneo e id_equipo del cuerpo, ya validados */
    private static function ids(): array
    {
        $input = static::leerInput();
        $idTorneo = (int) ($input['id_torneo'] ?? 0);
        $idEquipo = (int) ($input['id_equipo'] ?? 0);

        if ($idTorneo <= 0 || $idEquipo <= 0) {
            static::cortar('Faltan el torneo o el equipo.', 422);
        }

        return [$idTorneo, $idEquipo];
    }

    /** @return array{id:int|string,cantidad_equipos:int|string,tamano_grupo:int|string|null} */
    private static function torneo(int $id): array
    {
        $stmt = Torneo::conexion()->prepare(
            'SELECT id, cantidad_equipos, tamano_grupo FROM torneos WHERE id = ?'
        );
        $stmt->execute([$id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$fila) {
            static::cortar('Torneo no encontrado.', 404);
        }

        return $fila;
    }

    /** Fila de torneo_equipos o null si el equipo no está inscripto */
    private static function inscripcion(int $idTorneo, int $idEquipo): ?array
    {
        $stmt = Torneo::conexion()->prepare(
            'SELECT id_grupo FROM torneo_equipos WHERE id_torneo = ? AND id_equipo = ?'
        );
        $stmt->execute([$idTorneo, $idEquipo]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $fila === false ? null : $fila;
    }

    private static function contarInscriptos(int $idTorneo): int
    {
        $stmt = Torneo::conexion()->prepare('SELECT COUNT(*) FROM torneo_equipos WHERE id_torneo = ?');
        $stmt->execute([$idTorneo]);

        return (int) $stmt->fetchColumn();
    }

    private static function contarPartidos(int $idTorneo, int $idEquipo): int
    {
        $stmt = Torneo::conexion()->prepare(
            'SELECT COUNT(*) FROM partidos
             WHERE id_torneo = ? AND (id_equipo_local = ? OR id_equipo_visitante = ?)'
        );
        $stmt->execute([$idTorneo, $idEquipo, $idEquipo]);

        return (int) $stmt->fetchColumn();
    }

    private static function cortar(string $mensaje, int $codigo): never
    {
        static::json(['ok' => false, 'message' => $mensaje], $codigo);
    }
}