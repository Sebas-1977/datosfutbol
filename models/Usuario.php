<?php

declare(strict_types=1);

namespace Model;

class Usuario extends ActiveRecord
{
    protected static string $tabla = 'usuarios';

    protected static array $columnasDB = [
        'id', 'nombre', 'apellido', 'email', 'password', 'admin', 'activo', 'ultimo_login',
    ];

    public string $nombre = '';
    public string $apellido = '';
    public string $email = '';
    public string $password = '';
    public string $password2 = '';      // no está en $columnasDB: no se guarda
    public int $admin = 0;
    public int $activo = 1;
    public ?string $ultimo_login = null;

    public function validar(): array
    {
        parent::validar();
        $this->nombre   = trim($this->nombre);
        $this->apellido = trim($this->apellido);
        $this->email    = mb_strtolower(trim($this->email));

        if ($this->nombre === '')   static::setAlerta('error', 'El nombre es obligatorio.');
        if ($this->apellido === '') static::setAlerta('error', 'El apellido es obligatorio.');

        if ($this->email === '' || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            static::setAlerta('error', 'El email no es válido.');
        } else {
            $existente = self::where('email', $this->email);
            if ($existente && $existente->id !== $this->id) {
                static::setAlerta('error', 'Ya existe un usuario con ese email.');
            }
        }
        return static::getAlertas();
    }

    /** Devuelve los errores de una contraseña nueva (vacío si está bien). */
    public static function erroresPassword(string $pass, string $pass2): array
    {
        $errores = [];
        if (mb_strlen($pass) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($pass !== $pass2)     $errores[] = 'Las contraseñas no coinciden.';
        return $errores;
    }

    public function actualizarPassword(string $plano): void
    {
        $stmt = self::$db->prepare('UPDATE usuarios SET password = ? WHERE id = ? LIMIT 1');
        $stmt->execute([password_hash($plano, PASSWORD_DEFAULT), (int) $this->id]);
    }

    public function registrarLogin(): void
    {
        $stmt = self::$db->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $this->id]);
    }

    public function esAdmin(): bool
    {
        return $this->admin === 1;
    }
}