<?php

namespace App\Services;

use App\Core\Database;

/**
 * LoginRateLimiter
 *
 * Protección anti fuerza bruta para el formulario de login.
 *
 * Política:
 *  - Más de 5 fallos por EMAIL en los últimos 15 min → bloqueo
 *  - Más de 10 fallos por IP en los últimos 15 min → bloqueo
 *  - Bloqueo dura 15 minutos desde el último intento fallido
 *
 * Tabla: login_intentos (ver database/migrations/security.sql)
 */
class LoginRateLimiter
{
    private const WINDOW_MINUTES      = 15;
    private const MAX_BY_EMAIL        = 5;
    private const MAX_BY_IP           = 10;

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Comprueba si la combinación IP+email está bloqueada.
     * Devuelve null si está OK, o los segundos restantes de bloqueo.
     */
    public function remainingLockout(string $ip, string $email): ?int
    {
        $window = date('Y-m-d H:i:s', strtotime('-' . self::WINDOW_MINUTES . ' minutes'));

        // Fallos recientes por email
        $byEmail = (int)$this->db->fetchOne(
            "SELECT COUNT(*) as c FROM login_intentos
              WHERE email = ? AND exitoso = 0 AND created_at >= ?",
            [$email, $window]
        )['c'];

        // Fallos recientes por IP
        $byIp = (int)$this->db->fetchOne(
            "SELECT COUNT(*) as c FROM login_intentos
              WHERE ip = ? AND exitoso = 0 AND created_at >= ?",
            [$ip, $window]
        )['c'];

        if ($byEmail >= self::MAX_BY_EMAIL || $byIp >= self::MAX_BY_IP) {
            // Calcular segundos restantes desde el último intento fallido
            $latest = $this->db->fetchOne(
                "SELECT MAX(created_at) as t FROM login_intentos
                  WHERE (email = ? OR ip = ?) AND exitoso = 0 AND created_at >= ?",
                [$email, $ip, $window]
            )['t'] ?? null;

            if ($latest) {
                $unlockAt = strtotime($latest) + (self::WINDOW_MINUTES * 60);
                $remaining = $unlockAt - time();
                return $remaining > 0 ? $remaining : null;
            }
        }

        return null;
    }

    /**
     * Registra un intento (exitoso o fallido).
     */
    public function record(string $ip, string $email, bool $success): void
    {
        $this->db->execute(
            "INSERT INTO login_intentos (ip, email, exitoso, created_at) VALUES (?, ?, ?, NOW())",
            [$ip, $email, $success ? 1 : 0]
        );

        // Si fue exitoso, limpiar intentos fallidos de ese email+IP
        if ($success) {
            $this->clearFailed($ip, $email);
        }

        // Limpieza periódica de registros antiguos (1 de cada 20 peticiones)
        if (random_int(1, 20) === 1) {
            $this->cleanup();
        }
    }

    /**
     * Elimina intentos fallidos de un email/IP tras login exitoso.
     */
    private function clearFailed(string $ip, string $email): void
    {
        $this->db->execute(
            "DELETE FROM login_intentos WHERE (email = ? OR ip = ?) AND exitoso = 0",
            [$email, $ip]
        );
    }

    /**
     * Elimina registros más antiguos que la ventana de tiempo.
     */
    private function cleanup(): void
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . (self::WINDOW_MINUTES * 2) . ' minutes'));
        $this->db->execute(
            "DELETE FROM login_intentos WHERE created_at < ?",
            [$cutoff]
        );
    }

    /**
     * Formatea los segundos restantes en texto legible.
     */
    public static function formatRemaining(int $seconds): string
    {
        if ($seconds >= 60) {
            return ceil($seconds / 60) . ' minutos';
        }
        return $seconds . ' segundos';
    }
}
