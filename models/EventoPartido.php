<?php

declare(strict_types=1);

namespace Model;

class EventoPartido extends ActiveRecord
{
    public const TIPOS = [
        'gol', 'gol_penal', 'autogol', 'penal_fallado',
        'tarjeta_amarilla', 'doble_amarilla', 'tarjeta_roja', 'cambio',
    ];
    public const EXPULSIONES = ['doble_amarilla', 'tarjeta_roja'];
    public const TARJETAS = ['tarjeta_amarilla', 'doble_amarilla', 'tarjeta_roja'];
    public const MINUTOS_EXTRA = [45, 90, 105, 120];
    public const MINUTO_MAX = 150;
    public const GOLES = ['gol', 'gol_penal', 'autogol'];

    protected static string $tabla = 'eventos_partido';

    protected static array $columnasDB = [
        'id', 'id_partido', 'id_equipo', 'id_jugador', 'id_jugador_entra',
        'id_asistente', 'tipo', 'minuto', 'minuto_extra', 'detalle',
    ];

    public ?int $id_partido = null;
    public ?int $id_equipo = null;
    public ?int $id_jugador = null;
    public ?int $id_jugador_entra = null;
    public ?int $id_asistente = null;
    public string $tipo = 'gol';
    public ?int $minuto = null;
    public ?int $minuto_extra = null;
    public ?string $detalle = null;

    public function validar(): array
    {
        parent::validar();

        $partido = null;

        if (!$this->id_partido) {
            static::setAlerta('error', 'El partido es obligatorio.');
        } else {
            $partido = Partido::find($this->id_partido);
            if (!$partido) {
                static::setAlerta('error', 'El partido no existe.');
            }
        }

        if (!$this->id_equipo) {
            static::setAlerta('error', 'El equipo es obligatorio.');
        } elseif ($partido && !in_array($this->id_equipo, [$partido->id_equipo_local, $partido->id_equipo_visitante], true)) {
            static::setAlerta('error', 'Ese equipo no juega este partido.');
        }

        if (!$this->id_jugador) {
            static::setAlerta('error', 'El jugador es obligatorio.');
        }

        if (!in_array($this->tipo, self::TIPOS, true)) {
            static::setAlerta('error', 'El tipo de evento no es válido.');
        }

        if ($this->minuto === null || $this->minuto < 0 || $this->minuto > self::MINUTO_MAX) {
            static::setAlerta('error', 'El minuto no es válido (0 a ' . self::MINUTO_MAX . ').');
        } elseif ($this->minuto_extra !== null) {
            if (!in_array($this->minuto, self::MINUTOS_EXTRA, true)) {
                static::setAlerta('error', 'El minuto extra solo aplica a los minutos 45, 90, 105 y 120.');
            } elseif ($this->minuto_extra < 1) {
                static::setAlerta('error', 'El minuto extra debe ser mayor que 0.');
            }
        }

        // Reglas que consultan la BD (solo si lo anterior está bien)
        if (empty(static::getAlertas()) && $partido) {
            $this->validarReglas();
        }

        return static::getAlertas();
    }

    private function validarReglas(): void
    {
        $t = $this->tiempo();

        if ($this->tipo === 'cambio') {
            $this->validarCambio($t);
            return;
        }

        if ($this->id_jugador_entra !== null) {
            static::setAlerta('error', 'Solo los cambios llevan jugador que entra.');
        }

        $motivo = in_array($this->tipo, self::TARJETAS, true)
            ? $this->motivoNoSancionable((int) $this->id_jugador, $t)
            : $this->motivoFueraDeCancha((int) $this->id_jugador, $t);

        if ($motivo) {
            static::setAlerta('error', $motivo);
            return;
        }

        if ($this->id_asistente !== null) {
            if ($this->tipo !== 'gol') {
                static::setAlerta('error', 'Solo un gol normal puede tener asistente.');
            } elseif ($this->id_asistente === $this->id_jugador) {
                static::setAlerta('error', 'El asistente no puede ser el mismo goleador.');
            } elseif ($motivo = $this->motivoFueraDeCancha((int) $this->id_asistente, $t)) {
                static::setAlerta('error', 'Asistente: ' . $motivo);
            }
        }

        if ($this->tipo === 'tarjeta_amarilla') {
            if ($this->existeEvento("id_jugador = {$this->idJ()} AND tipo IN ('tarjeta_amarilla','doble_amarilla') AND {$this->hastaT($t)}")) {
                static::setAlerta('error', 'El jugador ya tiene amarilla. La segunda se registra como doble amarilla.');
            }
        }

        if ($this->tipo === 'doble_amarilla') {
            if (!$this->existeEvento("id_jugador = {$this->idJ()} AND tipo = 'tarjeta_amarilla' AND {$this->hastaT($t)}")) {
                static::setAlerta('error', 'La doble amarilla requiere una amarilla previa del jugador.');
            }
        }
    }

    private function validarCambio(int $t): void
    {
        $entra = (int) $this->id_jugador_entra;

        if ($this->id_asistente !== null) {
            static::setAlerta('error', 'Un cambio no lleva asistente.');
        }

        if (!$entra) {
            static::setAlerta('error', 'El jugador que entra es obligatorio.');
            return;
        }
        if ($entra === (int) $this->id_jugador) {
            static::setAlerta('error', 'El jugador que entra debe ser distinto del que sale.');
            return;
        }

        // El que sale debe estar en cancha
        if ($motivo = $this->motivoFueraDeCancha((int) $this->id_jugador, $t)) {
            static::setAlerta('error', 'Sale: ' . $motivo);
        }

        // El que entra debe ser suplente del mismo equipo que todavía no entró
        $fila = $this->filaAlineacion($entra);
        if (!$fila) {
            static::setAlerta('error', 'Entra: el jugador no está en la alineación de este equipo en el partido.');
        } elseif ($fila->condicion !== 'suplente') {
            static::setAlerta('error', 'Entra: el jugador es titular, solo un suplente puede entrar.');
        } elseif ($this->existeEvento("tipo = 'cambio' AND id_jugador_entra = {$entra}")) {
            static::setAlerta('error', 'Entra: ese suplente ya ingresó en otro cambio.');
        }

        if ($fila && $fila->condicion === 'suplente') {
            $exp = "'" . implode("','", self::EXPULSIONES) . "'";
            if ($this->existeEvento("id_jugador = {$entra} AND tipo IN ({$exp}) AND {$this->hastaT($t)}")) {
                static::setAlerta('error', 'Entra: el jugador ya fue expulsado y no puede ingresar.');
            }
        }
    }

    /** Devuelve el motivo por el que el jugador no está en cancha en el instante $t, o null si está. */
    private function motivoFueraDeCancha(int $idJugador, int $t): ?string
    {
        $fila = $this->filaAlineacion($idJugador);
        if (!$fila) {
            return 'El jugador no está en la alineación de este equipo en el partido.';
        }

        $exp = "'" . implode("','", self::EXPULSIONES) . "'";
        if ($this->existeEvento("id_jugador = {$idJugador} AND tipo IN ({$exp}) AND {$this->hastaT($t)}")) {
            return 'El jugador ya fue expulsado.';
        }
        if ($this->existeEvento("id_jugador = {$idJugador} AND tipo = 'cambio' AND {$this->hastaT($t)}")) {
            return 'El jugador ya salió del campo.';
        }
        if ($fila->condicion === 'suplente'
            && !$this->existeEvento("id_jugador_entra = {$idJugador} AND tipo = 'cambio' AND {$this->hastaT($t)}")) {
            return 'El jugador es suplente y todavía no entró.';
        }

        return null;
    }

    /**
     * Las tarjetas pueden ir a cualquier convocado (titular, suplente que entró,
     * suplente que no entró o jugador ya sustituido), salvo que ya esté expulsado.
     */
    private function motivoNoSancionable(int $idJugador, int $t): ?string
    {
        if (!$this->filaAlineacion($idJugador)) {
            return 'El jugador no está en la alineación de este equipo en el partido.';
        }

        $exp = "'" . implode("','", self::EXPULSIONES) . "'";
        if ($this->existeEvento("id_jugador = {$idJugador} AND tipo IN ({$exp}) AND {$this->hastaT($t)}")) {
            return 'El jugador ya fue expulsado.';
        }

        return null;
    }

    private function filaAlineacion(int $idJugador): ?PartidoJugador
    {
        $filas = PartidoJugador::SQL(
            'SELECT * FROM partido_jugadores'
            . ' WHERE id_partido = ' . (int) $this->id_partido
            . ' AND id_equipo = ' . (int) $this->id_equipo
            . " AND id_jugador = {$idJugador} LIMIT 1"
        );
        return $filas[0] ?? null;
    }

    /** Busca otros eventos del mismo partido (excluye este mismo al editar). */
    private function existeEvento(string $condicion): bool
    {
        $filas = static::SQL(
            'SELECT * FROM eventos_partido WHERE id_partido = ' . (int) $this->id_partido
            . ' AND id <> ' . (int) ($this->id ?? 0)
            . " AND {$condicion} LIMIT 1"
        );
        return !empty($filas);
    }

    private function tiempo(): int
    {
        return (int) $this->minuto * 1000 + (int) ($this->minuto_extra ?? 0);
    }

    private function hastaT(int $t): string
    {
        return "(minuto * 1000 + IFNULL(minuto_extra, 0)) <= {$t}";
    }

    private function idJ(): int
    {
        return (int) $this->id_jugador;
    }

        /**
     * Recalcula goles_local / goles_visitante a partir de los eventos de gol.
     * Si no hay goles cargados y no se fuerza, respeta el marcador manual.
     */
    public static function recalcularMarcador(int $idPartido, bool $forzar = false): void
    {
        $partido = Partido::find($idPartido);
        if (!$partido) {
            return;
        }

        $goles = static::SQL(
            'SELECT * FROM eventos_partido WHERE id_partido = ' . $idPartido
            . " AND tipo IN ('gol','gol_penal','autogol')"
        );

        if (!$goles && !$forzar) {
            return;
        }

        $local = 0;
        $visitante = 0;
        foreach ($goles as $g) {
            $delLocal = (int) $g->id_equipo === (int) $partido->id_equipo_local;
            // El autogol suma al rival de quien lo comete
            if ($g->tipo === 'autogol') {
                $delLocal = !$delLocal;
            }
            $delLocal ? $local++ : $visitante++;
        }

        $partido->goles_local = $local;
        $partido->goles_visitante = $visitante;
        $partido->guardar();
    }

    /** Motivo por el que este evento no se puede borrar, o null si se puede. */
    public function motivoNoBorrar(): ?string
    {
        $j = (int) $this->id_jugador;

        if ($this->tipo === 'tarjeta_amarilla'
            && $this->existeEvento("id_jugador = {$j} AND tipo = 'doble_amarilla'")) {
            return 'Primero borrá la doble amarilla de este jugador.';
        }

        if ($this->tipo === 'cambio') {
            $e = (int) $this->id_jugador_entra;
            $t = $this->tiempo();
            // Solo bloquean los eventos del que entró desde su ingreso en adelante
            if ($this->existeEvento(
                "(id_jugador = {$e} OR id_asistente = {$e}) AND (minuto * 1000 + IFNULL(minuto_extra, 0)) >= {$t}"
            )) {
                return 'El jugador que entró ya tiene eventos posteriores. Borralos primero.';
            }
        }

        return null;
    }
}