<?php

declare(strict_types=1);

namespace Model;

class Grupo extends ActiveRecord
{
    protected static string $tabla = 'grupos';

    protected static array $columnasDB = ['id', 'id_fase', 'nombre'];

    public ?int $id_fase = null;
    public string $nombre = '';

    public function validar(): array
    {
        parent::validar();

        if (!$this->id_fase) {
            static::setAlerta('error', 'La fase es obligatoria.');
        }

        if (trim($this->nombre) === '') {
            static::setAlerta('error', 'El nombre del grupo es obligatorio.');
        }

        return static::getAlertas();
    }
}