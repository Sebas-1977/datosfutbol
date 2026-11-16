<?php

declare(strict_types=1);

namespace Model;

class JugadorEquipo extends ActiveRecord
{
    public const POSICIONES = ['PT', 'DEF', 'MED', 'DEL'];

    protected static string $tabla = 'jugadores_equipo';

    protected static array $columnasDB = [
        'id', 'id_torneo', 'id_equipo', 'id_jugador', 'numero_camiseta', 'posicion',
    ];

    public ?int $id_torneo = null;
    public ?int $id_equipo = null;
    public ?int $id_jugador = null;
    public ?int $numero_camiseta = null;
    public ?string $posicion = null;

    public function validar(): array
    {
        parent::validar();

        if (!$this->id_torneo) {
            static::setAlerta('error', 'El torneo es obligatorio.');
        } elseif (!Torneo::find($this->id_torneo)) {
            static::setAlerta('error', 'El torneo no existe.');
        }

        if (!$this->id_equipo) {
            static::setAlerta('error', 'El equipo es obligatorio.');
        } elseif (!Equipo::find($this->id_equipo)) {
            static::setAlerta('error', 'El equipo no existe.');
        }

        if (!$this->id_jugador) {
            static::setAlerta('error', 'El jugador es obligatorio.');
        } elseif (!Jugador::find($this->id_jugador)) {
            static::setAlerta('error', 'El jugador no existe.');
        }

        if ($this->numero_camiseta !== null && ($this->numero_camiseta < 1 || $this->numero_camiseta > 99)) {
            static::setAlerta('error', 'El número de camiseta debe estar entre 1 y 99.');
        }

        if ($this->posicion !== null && !in_array($this->posicion, self::POSICIONES, true)) {
            static::setAlerta('error', 'La posición no es válida.');
        }

        // Duplicados (solo si lo anterior está bien)
        if (empty(static::getAlertas())) {
            $idPropio = (int) ($this->id ?? 0);
            $torneo = (int) $this->id_torneo;

            $yaEnTorneo = static::SQL(
                "SELECT * FROM jugadores_equipo WHERE id_torneo = {$torneo}"
                . ' AND id_jugador = ' . (int) $this->id_jugador
                . " AND id <> {$idPropio}"
            );
            if ($yaEnTorneo) {
                static::setAlerta('error', 'Este jugador ya pertenece a un equipo en este torneo.');
            }

            if ($this->numero_camiseta !== null) {
                $camisetaRepetida = static::SQL(
                    "SELECT * FROM jugadores_equipo WHERE id_torneo = {$torneo}"
                    . ' AND id_equipo = ' . (int) $this->id_equipo
                    . ' AND numero_camiseta = ' . (int) $this->numero_camiseta
                    . " AND id <> {$idPropio}"
                );
                if ($camisetaRepetida) {
                    static::setAlerta('error', "La camiseta {$this->numero_camiseta} ya está asignada en este equipo.");
                }
            }
        }

        return static::getAlertas();
    }
}