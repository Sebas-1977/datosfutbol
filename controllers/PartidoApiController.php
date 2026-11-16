<?php

declare(strict_types=1);

namespace Controllers;

use Model\Partido;
use Model\Torneo;
use Model\Fase;
use Model\Grupo;
use Model\Equipo;
use Model\Cancha;
use Model\Arbitro;

class PartidoApiController
{
    private const ESTADOS = ['programado', 'en_juego', 'finalizado', 'suspendido', 'cancelado'];

    private const CAMPOS_PERMITIDOS = [
        'id_torneo', 'id_fase', 'id_grupo', 'jornada', 'llave_orden',
        'id_equipo_local', 'id_equipo_visitante', 'id_cancha', 'id_arbitro',
        'fecha_hora', 'estado', 'goles_local', 'goles_visitante',
    ];

    // GET: /api/partidos?id_torneo=1&id_fase=2&id_grupo=3&jornada=1&estado=programado
    public static function listar(): void
    {
        $clausulas = [];
        $params    = [];

        foreach (['id_torneo', 'id_fase', 'id_grupo', 'jornada'] as $campo) {
            if (isset($_GET[$campo]) && $_GET[$campo] !== '') {
                $clausulas[] = "{$campo} = ?";
                $params[]    = (int) $_GET[$campo];
            }
        }

        if (!empty($_GET['estado']) && in_array($_GET['estado'], self::ESTADOS, true)) {
            $clausulas[] = "estado = ?";
            $params[]    = $_GET['estado'];
        }

        $sql = 'SELECT * FROM partidos';
        if ($clausulas) {
            $sql .= ' WHERE ' . implode(' AND ', $clausulas);
        }
        $sql .= ' ORDER BY jornada ASC, fecha_hora ASC, id ASC';

        $db   = Partido::conexion();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $objetos = array_map(fn(array $f) => Partido::find((int) $f['id']), $filas);

        self::json(['ok' => true, 'data' => array_values(array_filter($objetos))]);
    }

    // POST: crea (sin id) o actualiza (con id)
    public static function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::json(['ok' => false, 'message' => 'Método no permitido.'], 405);
        }

        $input = self::leerInput();
        $datos = array_intersect_key($input, array_flip(self::CAMPOS_PERMITIDOS));

        // datetime-local envía "2026-10-08T15:30"
        if (!empty($datos['fecha_hora'])) {
            $fecha = str_replace('T', ' ', (string) $datos['fecha_hora']);
            $datos['fecha_hora'] = strlen($fecha) === 16 ? $fecha . ':00' : $fecha;
        }

        $id = isset($input['id']) ? (int) $input['id'] : 0;

        if ($id > 0) {
            $partido = Partido::find($id);
            if (!$partido) {
                self::json(['ok' => false, 'message' => 'Partido no encontrado.'], 404);
            }
        } else {
            $partido = new Partido();
            $partido->created_at = date('Y-m-d H:i:s');
        }

        $estadoAnterior = $id > 0 ? $partido->estado : null;
        $partido->sincronizar($datos);
        $partido->updated_at = date('Y-m-d H:i:s');

        $partido->validar(); // reinicia las alertas, por eso va primero

        if (!in_array($partido->estado, self::ESTADOS, true)) {
            Partido::setAlerta('error', 'Estado inválido.');
        }

        self::validarCoherencia($partido);
        self::validarDuplicado($partido);
        self::validarEquipoUnicoEnJornada($partido);
        self::validarJornada($partido);            
        $partido->validarTitulares($estadoAnterior);

        $alertas = Partido::getAlertas();

        if (!empty($alertas)) {
            self::json(['ok' => false, 'alertas' => $alertas], 422);
        }

        $resultado = $partido->guardar();

        if (is_array($resultado)) { // crear()
            $partido->id = (int) $resultado['id'];
        }

        self::json(['ok' => true, 'data' => $partido], $id > 0 ? 200 : 201);
    }

    // POST: elimina por id
    public static function eliminar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::json(['ok' => false, 'message' => 'Método no permitido.'], 405);
        }

        $input   = self::leerInput();
        $partido = Partido::find((int) ($input['id'] ?? 0));

        if (!$partido) {
            self::json(['ok' => false, 'message' => 'Partido no encontrado.'], 404);
        }

        $partido->eliminar();
        self::json(['ok' => true]);
    }

    // Acepta JSON (fetch) o form-data (FormData / $.ajax)
    private static function leerInput(): array
    {
        $tipo = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($tipo, 'application/json')) {
            $json = json_decode((string) file_get_contents('php://input'), true);
            return is_array($json) ? $json : [];
        }

        return $_POST;
    }

    private static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function validarCoherencia(Partido $p): void
    {
        if ($p->id_torneo && !Torneo::find($p->id_torneo)) {
            Partido::setAlerta('error', 'El torneo no existe.');
        }

        if ($p->id_fase) {
            $fase = Fase::find($p->id_fase);
            if (!$fase) {
                Partido::setAlerta('error', 'La fase no existe.');
            } elseif ($p->id_torneo && $fase->id_torneo !== $p->id_torneo) {
                Partido::setAlerta('error', 'La fase no pertenece al torneo seleccionado.');
            }
        }

        if ($p->id_grupo) {
            $grupo = Grupo::find($p->id_grupo);
            if (!$grupo) {
                Partido::setAlerta('error', 'El grupo no existe.');
            } elseif ($p->id_fase && $grupo->id_fase !== $p->id_fase) {
                Partido::setAlerta('error', 'El grupo no pertenece a la fase seleccionada.');
            }
        }

        if ($p->id_equipo_local && !Equipo::find($p->id_equipo_local)) {
            Partido::setAlerta('error', 'El equipo local no existe.');
        }

        if ($p->id_equipo_visitante && !Equipo::find($p->id_equipo_visitante)) {
            Partido::setAlerta('error', 'El equipo visitante no existe.');
        }

        if ($p->id_cancha && !Cancha::find($p->id_cancha)) {
            Partido::setAlerta('error', 'La cancha no existe.');
        }

        if ($p->id_arbitro && !Arbitro::find($p->id_arbitro)) {
            Partido::setAlerta('error', 'El árbitro no existe.');
        }
    }

    private static function validarDuplicado(Partido $p): void
    {
        // Solo se revisa si las validaciones anteriores pasaron
        if (!empty(Partido::getAlertas())) {
            return;
        }

        $torneo = Torneo::find((int) $p->id_torneo);
        $local  = (int) $p->id_equipo_local;
        $visit  = (int) $p->id_equipo_visitante;
        $idP    = (int) ($p->id ?? 0);
        $idT    = (int) $p->id_torneo;

        $db = Partido::conexion();

        if ($torneo && $torneo->ida_vuelta === 1) {
            // Ida y vuelta: solo se bloquea repetir el mismo local contra el mismo visitante
            $stmt = $db->prepare(
                'SELECT 1 FROM partidos
                 WHERE id_torneo = ? AND id <> ?
                   AND id_equipo_local = ? AND id_equipo_visitante = ?
                 LIMIT 1'
            );
            $stmt->execute([$idT, $idP, $local, $visit]);
            $mensaje = 'Este partido ya existe en el torneo (mismo local y visitante).';
        } else {
            // Solo ida: se bloquea el cruce en cualquier orden
            $stmt = $db->prepare(
                'SELECT 1 FROM partidos
                 WHERE id_torneo = ? AND id <> ?
                   AND ((id_equipo_local = ? AND id_equipo_visitante = ?)
                     OR (id_equipo_local = ? AND id_equipo_visitante = ?))
                 LIMIT 1'
            );
            $stmt->execute([$idT, $idP, $local, $visit, $visit, $local]);
            $mensaje = 'Estos equipos ya se enfrentan en este torneo (torneo de solo ida).';
        }

        if ($stmt->fetch()) {
            Partido::setAlerta('error', $mensaje);
        }
        $stmt->closeCursor();
    }

        /**
     * Dentro de una misma fase y jornada, un equipo no puede jugar más de un partido
     * (ni de local ni de visitante). Al editar se excluye el propio partido.
     * Los partidos cancelados no cuentan, para poder reprogramarlos.
     */
    private static function validarEquipoUnicoEnJornada(Partido $p): void
    {
        // Solo se revisa si las validaciones anteriores pasaron
        if (!empty(Partido::getAlertas())) {
            return;
        }

        if (!$p->jornada || $p->estado === 'cancelado') {
            return;
        }

        $local = (int) $p->id_equipo_local;
        $visit = (int) $p->id_equipo_visitante;

        $stmt = Partido::conexion()->prepare(
            "SELECT p.id_equipo_local, p.id_equipo_visitante,
                    el.nombre AS local, ev.nombre AS visitante
             FROM partidos p
             INNER JOIN equipos el ON el.id = p.id_equipo_local
             INNER JOIN equipos ev ON ev.id = p.id_equipo_visitante
             WHERE p.id_torneo = ? AND p.id_fase = ? AND p.jornada = ?
               AND p.id <> ? AND p.estado <> 'cancelado'
               AND (p.id_equipo_local IN (?, ?) OR p.id_equipo_visitante IN (?, ?))"
        );
        $stmt->execute([
            (int) $p->id_torneo, (int) $p->id_fase, (int) $p->jornada,
            (int) ($p->id ?? 0),
            $local, $visit, $local, $visit,
        ]);
        $choques = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $avisados = [];
        foreach ($choques as $c) {
            $nombres = [
                (int) $c['id_equipo_local']     => $c['local'],
                (int) $c['id_equipo_visitante'] => $c['visitante'],
            ];
            foreach ([$local, $visit] as $idEquipo) {
                if (isset($nombres[$idEquipo]) && !isset($avisados[$idEquipo])) {
                    $avisados[$idEquipo] = true;
                    Partido::setAlerta(
                        'error',
                        "{$nombres[$idEquipo]} ya juega en la jornada {$p->jornada} de esta fase ({$c['local']} vs {$c['visitante']})."
                    );
                }
            }
        }
    }

    // En PartidoApiController
    private static function totalJornadas(Torneo $t): int
    {
        $n     = (int) $t->cantidad_equipos;
        $ronda = ($n % 2 === 0) ? $n - 1 : $n;       // jornadas de una vuelta
        return $ronda * ((int) $t->ida_vuelta === 1 ? 2 : 1);
    }

    private static function validarJornada(Partido $p): void
    {
        if (!empty(Partido::getAlertas())) {
            return;
        }

        if ($p->jornada !== null && $p->jornada < 1) {
            Partido::setAlerta('error', 'La jornada debe ser 1 o mayor.');
            return;
        }

        if ($p->jornada === null) {
            return;
        }

        $fase = Fase::find((int) $p->id_fase);
        if (!$fase || $fase->tipo !== 'liga') {
            return; // grupos y eliminatorias tienen otra lógica
        }

        $torneo = Torneo::find((int) $p->id_torneo);
        if ($torneo && $p->jornada > self::totalJornadas($torneo)) {
            Partido::setAlerta('error', 'La jornada máxima de este torneo es ' . self::totalJornadas($torneo) . '.');
        }
    }
}
