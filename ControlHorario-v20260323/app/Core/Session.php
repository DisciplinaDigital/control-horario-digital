<?php

namespace App\Core;

class Session
{
    private static ?Session $instance = null;
    private bool $started = false;

    private function __construct() {}

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        $lifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 3600);

        // Detectar HTTPS de forma fiable (proxy inverso, Cloudflare, nginx, etc.)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443')
            || str_starts_with($_ENV['APP_URL'] ?? '', 'https://');

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,   // flag Secure cuando estamos en HTTPS
            'httponly' => true,
            'samesite' => 'Strict',   // protección CSRF de nivel cookie
        ]);

        // Prefijo __Secure- obliga al navegador a rechazar la cookie si no es Secure.
        // En HTTP local (dev) usamos el nombre sin prefijo para que funcione igualmente.
        session_name($isHttps ? '__Secure-CH_SESSION' : 'CH_SESSION');
        session_start();
        $this->started = true;

        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Session timeout check
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $lifetime) {
                $this->destroy();
                $this->start();
                return;
            }
        }
        $_SESSION['last_activity'] = time();
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function regenerate(bool $deleteOld = true): void
    {
        session_regenerate_id($deleteOld);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        $this->started = false;
    }

    public function setUser(array $user): void
    {
        $this->set('user', $user);
        $this->set('user_id', $user['id']);
    }

    public function getUser(): ?array
    {
        return $this->get('user');
    }

    public function isLoggedIn(): bool
    {
        return $this->has('user_id') && $this->has('user');
    }

    public function isAdmin(): bool
    {
        $user = $this->getUser();
        return $user && ($user['role'] ?? '') === 'admin';
    }

    public function getCsrfToken(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    public function isStarted(): bool
    {
        return $this->started;
    }
}
