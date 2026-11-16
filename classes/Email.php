<?php

declare(strict_types=1);

namespace Classes;

use PHPMailer\PHPMailer\PHPMailer;

final class Email
{
    public function __construct(
        private string $email,
        private string $nombre,
        private string $apellido,
        private string $token
    ) {}

    private function mailer(): PHPMailer
    {
        $mail = new PHPMailer(true);   // lanza excepciones
        $mail->isSMTP();
        $mail->Host       = $_ENV['EMAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['EMAIL_USER'];
        $mail->Password   = $_ENV['EMAIL_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $_ENV['EMAIL_PORT'];
        $mail->setFrom($_ENV['EMAIL_FROM'], $_ENV['EMAIL_NAME']);
        $mail->addAddress($this->email, $this->nombre . ' ' . $this->apellido);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        return $mail;
    }

    private function enviar(string $asunto, string $texto, string $boton, string $aviso): void
    {
        $e = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        $url = rtrim($_ENV['APP_URL'], '/') . '/reestablecer?token=' . urlencode($this->token);

        $mail = $this->mailer();
        $mail->Subject = $asunto;
        $mail->Body = "<html><body>"
            . "<p><strong>Hola " . $e($this->nombre . ' ' . $this->apellido) . "</strong></p>"
            . "<p>" . $e($texto) . "</p>"
            . "<p><a href='" . $e($url) . "'>" . $e($boton) . "</a></p>"
            . "<p>" . $e($aviso) . "</p>"
            . "</body></html>";
        $mail->AltBody = "$texto\n$url";
        $mail->send();
    }

    public function enviarReset(): void
    {
        $this->enviar(
            'Restablecé tu contraseña',
            'Pediste restablecer tu contraseña. El enlace vence en 1 hora y se puede usar una sola vez.',
            'Restablecer contraseña',
            'Si no fuiste vos, ignorá este mensaje.'
        );
    }

    public function enviarInvitacion(): void
    {
        $this->enviar(
            'Te invitaron al panel de DatosFutbol',
            'Te crearon una cuenta en el panel de administración. Elegí tu contraseña desde este enlace (vence en 72 horas).',
            'Crear mi contraseña',
            'Si no esperabas este mensaje, ignoralo.'
        );
    }
}