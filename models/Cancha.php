<?php

declare(strict_types=1);

namespace Model;

class Cancha extends ActiveRecord
{
    public const TIPOS = ['club', 'estatal', 'privada'];

    protected static string $tabla = 'canchas';

    protected static array $columnasDB = [
        'id', 'nombre', 'tipo', 'id_equipo_propietario',
        'capacidad', 'direccion', 'ciudad', 'foto',
    ];

    public string $nombre = '';
    public string $tipo = 'privada';
    public ?int $id_equipo_propietario = null;
    public ?int $capacidad = null;
    public ?string $direccion = null;
    public ?string $ciudad = null;
    public ?string $foto = null;

    public function validar(): array
    {
        parent::validar();

        $this->nombre = trim($this->nombre);
        foreach (['direccion', 'ciudad', 'foto'] as $campo) {
            if (is_string($this->$campo)) {
                $valor = trim($this->$campo);
                $this->$campo = $valor === '' ? null : $valor;
            }
        }

        if ($this->nombre === '') {
            static::setAlerta('error', 'El nombre de la cancha es obligatorio.');
        } elseif (mb_strlen($this->nombre) > 100) {
            static::setAlerta('error', 'El nombre no puede superar los 100 caracteres.');
        }

        if (!in_array($this->tipo, self::TIPOS, true)) {
            static::setAlerta('error', 'El tipo de cancha no es válido.');
        }

        if ($this->tipo === 'club' && $this->id_equipo_propietario === null) {
            static::setAlerta('error', 'Una cancha de club necesita un equipo propietario.');
        }

        if ($this->id_equipo_propietario !== null && !Equipo::find($this->id_equipo_propietario)) {
            static::setAlerta('error', 'El equipo propietario no existe.');
        }

        if ($this->capacidad !== null && $this->capacidad < 1) {
            static::setAlerta('error', 'La capacidad debe ser mayor a 0.');
        }

        foreach (['direccion' => 150, 'ciudad' => 100, 'foto' => 255] as $campo => $max) {
            if ($this->$campo !== null && mb_strlen($this->$campo) > $max) {
                static::setAlerta('error', "El campo {$campo} no puede superar los {$max} caracteres.");
            }
        }

        return static::getAlertas();
    }
}