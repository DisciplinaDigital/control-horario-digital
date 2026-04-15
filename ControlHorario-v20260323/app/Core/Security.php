<?php

namespace App\Core;

/**
 * Security Headers
 *
 * Aplica todas las cabeceras de seguridad HTTP recomendadas.
 * Llamar ANTES de cualquier salida (output).
 *
 * Cubre:
 *  - Strict-Transport-Security (HSTS)
 *  - Content-Security-Policy (CSP)
 *  - X-Frame-Options
 *  - X-Content-Type-Options
 *  - Referrer-Policy
 *  - Permissions-Policy
 *  - Cross-Origin-Opener-Policy
 *  - Cross-Origin-Resource-Policy
 *  - Elimina X-Powered-By y Server si es posible
 */
class Security
{
    /**
     * Aplica todas las cabeceras de seguridad HTTP.
     * Llamar una sola vez desde el front controller (public/index.php).
     */
    public static function applyHeaders(): void
    {
        // Determinar si estamos en HTTPS
        $isHttps = self::isHttps();

        // ── 1. Eliminar cabeceras que revelan información del servidor ────
        header_remove('X-Powered-By');
        header_remove('Server');

        // ── 2. HSTS — Solo en HTTPS (en HTTP causaría problemas) ─────────
        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // ── 3. Content-Security-Policy ────────────────────────────────────
        // Bootstrap y Bootstrap Icons se sirven localmente (/assets/vendor/).
        // 'unsafe-inline' necesario porque las vistas PHP inyectan variables
        // en bloques <script> y <style> inline (tema de colores, coords GPS).
        $cspParts = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",          // data: para posibles logos en base64
            "font-src 'self'",                      // Bootstrap Icons webfont local
            "connect-src 'self'",                   // fetch() de fichajes, notificaciones
            "frame-src 'none'",
            "frame-ancestors 'none'",               // equiv. a X-Frame-Options DENY
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ];
        if ($isHttps) {
            $cspParts[] = 'upgrade-insecure-requests';
        }
        header('Content-Security-Policy: ' . implode('; ', $cspParts));

        // ── 4. X-Frame-Options (soporte para navegadores antiguos) ────────
        header('X-Frame-Options: DENY');

        // ── 5. X-Content-Type-Options ─────────────────────────────────────
        header('X-Content-Type-Options: nosniff');

        // ── 6. Referrer-Policy ────────────────────────────────────────────
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // ── 7. Permissions-Policy ─────────────────────────────────────────
        // Geolocation DEBE quedar permitida (self) — el sistema la usa para fichajes
        $permissions = [
            'camera=()',
            'microphone=()',
            'payment=()',
            'usb=()',
            'bluetooth=()',
            'geolocation=(self)',    // ← necesario para el fichaje GPS
            'fullscreen=(self)',
        ];
        header('Permissions-Policy: ' . implode(', ', $permissions));

        // ── 8. Cross-Origin Headers ───────────────────────────────────────
        // COEP require-corp roto con CDN externos → usamos unsafe-none
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');

        // ── 9. Otras buenas prácticas ─────────────────────────────────────
        header('X-DNS-Prefetch-Control: off');
    }

    /**
     * Detecta HTTPS de forma fiable en entornos con proxy inverso (nginx, Cloudflare, etc.)
     */
    public static function isHttps(): bool
    {
        // HTTPS directo
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        // Detrás de proxy (nginx → PHP-FPM, Cloudflare, etc.)
        if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return true;
        }
        if (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') {
            return true;
        }
        // Puerto 443
        if (($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        // APP_URL configurada con https://
        if (str_starts_with($_ENV['APP_URL'] ?? '', 'https://')) {
            return true;
        }
        return false;
    }
}
