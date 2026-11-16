<?php

declare(strict_types=1);

namespace Model;

class Arbitro extends ActiveRecord
{
    protected static string $tabla = 'arbitros';

    protected static array $columnasDB = ['id', 'nombre', 'apellido', 'foto'];

    public string $nombre = '';
    public string $apellido = '';
    public ?string $foto = null;

    public function validar(): array
    {
        parent::validar();

        $this->nombre = trim($this->nombre);
        $this->apellido = trim($this->apellido);

        foreach (['nombre' => 'El nombre', 'apellido' => 'El apellido'] as $campo => $texto) {
            if ($this->$campo === '') {
                static::setAlerta('error', "{$texto} del árbitro es obligatorio.");
            } elseif (mb_strlen($this->$campo) > 100) {
                static::setAlerta('error', "{$texto} del árbitro no puede superar los 100 caracteres.");
            }
        }

        return static::getAlertas();
    }
}