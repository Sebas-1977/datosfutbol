<?php

declare(strict_types=1);

namespace Model;

use PDO;

class PasswordReset extends ActiveRecord
{
    protected static string $tabla = 'password_resets';
    protected static array $columnasDB = ['id', 'usuario_id', 'tipo', 'token_hash', 'expira_en', 'usado_en'];

    public int $usuario_id = 0;
    public string $tipo = 'reset';
    public string $token_hash = '';
    public string $expira_en = '';
    public ?string $usado_en = null;

    /** Crea un token nuevo, anula los anteriores y devuelve el token en claro (solo para el email). */
    public static function crearPara(int $usuarioId, string $tipo): string
    {
        $minutos = $tipo === 'invitacion' ? 4320 : 60;   // 72 h / 1 h
        $token   = bin2hex(random_bytes(32));

        $anular = self::$db->prepare(
            'UPDATE password_resets SET usado_en = NOW() WHERE usuario_id = ? AND tipo = ? AND usado_en IS NULL'
        );
        $anular->execute([$usuarioId, $tipo]);

        $ins = self::$db->prepare(
            'INSERT INTO password_resets (usuario_id, tipo, token_hash, expira_en)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
        );
        $ins->execute([$usuarioId, $tipo, hash('sha256', $token), $minutos]);

        return $token;
    }

    /** Hay un pedido reciente (evita que alguien llene el buzón de otra persona). */
    public static function huboReciente(int $usuarioId, int $minutos = 5): bool
    {
        $stmt = self::$db->prepare(
            'SELECT COUNT(*) FROM password_resets
             WHERE usuario_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$usuarioId, $minutos]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function buscarValido(string $token): ?array
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
            return null;
        }
        $stmt = self::$db->prepare(
            'SELECT id, usuario_id, tipo FROM password_resets
             WHERE token_hash = ? AND usado_en IS NULL AND expira_en > NOW() LIMIT 1'
        );
        $stmt->execute([hash('sha256', $token)]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public static function marcarUsado(int $id): void
    {
        $stmt = self::$db->prepare('UPDATE password_resets SET usado_en = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }
}