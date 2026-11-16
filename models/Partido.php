<?php

declare(strict_types=1);

namespace Model;

class Partido extends ActiveRecord
{
    protected static string $tabla = 'partidos';

    protected static array $columnasDB = [
        'id', 'id_torneo', 'id_fase', 'id_grupo', 'jornada', 'llave_orden',
        'id_equipo_local', 'id_equipo_visitante', 'id_cancha', 'id_arbitro',
        'fecha_hora', 'estado', 'goles_local', 'goles_visitante',
        'created_at', 'updated_at',
    ];

    public ?int $id_torneo = null;
    public ?int $id_fase = null;
    public ?int $id_grupo = null;
    public ?int $jornada = 1;
    public ?int $llave_orden = null;
    public ?int $id_equipo_local = null;
    public ?int $id_equipo_visitante = null;
    public ?int $id_cancha = null;
    public ?int $id_arbitro = null;
    public ?string $fecha_hora = null;
    public string $estado = 'programado';
    public int $goles_local = 0;
    public int $goles_visitante = 0;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function validar(): array
    {
        parent::validar();

        if (!$this->id_torneo) {
            static::setAlerta('error', 'El torneo es obligatorio.');
        }

        if (!$this->id_fase) {
            static::setAlerta('error', 'La fase es obligatoria.');
        }

        if (!$this->id_equipo_local || !$this->id_equipo_visitante) {
            static::setAlerta('error', 'Debes elegir ambos equipos.');
        } elseif ($this->id_equipo_local === $this->id_equipo_visitante) {
            static::setAlerta('error', 'Un equipo no puede jugar contra sí mismo.');
        }

        return static::getAlertas();
    }

        /**
     * Exige 11 titulares por equipo al pasar a en_juego o finalizado.
     * Solo se evalúa cuando el estado cambia, así se pueden seguir corrigiendo
     * cancha, árbitro o fecha de un partido ya finalizado.
     */
    public function validarTitulares(?string $estadoAnterior): void
    {
        if (!empty(static::getAlertas())) {
            return;
        }

        if (!in_array($this->estado, ['en_juego', 'finalizado'], true) || $this->estado === $estadoAnterior) {
            return;
        }

        $requeridos  = PartidoJugador::MAX_TITULARES;
        $estadoTexto = $this->estado === 'en_juego' ? 'en juego' : 'finalizado';

        foreach ([$this->id_equipo_local, $this->id_equipo_visitante] as $idEquipo) {
            $cantidad = count(PartidoJugador::SQL(
                'SELECT * FROM partido_jugadores'
                . ' WHERE id_partido = ' . (int) ($this->id ?? 0)
                . ' AND id_equipo = ' . (int) $idEquipo
                . " AND condicion = 'titular'"
            ));

            if ($cantidad < $requeridos) {
                $nombre = Equipo::find((int) $idEquipo)->nombre ?? 'El equipo';
                static::setAlerta(
                    'error',
                    "{$nombre} tiene {$cantidad} titulares; necesita {$requeridos}. "
                    . "Carga la alineación (botón «Alineación») antes de marcar el partido como {$estadoTexto}."
                );
            }
        }
    }

        /**
     * Últimos partidos finalizados, para la portada pública.
     * Devuelve arreglos con las claves que espera la vista.
     */
    public static function resultadosRecientes(int $limite = 10): array
    {
        return static::consultaPublica(
            "p.estado = 'finalizado' AND p.fecha_hora IS NOT NULL",
            'p.fecha_hora DESC, p.id DESC',
            $limite
        );
    }

    /**
     * Próximos partidos programados (desde hoy), el más cercano primero.
     */
    public static function proximosPartidos(int $limite = 6): array
    {
        return static::consultaPublica(
            "p.estado = 'programado' AND p.fecha_hora IS NOT NULL AND p.fecha_hora >= CURDATE()",
            'p.fecha_hora ASC, p.id ASC',
            $limite
        );
    }

    private static function consultaPublica(string $where, string $orden, int $limite): array
    {
        $sql = 'SELECT p.id AS id_partido, p.fecha_hora, p.estado, p.jornada,'
            . ' el.nombre AS local, ev.nombre AS visitante,'
            . ' el.escudo AS escudo_local, ev.escudo AS escudo_visitante,'
            . ' p.id_equipo_local, p.id_equipo_visitante,'
            . ' p.goles_local, p.goles_visitante, t.nombre AS torneo'
            . ' FROM partidos p'
            . ' INNER JOIN equipos el ON el.id = p.id_equipo_local'
            . ' INNER JOIN equipos ev ON ev.id = p.id_equipo_visitante'
            . ' INNER JOIN torneos t ON t.id = p.id_torneo'
            . " WHERE t.publico = 1 AND {$where}"
            . " ORDER BY {$orden}"
            . ' LIMIT ' . max(1, $limite);

        $resultado = static::$db->query($sql);

        return $resultado ? $resultado->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

        private static function filas(string $sql, array $params = []): array
    {
        $st = static::$db->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Torneos que tienen al menos un partido, el más nuevo primero. */
    public static function torneosConPartidos(): array
    {
        return static::filas(
            'SELECT t.id, t.nombre FROM torneos t'
            . ' WHERE t.publico = 1'
            . ' AND EXISTS (SELECT 1 FROM partidos p WHERE p.id_torneo = t.id)'
            . ' ORDER BY t.id DESC'
        );
    }

    /** Números de jornada que ya tienen partidos en el torneo, en orden ascendente. */
    public static function jornadasDeTorneo(int $idTorneo): array
    {
        $filas = static::filas(
            'SELECT DISTINCT jornada FROM partidos'
            . ' WHERE id_torneo = ? AND jornada IS NOT NULL'
            . ' ORDER BY jornada ASC',
            [$idTorneo]
        );

        return array_map('intval', array_column($filas, 'jornada'));
    }

    // /** Jornadas del torneo con su cantidad de partidos, incluidas las que aún están vacías. */
    // public static function jornadasConConteo(int $idTorneo, ?int $idFase = null): array
    // {
    //     $sql    = 'SELECT jornada, COUNT(*) AS total FROM partidos'
    //             . ' WHERE id_torneo = ? AND jornada IS NOT NULL';
    //     $params = [$idTorneo];

    //     if ($idFase) {
    //         $sql    .= ' AND id_fase = ?';
    //         $params[] = $idFase;
    //     }

    //     $filas = static::filas($sql . ' GROUP BY jornada ORDER BY jornada', $params);

    //     $conteo = [];
    //     foreach ($filas as $f) {
    //         $conteo[(int) $f['jornada']] = (int) $f['total'];
    //     }

    //     // Total teórico solo para fases de liga
    //     $maximo = 0;
    //     $torneo = Torneo::find($idTorneo);
    //     if ($torneo && $torneo->formato === 'liga') {
    //         $n      = (int) $torneo->cantidad_equipos;
    //         $ronda  = ($n % 2 === 0) ? $n - 1 : $n;
    //         $maximo = $ronda * ((int) $torneo->ida_vuelta === 1 ? 2 : 1);
    //     }

    //     $maximo = max($maximo, $conteo ? max(array_keys($conteo)) : 0);

    //     $resultado = [];
    //     for ($j = 1; $j <= $maximo; $j++) {
    //         $resultado[$j] = $conteo[$j] ?? 0;
    //     }

    //     return $resultado; // [jornada => cantidad de partidos]
    // }

    public static function partidosDeJornada(int $idTorneo, int $jornada): array
    {
        return static::filas(
            'SELECT p.id AS id_partido, p.fecha_hora, p.estado,'
            . ' el.nombre AS local, ev.nombre AS visitante,'
            . ' el.escudo AS escudo_local, ev.escudo AS escudo_visitante,'
            . ' p.id_equipo_local, p.id_equipo_visitante,'
            . ' p.goles_local, p.goles_visitante'
            . ' FROM partidos p'
            . ' INNER JOIN equipos el ON el.id = p.id_equipo_local'
            . ' INNER JOIN equipos ev ON ev.id = p.id_equipo_visitante'
            . ' WHERE p.id_torneo = ? AND p.jornada = ?'
            . ' ORDER BY p.fecha_hora IS NULL, p.fecha_hora ASC, p.id ASC',
            [$idTorneo, $jornada]
        );
    }

    /** Jornada del próximo partido; si no hay, la del último jugado; si no, la primera. */
    public static function jornadaActual(int $idTorneo): ?int
    {
        $f = static::filas(
            "SELECT jornada FROM partidos WHERE id_torneo = ? AND estado = 'programado'"
            . ' AND fecha_hora >= CURDATE() ORDER BY fecha_hora ASC LIMIT 1',
            [$idTorneo]
        ) ?: static::filas(
            "SELECT jornada FROM partidos WHERE id_torneo = ? AND estado = 'finalizado'"
            . ' ORDER BY fecha_hora DESC LIMIT 1',
            [$idTorneo]
        ) ?: static::filas(
            'SELECT MIN(jornada) AS jornada FROM partidos'
            . ' WHERE id_torneo = ? AND jornada IS NOT NULL',
            [$idTorneo]
        );

        return ($f && $f[0]['jornada'] !== null) ? (int) $f[0]['jornada'] : null;
    }

    public static function conexion(): \PDO
    {
        return static::$db;
    }
}