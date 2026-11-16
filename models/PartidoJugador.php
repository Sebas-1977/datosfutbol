<?php

declare(strict_types=1);

namespace Model;

class PartidoJugador extends ActiveRecord
{
    public const CONDICIONES = ['titular', 'suplente'];
    public const MAX_TITULARES = 11;

    public const PUESTOS = [
        'PT',
        'LI', 'DCI', 'DFC', 'DCD', 'LD',
        'MI', 'MCI', 'MC', 'MCD', 'MD',
        'MPI', 'MP', 'MPD',
        'EI', 'DI', 'DC', 'DD', 'ED',
    ];

    protected static string $tabla = 'partido_jugadores';

    protected static array $columnasDB = [
        'id', 'id_partido', 'id_equipo', 'id_jugador', 'condicion', 'posicion_campo',
    ];

    public ?int $id_partido = null;
    public ?int $id_equipo = null;
    public ?int $id_jugador = null;
    public string $condicion = 'suplente';
    public ?string $posicion_campo = null;

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

        if (!in_array($this->condicion, self::CONDICIONES, true)) {
            static::setAlerta('error', 'La condición no es válida.');
        }

        // El puesto en la cancha es opcional y solo lo llevan los titulares
        if ($this->posicion_campo === '') {
            $this->posicion_campo = null;
        }
        if ($this->condicion !== 'titular') {
            $this->posicion_campo = null; // al pasar a suplente se limpia solo
        } elseif ($this->posicion_campo !== null && !in_array($this->posicion_campo, self::PUESTOS, true)) {
            static::setAlerta('error', 'El puesto en la cancha no es válido.');
        }

        // Reglas que consultan la BD (solo si lo anterior está bien)
        if (empty(static::getAlertas()) && $partido) {
            $idPropio = (int) ($this->id ?? 0);
            $idPartido = (int) $this->id_partido;
            $idEquipo = (int) $this->id_equipo;
            $idJugador = (int) $this->id_jugador;

            // El jugador debe estar en el plantel del equipo en ese torneo
            $enPlantel = JugadorEquipo::SQL(
                'SELECT * FROM jugadores_equipo'
                . ' WHERE id_torneo = ' . (int) $partido->id_torneo
                . " AND id_equipo = {$idEquipo} AND id_jugador = {$idJugador}"
            );
            if (!$enPlantel) {
                static::setAlerta('error', 'El jugador no pertenece al plantel de este equipo en el torneo.');
            }

            // Una sola vez por partido
            $repetido = static::SQL(
                "SELECT * FROM partido_jugadores WHERE id_partido = {$idPartido}"
                . " AND id_jugador = {$idJugador} AND id <> {$idPropio}"
            );
            if ($repetido) {
                static::setAlerta('error', 'Este jugador ya está en la alineación del partido.');
            }

            // Máximo de titulares por equipo
            if ($this->condicion === 'titular') {
                $titulares = static::SQL(
                    "SELECT * FROM partido_jugadores WHERE id_partido = {$idPartido}"
                    . " AND id_equipo = {$idEquipo} AND condicion = 'titular' AND id <> {$idPropio}"
                );
                if (count($titulares) >= self::MAX_TITULARES) {
                    static::setAlerta('error', 'Ya hay ' . self::MAX_TITULARES . ' titulares en este equipo.');
                }
            }
        }

        return static::getAlertas();
    }
}