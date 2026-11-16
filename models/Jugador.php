<?php

declare(strict_types=1);

namespace Model;

class Jugador extends ActiveRecord
{
    protected static string $tabla = 'jugadores';

    protected static array $columnasDB = [
        'id', 'nombre', 'apellido', 'seudonimo', 'pais', 'fecha_nacimiento', 'foto',
    ];

    public string $nombre = '';
    public string $apellido = '';
    public ?string $seudonimo = null;
    public ?string $pais = null;
    public ?string $fecha_nacimiento = null;
    public ?string $foto = null;

    public function validar(): array
    {
        parent::validar();

        $this->nombre = trim($this->nombre);
        $this->apellido = trim($this->apellido);
        if (is_string($this->pais)) {
            $pais = trim($this->pais);
            $this->pais = $pais === '' ? null : $pais;
        }

        foreach (['nombre' => 'El nombre', 'apellido' => 'El apellido'] as $campo => $texto) {
            if ($this->$campo === '') {
                static::setAlerta('error', "{$texto} es obligatorio.");
            } elseif (mb_strlen($this->$campo) > 100) {
                static::setAlerta('error', "{$texto} no puede superar los 100 caracteres.");
            }
        }

        if (is_string($this->seudonimo)) {
            $seudonimo = trim($this->seudonimo);
            $this->seudonimo = $seudonimo === '' ? null : $seudonimo;
        }

        if ($this->seudonimo !== null && mb_strlen($this->seudonimo) > 100) {
            static::setAlerta('error', 'El seudónimo no puede superar los 100 caracteres.');
        }

        if ($this->pais !== null && mb_strlen($this->pais) > 100) {
            static::setAlerta('error', 'El país no puede superar los 100 caracteres.');
        }

        if (is_string($this->fecha_nacimiento)) {
            $fecha = trim($this->fecha_nacimiento);
            $this->fecha_nacimiento = $fecha === '' ? null : $fecha;
        }

        if ($this->fecha_nacimiento !== null) {
            $fecha = \DateTime::createFromFormat('Y-m-d', $this->fecha_nacimiento);

            if (!$fecha || $fecha->format('Y-m-d') !== $this->fecha_nacimiento) {
                static::setAlerta('error', 'La fecha de nacimiento no es válida.');
            } elseif ($fecha > new \DateTime('today')) {
                static::setAlerta('error', 'La fecha de nacimiento no puede ser futura.');
            }
        }

        return static::getAlertas();
    }
}