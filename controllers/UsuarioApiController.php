<?php

declare(strict_types=1);

namespace Controllers;

use Classes\Auth;
use Classes\Email;
use Model\ActiveRecord;
use Model\PasswordReset;
use Model\Usuario;

class UsuarioApiController extends ApiController
{
    protected static string $modelo = Usuario::class;

    /** Exige sesión de un admin activo (lo confirma en la BD) y lo devuelve. */
    private static function actual(): Usuario
    {
        $sesion = Auth::usuario();
        $yo = $sesion ? Usuario::find((int) $sesion['id']) : null;

        if (!$yo) {
            static::json(['ok' => false, 'message' => 'Sesión no iniciada o vencida.'], 401);
        }
        if ($yo->activo !== 1 || $yo->admin !== 1) {
            static::json(['ok' => false, 'message' => 'Solo un administrador puede gestionar usuarios.'], 403);
        }
        return $yo;
    }

    /** Crea el token y manda el correo. Si falla, anula el token y devuelve false. */
    private static function enviarInvitacion(Usuario $u): bool
    {
        $token = null;
        try {
            $token = PasswordReset::crearPara((int) $u->id, 'invitacion');
            (new Email($u->email, $u->nombre, $u->apellido, $token))->enviarInvitacion();
            return true;
        } catch (\Throwable $ex) {
            error_log('Error enviando invitación: ' . $ex->getMessage());
            if ($token !== null && ($fila = PasswordReset::buscarValido($token))) {
                PasswordReset::marcarUsado((int) $fila['id']);
            }
            return false;
        }
    }

    public static function listar(): void
    {
        $yo = self::actual();

        $stmt = Usuario::conexion()->query(
            "SELECT u.id, u.nombre, u.apellido, u.email, u.admin, u.activo, u.ultimo_login, u.created_at,
                    EXISTS (SELECT 1 FROM password_resets p
                            WHERE p.usuario_id = u.id AND p.tipo = 'invitacion'
                              AND p.usado_en IS NULL AND p.expira_en > NOW()) AS invitacion_vigente
             FROM usuarios u
             ORDER BY u.apellido ASC, u.nombre ASC, u.id ASC"
        );
        $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($filas as &$f) {
            $f['id']                = (int) $f['id'];
            $f['admin']             = (int) $f['admin'];
            $f['activo']            = (int) $f['activo'];
            $f['invitacion_vigente'] = (int) $f['invitacion_vigente'];
            $f['es_yo']             = $f['id'] === (int) $yo->id;
        }
        unset($f);

        static::json(['ok' => true, 'data' => $filas, 'total' => count($filas)]);
    }

    /** Sin id: crea la cuenta (contraseña aleatoria) y manda la invitación. Con id: edita nombre, apellido y permiso. */
    public static function guardar(): void
    {
        static::soloPost();
        $yo    = self::actual();
        $input = static::leerInput();
        $id    = (int) ($input['id'] ?? 0);

        if ($id > 0) {
            $u = Usuario::find($id);
            if (!$u) {
                static::json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
            }
        } else {
            $u = new Usuario();
            $u->email    = (string) ($input['email'] ?? '');
            $u->password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $u->activo   = 1;
        }

        $u->nombre   = (string) ($input['nombre'] ?? $u->nombre);
        $u->apellido = (string) ($input['apellido'] ?? $u->apellido);
        // Nadie puede quitarse a sí mismo el permiso de admin
        $u->admin    = ($id > 0 && $id === (int) $yo->id) ? 1 : ((int) ($input['admin'] ?? 0) === 1 ? 1 : 0);

        $alertas = $u->validar();
        if (!empty($alertas)) {
            static::json(['ok' => false, 'alertas' => $alertas], 422);
        }

        try {
            $resultado = $u->guardar();
        } catch (\PDOException $e) {
            static::responderErrorBD($e, 'Ya existe un usuario con ese email.');
        }

        if (is_array($resultado)) {
            $u->id = (int) $resultado['id'];
            self::enviarInvitacion($u);   // si falla, la fila queda con "Reenviar invitación"
        }

        static::json(['ok' => true, 'data' => ['id' => (int) $u->id]], $id > 0 ? 200 : 201);
    }

    public static function estado(): void
    {
        static::soloPost();
        $yo    = self::actual();
        $input = static::leerInput();
        $u     = Usuario::find((int) ($input['id'] ?? 0));

        if (!$u) {
            static::json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
        }
        if ((int) $u->id === (int) $yo->id) {
            static::json(['ok' => false, 'message' => 'No podés desactivar tu propia cuenta.'], 409);
        }

        $activo = !empty($input['activo']) ? 1 : 0;
        $stmt = Usuario::conexion()->prepare('UPDATE usuarios SET activo = ? WHERE id = ? LIMIT 1');
        $stmt->execute([$activo, (int) $u->id]);

        static::json(['ok' => true, 'activo' => $activo]);
    }

    public static function reenviar(): void
    {
        static::soloPost();
        self::actual();
        $input = static::leerInput();
        $u     = Usuario::find((int) ($input['id'] ?? 0));

        if (!$u) {
            static::json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
        }
        if ($u->activo !== 1) {
            static::json(['ok' => false, 'message' => 'La cuenta está desactivada.'], 409);
        }
        if ($u->ultimo_login !== null) {
            static::json(['ok' => false, 'message' => 'Esa persona ya ingresó. Si olvidó la contraseña, puede usar «Olvidé mi contraseña».'], 409);
        }
        if (PasswordReset::huboReciente((int) $u->id, 1)) {
            static::json(['ok' => false, 'message' => 'Ya se envió un correo hace menos de un minuto.'], 429);
        }
        if (!self::enviarInvitacion($u)) {
            static::json(['ok' => false, 'message' => 'No se pudo enviar el correo. Revisá la configuración de correo.'], 502);
        }

        static::json(['ok' => true]);
    }

    public static function eliminar(): void
    {
        static::soloPost();
        self::actual();
        parent::eliminar();
    }

    protected static function bloqueoEliminar(ActiveRecord $registro): ?string
    {
        $yo = Auth::usuario();
        if ($yo && (int) $registro->id === (int) $yo['id']) {
            return 'No podés eliminar tu propia cuenta.';
        }
        return null;
    }
}