<?php

declare(strict_types=1);

namespace Model;

class LoginIntento extends ActiveRecord
{
    protected static string $tabla = 'login_intentos';
    protected static array $columnasDB = ['id', 'email', 'ip'];

    public string $email = '';
    public string $ip = '';

    private const MAX_POR_EMAIL = 5;
    private const MAX_POR_IP    = 20;   // más alto: varias personas pueden compartir IP
    private const VENTANA_MIN   = 15;

    public static function registrar(string $email, string $ip): void
    {
        $stmt = self::$db->prepare('INSERT INTO login_intentos (email, ip) VALUES (?, ?)');
        $stmt->execute([mb_strtolower($email), $ip]);
    }

    public static function bloqueado(string $email, string $ip): bool
    {
        $porEmail = self::$db->prepare(
            'SELECT COUNT(*) FROM login_intentos WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $porEmail->execute([mb_strtolower($email), self::VENTANA_MIN]);

        $porIp = self::$db->prepare(
            'SELECT COUNT(*) FROM login_intentos WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $porIp->execute([$ip, self::VENTANA_MIN]);

        return (int) $porEmail->fetchColumn() >= self::MAX_POR_EMAIL
            || (int) $porIp->fetchColumn() >= self::MAX_POR_IP;
    }

    public static function limpiar(string $email): void
    {
        $stmt = self::$db->prepare('DELETE FROM login_intentos WHERE email = ?');
        $stmt->execute([mb_strtolower($email)]);
        self::$db->exec('DELETE FROM login_intentos WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }
}