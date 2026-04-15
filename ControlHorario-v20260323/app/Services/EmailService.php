<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $from;
    private string $fromName;

    public function __construct()
    {
        $this->host     = $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->port     = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->user     = $_ENV['SMTP_USER'] ?? '';
        $this->pass     = $_ENV['SMTP_PASS'] ?? '';
        $this->from     = $_ENV['SMTP_FROM'] ?? 'noreply@example.com';
        $this->fromName = $_ENV['SMTP_FROM_NAME'] ?? 'Control Horario';
    }

    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        try {
            $mail = new PHPMailer(true);

            // SMTP settings
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = !empty($this->user);
            $mail->Username   = $this->user;
            $mail->Password   = $this->pass;
            $mail->SMTPSecure = $this->port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';

            // From/To
            $mail->setFrom($this->from, $this->fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $isHtml ? $body : '';
            $mail->AltBody = $isHtml ? strip_tags($body) : $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordReset(string $email, string $token, string $nombre = ''): bool
    {
        $appUrl  = rtrim($_ENV['APP_URL'] ?? '', '/');
        $link    = "{$appUrl}/reset-password?token={$token}";
        $company = $_ENV['APP_NAME'] ?? 'Control Horario';

        $subject = "Restablecimiento de contraseña - {$company}";
        $body    = $this->buildPasswordResetEmail($nombre, $link, $company);

        return $this->send($email, $subject, $body);
    }

    public function sendWelcome(string $email, string $nombre, string $password): bool
    {
        $appUrl  = rtrim($_ENV['APP_URL'] ?? '', '/');
        $company = $_ENV['APP_NAME'] ?? 'Control Horario';

        $subject = "Bienvenido a {$company}";
        $body    = "
            <h2>Bienvenido, {$nombre}</h2>
            <p>Tu cuenta ha sido creada en {$company}.</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Contraseña temporal:</strong> {$password}</p>
            <p>Por favor, cambia tu contraseña al iniciar sesión.</p>
            <p><a href='{$appUrl}/login'>Iniciar sesión</a></p>
        ";

        return $this->send($email, $subject, $body);
    }

    private function buildPasswordResetEmail(string $nombre, string $link, string $company): string
    {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head><meta charset='UTF-8'><title>Restablecer contraseña</title></head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #2563eb; color: white; padding: 20px; border-radius: 8px 8px 0 0;'>
                <h1 style='margin: 0;'>{$company}</h1>
            </div>
            <div style='background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px;'>
                <h2>Restablecer contraseña</h2>
                " . ($nombre ? "<p>Hola <strong>{$nombre}</strong>,</p>" : '') . "
                <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
                <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
                <a href='{$link}' style='display: inline-block; background: #2563eb; color: white;
                   padding: 12px 24px; border-radius: 6px; text-decoration: none; margin: 20px 0;'>
                   Restablecer contraseña
                </a>
                <p style='color: #666; font-size: 14px;'>
                    Si no solicitaste este cambio, ignora este correo.<br>
                    Este enlace expira en 1 hora.
                </p>
                <hr style='border: none; border-top: 1px solid #e2e8f0;'>
                <p style='color: #999; font-size: 12px;'>
                    {$company} - Sistema de Control Horario
                </p>
            </div>
        </body>
        </html>
        ";
    }
}
