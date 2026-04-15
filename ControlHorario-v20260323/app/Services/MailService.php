<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

/**
 * MailService — Wrapper sobre PHPMailer (bundled en lib/PHPMailer/)
 *
 * Configuración via .env:
 *  SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS,
 *  SMTP_FROM, SMTP_FROM_NAME, SMTP_SECURE (ssl|tls|none)
 */
class MailService
{
    /**
     * Devuelve true si SMTP está configurado con al menos un host.
     */
    public static function isConfigured(): bool
    {
        return !empty(trim($_ENV['SMTP_HOST'] ?? ''));
    }

    private function createMailer(): PHPMailer
    {
        if (!self::isConfigured()) {
            throw new MailerException('SMTP no configurado (SMTP_HOST vacío). El email no será enviado.');
        }

        $mail = new PHPMailer(true);

        $secure = strtolower($_ENV['SMTP_SECURE'] ?? 'tls');
        $port   = (int)($_ENV['SMTP_PORT'] ?? 587);

        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = trim($_ENV['SMTP_HOST']);
        $mail->Port       = $port;
        $mail->SMTPAuth   = !empty($_ENV['SMTP_USER']);
        $mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;

        if ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $fromEmail = $_ENV['SMTP_FROM'] ?? ($_ENV['SMTP_USER'] ?? '');
        $fromName  = $_ENV['SMTP_FROM_NAME'] ?? ($_ENV['APP_NAME'] ?? 'Control Horario');

        $mail->setFrom($fromEmail, $fromName);

        return $mail;
    }

    /**
     * Notifica al empleado que el admin ha reseteado su contraseña.
     */
    public function sendPasswordResetByAdmin(string $toEmail, string $toName, string $tempPassword): bool
    {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = 'Tu contraseña ha sido restablecida — ' . ($_ENV['APP_NAME'] ?? 'Control Horario');
            $mail->Body    = $this->buildAdminResetEmail($toName, $tempPassword);
            $mail->AltBody = "Hola {$toName},\n\nEl administrador ha restablecido tu contraseña temporal: {$tempPassword}\n\nDeberás cambiarla obligatoriamente en tu próximo acceso.\n\nAccede en: " . rtrim($_ENV['APP_URL'] ?? '', '/') . '/login';
            $mail->send();
            return true;
        } catch (MailerException $e) {
            error_log('[MailService] sendPasswordResetByAdmin error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía el email de recuperación de contraseña (self-service).
     */
    public function sendPasswordReset(string $toEmail, string $toName, string $resetUrl): bool
    {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de contraseña — ' . ($_ENV['APP_NAME'] ?? 'Control Horario');
            $mail->Body    = $this->buildResetEmail($toName, $resetUrl);
            $mail->AltBody = "Hola {$toName},\n\nRestablece tu contraseña en: {$resetUrl}\n\nEste enlace caduca en 60 minutos.";
            $mail->send();
            return true;
        } catch (MailerException $e) {
            error_log('[MailService] sendPasswordReset error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Método genérico de envío.
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            $mail->send();
            return true;
        } catch (MailerException $e) {
            error_log('[MailService] send error: ' . $e->getMessage());
            return false;
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Email templates
    // ──────────────────────────────────────────────────────────────────────

    private function buildAdminResetEmail(string $toName, string $tempPassword): string
    {
        $appName = htmlspecialchars($_ENV['APP_NAME'] ?? 'Control Horario');
        $appUrl  = rtrim($_ENV['APP_URL'] ?? '', '/');
        $nombre  = htmlspecialchars($toName);
        $pass    = htmlspecialchars($tempPassword);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 20px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <tr>
          <td style="background:#2563eb;padding:28px 40px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">{$appName}</h1>
            <p style="color:#bfdbfe;margin:6px 0 0;font-size:14px;">Sistema de Control Horario Digital</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 40px;">
            <h2 style="color:#1e293b;margin:0 0 16px;font-size:20px;">Contraseña restablecida</h2>
            <p style="color:#475569;margin:0 0 16px;line-height:1.6;">Hola <strong>{$nombre}</strong>,</p>
            <p style="color:#475569;margin:0 0 20px;line-height:1.6;">
              El administrador ha restablecido tu contraseña de acceso al sistema.<br>
              Tu <strong>contraseña temporal</strong> es:
            </p>
            <div style="background:#f8fafc;border:2px dashed #2563eb;border-radius:8px;padding:16px;text-align:center;margin:0 0 24px;">
              <span style="font-size:22px;font-weight:800;letter-spacing:3px;color:#1e293b;font-family:monospace;">{$pass}</span>
            </div>
            <div style="background:#fef3c7;border-left:4px solid #d97706;padding:14px 16px;border-radius:0 6px 6px 0;margin:0 0 24px;">
              <p style="margin:0;color:#92400e;font-size:13px;line-height:1.5;">
                <strong>Deberás cambiarla obligatoriamente en tu próximo acceso.</strong><br>
                Elige una contraseña que solo tú conozcas y no la compartas con nadie.
              </p>
            </div>
            <p style="color:#475569;margin:0 0 24px;line-height:1.6;font-size:13px;">
              Si no esperabas este cambio, contacta inmediatamente con tu administrador.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center">
                  <a href="{$appUrl}/login"
                     style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;
                            padding:12px 28px;border-radius:6px;font-size:15px;font-weight:600;">
                    Iniciar Sesión
                  </a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="background:#f8fafc;padding:16px 40px;text-align:center;">
            <p style="color:#94a3b8;font-size:11px;margin:0;">{$appName} · Sistema conforme a RD-Ley 8/2019</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    private function buildResetEmail(string $nombre, string $resetUrl): string
    {
        $appName  = htmlspecialchars($_ENV['APP_NAME'] ?? 'Control Horario');
        $nombre   = htmlspecialchars($nombre);
        $resetUrl = htmlspecialchars($resetUrl);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 20px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <tr>
          <td style="background:#2563eb;padding:32px 40px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">{$appName}</h1>
            <p style="color:#bfdbfe;margin:6px 0 0;font-size:14px;">Sistema de Control Horario Digital</p>
          </td>
        </tr>
        <tr>
          <td style="padding:40px;">
            <h2 style="color:#1e293b;margin:0 0 16px;font-size:20px;">Recuperar contraseña</h2>
            <p style="color:#475569;margin:0 0 16px;line-height:1.6;">Hola <strong>{$nombre}</strong>,</p>
            <p style="color:#475569;margin:0 0 24px;line-height:1.6;">
              Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center" style="padding:8px 0 24px;">
                  <a href="{$resetUrl}"
                     style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;
                            padding:14px 32px;border-radius:6px;font-size:16px;font-weight:600;">
                    Restablecer contraseña
                  </a>
                </td>
              </tr>
            </table>
            <p style="color:#64748b;font-size:13px;margin:0 0 8px;">URL directa:</p>
            <p style="color:#2563eb;font-size:12px;word-break:break-all;margin:0 0 24px;">{$resetUrl}</p>
            <hr style="border:none;border-top:1px solid #e2e8f0;margin:0 0 24px;">
            <p style="color:#94a3b8;font-size:12px;margin:0;line-height:1.5;">
              Este enlace caduca en <strong>60 minutos</strong>.<br>
              Si no has solicitado este cambio, ignora este email.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f8fafc;padding:20px 40px;text-align:center;">
            <p style="color:#94a3b8;font-size:11px;margin:0;">{$appName} · Sistema conforme a RD-Ley 8/2019</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
