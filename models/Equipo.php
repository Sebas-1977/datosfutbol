<?php

declare(strict_types=1);

namespace Model;

class Equipo extends ActiveRecord
{
    protected static string $tabla = 'equipos';

    protected static array $columnasDB = [
        'id', 'nombre', 'escudo', 'foto', 'nombre_completo', 'fundacion',
        'color_primario', 'color_secundario', 'ciudad', 'direccion',
        'telefono', 'email', 'sitio_web', 'id_cancha_local',
    ];

    /** Largo máximo de cada campo de texto (igual que en la base). */
    private const LIMITES = [
        'nombre' => 100, 'escudo' => 255, 'foto' => 255, 'nombre_completo' => 150,
        'ciudad' => 100, 'direccion' => 150, 'telefono' => 30, 'email' => 150,
        'sitio_web' => 255,
    ];

    private const ETIQUETAS = [
    'nombre' => 'El nombre', 'escudo' => 'El escudo', 'foto' => 'La foto',
    'nombre_completo' => 'El nombre completo', 'ciudad' => 'La ciudad',
    'direccion' => 'La dirección', 'telefono' => 'El teléfono',
    'email' => 'El email', 'sitio_web' => 'El sitio web',
    ];

    public string $nombre = '';
    public ?string $escudo = null;
    public ?string $foto = null;
    public ?string $nombre_completo = null;
    public ?int $fundacion = null;
    public ?string $color_primario = null;
    public ?string $color_secundario = null;
    public ?string $ciudad = null;
    public ?string $direccion = null;
    public ?string $telefono = null;
    public ?string $email = null;
    public ?string $sitio_web = null;
    public ?int $id_cancha_local = null;

    /** Recorta los textos y convierte los vacíos en NULL. */
    private function normalizar(): void
    {
        $this->nombre = trim($this->nombre);

        foreach (array_keys(self::LIMITES) as $campo) {
            if ($campo === 'nombre') {
                continue;
            }
            if (is_string($this->$campo)) {
                $valor = trim($this->$campo);
                $this->$campo = $valor === '' ? null : $valor;
            }
        }
    }

    public function validar(): array
    {
        parent::validar();
        $this->normalizar();

        if ($this->nombre === '') {
            static::setAlerta('error', 'El nombre del equipo es obligatorio.');
        }

        foreach (self::LIMITES as $campo => $max) {
            if ($this->$campo !== null && mb_strlen((string) $this->$campo) > $max) {
                static::setAlerta('error', self::ETIQUETAS[$campo] . " no puede superar los {$max} caracteres.");
            }
        }

        if ($this->fundacion !== null && ($this->fundacion < 1850 || $this->fundacion > (int) date('Y'))) {
            static::setAlerta('error', 'El año de fundación no es válido.');
        }

        foreach (['color_primario', 'color_secundario'] as $campo) {
            if ($this->$campo !== null && !preg_match('/^#[0-9A-Fa-f]{6}$/', $this->$campo)) {
                static::setAlerta('error', "El {$campo} debe tener formato #RRGGBB.");
            }
        }

        if ($this->email !== null && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            static::setAlerta('error', 'El email no es válido.');
        }

        if ($this->sitio_web !== null) {
            $esUrl = filter_var($this->sitio_web, FILTER_VALIDATE_URL)
                && in_array(parse_url($this->sitio_web, PHP_URL_SCHEME), ['http', 'https'], true);
            if (!$esUrl) {
                static::setAlerta('error', 'El sitio web debe ser una URL http o https.');
            }
        }

        if ($this->id_cancha_local !== null && !Cancha::find($this->id_cancha_local)) {
            static::setAlerta('error', 'La cancha local no existe.');
        }

        return static::getAlertas();
    }
}