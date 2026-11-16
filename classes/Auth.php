<?php

declare(strict_types=1);

namespace Classes;

use Model\ActiveRecord;
use Model\Usuario;

final class Auth
{
    public static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
            return;
        }
        session_name('liga_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function entrar(Usuario $u): void
    {
        self::iniciarSesion();
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'       => (int) $u->id,
            'nombre'   => $u->nombre,
            'apellido' => $u->apellido,
            'email'    => $u->email,
            'admin'    => (int) $u->admin,
        ];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    public static function salir(): void
    {
        self::iniciarSesion();
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600, 'path' => $p['path'],
            'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_destroy();
    }

    public static function isAuth(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!isset($_COOKIE['liga_sid'])) {
                return false;
            }
            self::iniciarSesion();
        }
        return isset($_SESSION['usuario']['id']);
    }

    public static function isAdmin(): bool
    {
        return self::isAuth() && ($_SESSION['usuario']['admin'] ?? 0) === 1;
    }

    public static function usuario(): ?array
    {
        return self::isAuth() ? $_SESSION['usuario'] : null;
    }

    public static function csrfToken(): string
    {
        self::iniciarSesion();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function csrfValido(?string $token): bool
    {
        self::iniciarSesion();
        return is_string($token) && $token !== ''
            && hash_equals($_SESSION['csrf'] ?? '', $token);
    }

    /** Confirma en la BD que la cuenta sigue activa (y refresca el flag admin). */
    private static function revalidar(): bool
    {
        $stmt = ActiveRecord::conexion()->prepare('SELECT activo, admin, nombre, apellido FROM usuarios WHERE id = ?');
        $stmt->execute([(int) $_SESSION['usuario']['id']]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$fila || (int) $fila['activo'] !== 1) {
            self::salir();
            return false;
        }
        $_SESSION['usuario']['admin'] = (int) $fila['admin'];
        $_SESSION['usuario']['nombre']   = $fila['nombre'];
        $_SESSION['usuario']['apellido'] = $fila['apellido'];
        return true;
    }

    

    private static function json(int $codigo, string $mensaje): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Guardia global: se llama una vez en public/index.php, antes de comprobarRutas().
     *  - /admin/*                      requiere sesión
     *  - /api/* con POST/PUT/DELETE    requiere sesión + token CSRF
     */
    public static function proteger(string $ruta, string $metodo, string $uri): void
    {
        $esAdmin     = $ruta === '/admin' || str_starts_with($ruta, '/admin/');
        $esLectura   = in_array($metodo, ['GET', 'HEAD', 'OPTIONS'], true);
        $esEscritura = str_starts_with($ruta, '/api/') && !$esLectura;

        if (!$esAdmin && !$esEscritura) {
            if (isset($_COOKIE['liga_sid'])) {
                self::iniciarSesion();
            }
            return;
        }

        if (!self::isAuth() || !self::revalidar()) {
            if ($esAdmin && $metodo === 'GET') {
                header('Location: /login?volver=' . rawurlencode($uri));
                exit;
            }
            self::json(401, 'Sesión no iniciada o vencida.');
        }

        $soloAdmin = str_starts_with($ruta, '/admin/usuarios') || str_starts_with($ruta, '/api/usuarios');
        if ($soloAdmin && !self::isAdmin()) {
            if ($esAdmin && $metodo === 'GET') {
                http_response_code(403);
                exit('Acceso denegado.');
            }
            self::json(403, 'No tenés permisos de administrador.');
        }

        if (!$esLectura && !self::csrfValido($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            self::json(403, 'Token CSRF inválido.');
        }
    }
}