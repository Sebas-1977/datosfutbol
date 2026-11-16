<?php

declare(strict_types=1);

namespace Controllers;

use Model\ActiveRecord;
use Model\Torneo;

class TorneoApiController extends ApiController
{
    protected static string $modelo = Torneo::class;

    // ida_vuelta lo mantienen las fases; clasifican se guarda en la fase de grupos
    protected static array $camposPermitidos = ['nombre', 'formato', 'cantidad_equipos', 'tamano_grupo'];
    protected static string $orden = 'nombre ASC';

    /** POST /api/torneos/visibilidad – alterna el campo `publico` sin tocar fases */
    public static function visibilidad(): void
    {
        static::soloPost();

        $input = static::leerInput();
        $id    = (int) ($input['id'] ?? 0);
        $torneo = Torneo::find($id);

        if (!$torneo) {
            static::json(['ok' => false, 'message' => 'Torneo no encontrado.'], 404);
        }

        $torneo->publico = isset($input['publico']) ? ((int) $input['publico'] === 1 ? 1 : 0) : ($torneo->publico === 1 ? 0 : 1);

        $db = Torneo::conexion();
        $db->prepare('UPDATE torneos SET publico = ? WHERE id = ?')->execute([$torneo->publico, $id]);

        static::json(['ok' => true, 'publico' => $torneo->publico]);
    }

    // GET: torneos con su configuración, fases y conteos
    public static function listar(): void
    {
        $db = Torneo::conexion();

        $torneos = $db->query(
            'SELECT t.id, t.nombre, t.formato, t.cantidad_equipos, t.tamano_grupo, t.ida_vuelta, t.publico,
                (SELECT COUNT(*) FROM fases f WHERE f.id_torneo = t.id) AS total_fases,
                (SELECT COUNT(*) FROM partidos p WHERE p.id_torneo = t.id) AS total_partidos,
                (SELECT COUNT(*) FROM jugadores_equipo je WHERE je.id_torneo = t.id) AS total_plantel,
                (SELECT COUNT(*) FROM torneo_equipos te WHERE te.id_torneo = t.id) AS total_equipos
             FROM torneos t
             ORDER BY t.nombre ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);

        $fases = $db->query(
            'SELECT f.id, f.id_torneo, f.nombre, f.tipo, f.ida_vuelta, f.orden, f.clasifican,
                    (SELECT COUNT(*) FROM partidos p WHERE p.id_fase = f.id) AS total_partidos
             FROM fases f ORDER BY f.orden ASC, f.id ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);

        $fasesPorTorneo = [];
        foreach ($fases as $f) {
            $fasesPorTorneo[(int) $f['id_torneo']][] = [
                'id'             => (int) $f['id'],
                'nombre'         => $f['nombre'],
                'tipo'           => $f['tipo'],
                'ida_vuelta'     => (int) $f['ida_vuelta'],
                'orden'          => (int) $f['orden'],
                'clasifican'     => $f['clasifican'] === null ? null : (int) $f['clasifican'],
                'total_partidos' => (int) $f['total_partidos'],
            ];
        }

        $data = [];
        foreach ($torneos as $t) {
            $id = (int) $t['id'];
            $fasesTorneo = $fasesPorTorneo[$id] ?? [];
            $tamanoGrupo = $t['tamano_grupo'] === null ? null : (int) $t['tamano_grupo'];

            $clasifican = null;
            foreach ($fasesTorneo as $f) {
                if ($f['tipo'] === 'grupos') {
                    $clasifican = $f['clasifican'];
                }
            }

            $data[] = [
                'id'               => $id,
                'nombre'           => $t['nombre'],
                'formato'          => $t['formato'],
                'tipo'             => Torneo::tipo($t['formato'], $tamanoGrupo),
                'cantidad_equipos' => (int) $t['cantidad_equipos'],
                'tamano_grupo'     => $tamanoGrupo,
                'clasifican'       => $clasifican,
                'ida_vuelta'       => (int) $t['ida_vuelta'],
                'publico'          => (int) ($t['publico'] ?? 0),
                'total_fases'      => (int) $t['total_fases'],
                'total_partidos'   => (int) $t['total_partidos'],
                'total_plantel'    => (int) $t['total_plantel'],
                'total_equipos'    => (int) $t['total_equipos'],
                'fases'            => $fasesTorneo,
            ];
        }

        static::json(['ok' => true, 'data' => $data]);
    }

    // POST: crea el torneo o actualiza su nombre; la configuración inicial es inmutable.
    public static function guardar(): void
    {
        static::soloPost();

        $input = static::leerInput();
        $id = (int) ($input['id'] ?? 0);

        $actual = null;
        if ($id > 0) {
            $actual = Torneo::find($id);
            if (!$actual) {
                static::json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
            }
        }

        // sincronizar() ignora el nombre vacío al editar, así que se controla acá
        if ($actual && array_key_exists('nombre', $input) && trim((string) $input['nombre']) === '') {
            static::json([
                'ok' => false,
                'alertas' => ['error' => ['El nombre del torneo es obligatorio.']],
            ], 422);
        }

        // Leer valores propuestos (o los actuales si no vienen)
        $formato = (string) ($input['formato'] ?? ($actual ? $actual->formato : 'liga'));
        $cantidad = (int) ($input['cantidad_equipos'] ?? ($actual ? $actual->cantidad_equipos : 8));
        $tamano = array_key_exists('tamano_grupo', $input)
            ? ($input['tamano_grupo'] === '' || $input['tamano_grupo'] === null ? null : (int) $input['tamano_grupo'])
            : ($actual ? $actual->tamano_grupo : null);

        // En ligas nunca hay grupos
        if ($formato === 'liga') {
            $tamano = null;
        }

        // Clasifican: obligatorio al crear o regenerar una copa con grupos
        $clasifican = null;
        if ($formato === 'copa' && $tamano !== null) {
            $c = $input['clasifican'] ?? null;
            $clasifican = ($c === '' || $c === null) ? null : (int) $c;
        }

        $estructura = Torneo::estructura($formato, $cantidad, $tamano, $clasifican);
        if (!$estructura) {
            static::json([
                'ok' => false,
                'alertas' => ['error' => ['La combinación de formato, cantidad de equipos y tamaño de grupo no es válida.']],
            ], 422);
        }

        // La estructura se define al crear el torneo y no puede modificarse después.
        $cambiaConfiguracion = $actual && (
            $actual->formato !== $formato
            || $actual->cantidad_equipos !== $cantidad
            || $actual->tamano_grupo !== $tamano
            || static::clasificanActual($actual->id) !== $clasifican
        );

        if ($cambiaConfiguracion) {
            static::json([
                'ok' => false,
                'alertas' => ['error' => ['La configuración no se puede cambiar después de crear el torneo. Si todavía no tiene partidos, eliminá el torneo y volvé a crearlo con la configuración adecuada.']],
            ], 409);
        }

        // Instanciar o reutilizar
        $torneo = $actual ?? new Torneo();
        $torneo->sincronizar([
            'nombre'           => $input['nombre'] ?? $torneo->nombre,
            'formato'          => $formato,
            'cantidad_equipos' => $cantidad,
            'tamano_grupo'     => $tamano,
        ]);

        $alertas = $torneo->validar();
        if (!empty($alertas)) {
            static::json(['ok' => false, 'alertas' => $alertas], 422);
        }

        $db = Torneo::conexion();

        try {
            $db->beginTransaction();

            $resultado = $torneo->guardar();
            if (is_array($resultado)) {
                $torneo->id = (int) $resultado['id'];
            }

            // Al crear el torneo, genera sus fases y grupos iniciales.
            if (!$actual) {
                static::generarEstructura($db, (int) $torneo->id, $estructura);
            }

            $db->commit();
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            static::responderErrorBD($e, 'No se pudo guardar el torneo.');
        }

        static::json(['ok' => true, 'data' => $torneo], $actual ? 200 : 201);
    }

    // POST: elimina torneo, fases, grupos y limpia los partidos asociados
    public static function eliminar(): void
    {
        static::soloPost();

        $input = static::leerInput();
        $id = (int) ($input['id'] ?? 0);

        $torneo = Torneo::find($id);
        if (!$torneo) {
            static::json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
        }

        $bloqueo = static::bloqueoEliminar($torneo);
        if ($bloqueo !== null) {
            static::json(['ok' => false, 'message' => $bloqueo], 409);
        }

        $db = Torneo::conexion();

        try {
            $db->beginTransaction();

            // Los equipos inscriptos y los grupos se borran con el torneo
            $db->prepare('DELETE FROM torneo_equipos WHERE id_torneo = ?')->execute([$id]);

            // Fases y grupos
            $fases = $db->prepare('SELECT id FROM fases WHERE id_torneo = ?');
            $fases->execute([$id]);
            $idsFases = $fases->fetchAll(\PDO::FETCH_COLUMN);

            if ($idsFases) {
                $marcas = implode(',', array_fill(0, count($idsFases), '?'));
                $db->prepare("DELETE FROM grupos WHERE id_fase IN ({$marcas})")->execute($idsFases);
                $db->prepare('DELETE FROM fases WHERE id_torneo = ?')->execute([$id]);
            }

            // Jugadores del plantel
            $db->prepare('DELETE FROM jugadores_equipo WHERE id_torneo = ?')->execute([$id]);

            // El torneo
            $torneo->eliminar();

            $db->commit();
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            static::responderErrorBD($e, 'No se pudo eliminar el torneo.');
        }

        static::json(['ok' => true]);
    }

    protected static function bloqueoEliminar(ActiveRecord $registro): ?string
    {
        $id = (int) $registro->id;

        $partidos = static::contar('partidos', 'id_torneo', $id);
        if ($partidos > 0) {
            return "Antes de eliminar el torneo, borrá los fixtures de todas sus fases con sus partidos ({$partidos} en total). Cada fixture requiere confirmación escribiendo ELIMINAR.";
        }

        return null;
    }

    // ---------- Auxiliares ----------

    /** Genera las fases y grupos de un torneo según su configuración */
    private static function generarEstructura(\PDO $db, int $idTorneo, array $estructura): void
    {
        // 1. Desasignar grupos a los equipos del torneo (quedan inscriptos pero sin grupo)
        $db->prepare('UPDATE torneo_equipos SET id_grupo = NULL WHERE id_torneo = ?')->execute([$idTorneo]);

        // 2. Borrar grupos y fases anteriores
        $stmt = $db->prepare('SELECT id FROM fases WHERE id_torneo = ?');
        $stmt->execute([$idTorneo]);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if ($ids) {
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM grupos WHERE id_fase IN ({$marcas})")->execute($ids);
            $db->prepare('DELETE FROM fases WHERE id_torneo = ?')->execute([$idTorneo]);
        }

        // 3. Crear las fases nuevas
        $insFase = $db->prepare(
            'INSERT INTO fases (id_torneo, nombre, tipo, ida_vuelta, orden, clasifican)
             VALUES (?, ?, ?, 0, ?, ?)'
        );

        $insGrupo = $db->prepare('INSERT INTO grupos (id_fase, nombre) VALUES (?, ?)');

        foreach ($estructura['fases'] as $f) {
            $insFase->execute([
                $idTorneo,
                $f['nombre'],
                $f['tipo'],
                $f['orden'],
                $f['clasifican'],
            ]);
            $idFase = (int) $db->lastInsertId();

            // Si es la fase de grupos, crear Grupo A, Grupo B...
            if ($f['tipo'] === 'grupos') {
                for ($g = 0; $g < $estructura['grupos']; $g++) {
                    $letra = chr(65 + $g); // A, B, C...
                    $insGrupo->execute([$idFase, "Grupo {$letra}"]);
                }
            }
        }

        // 4. torneos.ida_vuelta queda en 0 (solo ida por defecto)
        $db->prepare('UPDATE torneos SET ida_vuelta = 0 WHERE id = ?')->execute([$idTorneo]);
    }

    /** Clasifican guardado en la fase de grupos del torneo (o null si no tiene) */
    private static function clasificanActual(int $idTorneo): ?int
    {
        $db = Torneo::conexion();
        $stmt = $db->prepare("SELECT clasifican FROM fases WHERE id_torneo = ? AND tipo = 'grupos' LIMIT 1");
        $stmt->execute([$idTorneo]);
        $c = $stmt->fetchColumn();

        return $c === false || $c === null ? null : (int) $c;
    }

    private static function contar(string $tabla, string $columna, int $valor): int
    {
        $db = Torneo::conexion();
        $stmt = $db->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE `{$columna}` = ?");
        $stmt->execute([$valor]);
        return (int) $stmt->fetchColumn();
    }
}
