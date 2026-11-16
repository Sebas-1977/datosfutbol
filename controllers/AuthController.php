<?php

declare(strict_types=1);

namespace Controllers;

use Classes\Auth;
use Classes\Email;
use Model\LoginIntento;
use Model\PasswordReset;
use Model\Usuario;

class AuthController
{
    private const DESTINO_DEFECTO = '/admin/torneos';
    // Hash bcrypt de una clave cualquiera: se usa para que tardar lo mismo cuando el email no existe.
    private const HASH_FALSO = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    private static function vista(string $nombre, array $datos = []): void
    {
        $e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $datos['csrf'] = Auth::csrfToken();
        extract($datos, EXTR_SKIP);

        ob_start();
        require __DIR__ . "/../views/auth/{$nombre}.php";
        $contenido = ob_get_clean();
        require __DIR__ . '/../views/auth/_marco.php';
    }

    /** Solo se permite volver a rutas internas del panel (evita open redirect). */
    private static function destinoSeguro(string $destino): string
    {
        if (preg_match('#^/admin(/[A-Za-z0-9_\-/]*)?(\?[A-Za-z0-9_\-=&%.]*)?$#', $destino)) {
            return $destino;
        }
        return self::DESTINO_DEFECTO;
    }

    public static function login(): void
    {
        Auth::iniciarSesion();
        $volver = self::destinoSeguro((string) ($_GET['volver'] ?? $_POST['volver'] ?? ''));

        if (Auth::isAuth()) {
            header('Location: ' . $volver);
            exit;
        }

        $errores = [];
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $pass  = (string) ($_POST['password'] ?? '');
            $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            if (!Auth::csrfValido($_POST['csrf'] ?? null)) {
                $errores[] = 'La página venció. Probá de nuevo.';
            } elseif (LoginIntento::bloqueado($email, $ip)) {
                $errores[] = 'Demasiados intentos fallidos. Esperá 15 minutos.';
            } else {
                $u = $email !== '' ? Usuario::where('email', mb_strtolower($email)) : null;
                $claveOk = password_verify($pass, $u->password ?? self::HASH_FALSO);

                if ($u && $u->activo === 1 && $claveOk) {
                    LoginIntento::limpiar($email);
                    if (password_needs_rehash($u->password, PASSWORD_DEFAULT)) {
                        $u->actualizarPassword($pass);
                    }
                    Auth::entrar($u);
                    $u->registrarLogin();
                    header('Location: ' . $volver);
                    exit;
                }

                LoginIntento::registrar($email, $ip);
                $errores[] = 'Email o contraseña incorrectos.';
            }
        }

        self::vista('login', [
            'titulo' => 'Ingresar', 'errores' => $errores, 'email' => $email, 'volver' => $volver,
        ]);
    }

    public static function logout(): void
    {
        Auth::iniciarSesion();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::csrfValido($_POST['csrf'] ?? null)) {
            Auth::salir();
        }
        header('Location: /login');
        exit;
    }

    public static function olvide(): void
    {
        Auth::iniciarSesion();
        $errores = [];
        $enviado = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::csrfValido($_POST['csrf'] ?? null)) {
                $errores[] = 'La página venció. Probá de nuevo.';
            } else {
                $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
                $u = $email !== '' ? Usuario::where('email', $email) : null;

                if ($u && $u->activo === 1 && !PasswordReset::huboReciente((int) $u->id)) {
                    try {
                        $token = PasswordReset::crearPara((int) $u->id, 'reset');
                        (new Email($u->email, $u->nombre, $u->apellido, $token))->enviarReset();
                    } catch (\Throwable $ex) {
                        error_log('Error enviando reset: ' . $ex->getMessage());
                    }
                }
                $enviado = true;   // mismo mensaje exista o no el email
            }
        }

        self::vista('olvide', ['titulo' => 'Recuperar contraseña', 'errores' => $errores, 'enviado' => $enviado]);
    }

    /** Sirve para recuperar contraseña y para aceptar una invitación. */
    public static function reestablecer(): void
    {
        Auth::iniciarSesion();
        $token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
        $reset = PasswordReset::buscarValido($token);
        $errores = [];
        $listo = false;

        if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::csrfValido($_POST['csrf'] ?? null)) {
                $errores[] = 'La página venció. Recargá el enlace del email.';
            } else {
                $pass  = (string) ($_POST['password'] ?? '');
                $pass2 = (string) ($_POST['password2'] ?? '');
                $errores = Usuario::erroresPassword($pass, $pass2);

                if (!$errores) {
                    $u = Usuario::find($reset['usuario_id']);
                    if ($u && $u->activo === 1) {
                        $u->actualizarPassword($pass);
                        PasswordReset::marcarUsado((int) $reset['id']);
                        $listo = true;
                    } else {
                        $reset = null;
                    }
                }
            }
        }

        self::vista('reestablecer', [
            'titulo' => 'Nueva contraseña', 'errores' => $errores,
            'token' => $token, 'valido' => (bool) $reset, 'listo' => $listo,
        ]);
    }
}