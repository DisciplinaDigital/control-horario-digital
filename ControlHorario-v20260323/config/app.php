<?php

// ── Parser de .env sin dependencias externas ─────────────────
function loadEnvFile(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignorar comentarios
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Quitar comillas envolventes
        if (strlen($value) >= 2 &&
            (($value[0] === '"' && str_ends_with($value, '"')) ||
             ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

loadEnvFile(BASE_PATH . '/.env');

// ── Valores por defecto ───────────────────────────────────────
$_ENV['APP_NAME']    ??= 'Control Horario Digital';
$_ENV['APP_ENV']     ??= 'production';
$_ENV['APP_DEBUG']   ??= 'false';
$_ENV['APP_URL']     ??= 'http://localhost/NuevoControlHorario/public';
$_ENV['TIMEZONE']    ??= 'Europe/Madrid';

// ── Zona horaria ──────────────────────────────────────────────
date_default_timezone_set($_ENV['TIMEZONE']);

// ── Reporte de errores ────────────────────────────────────────
if ($_ENV['APP_ENV'] === 'development' && $_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH . '/storage/logs/error.log');
}

// ── Autoloader PSR-4 (sin Composer) ──────────────────────────
$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require $autoloadPath;
} else {
    spl_autoload_register(function (string $class): void {
        // App\ namespace → app/
        if (str_starts_with($class, 'App\\')) {
            $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            if (file_exists($file)) {
                require $file;
            }
            return;
        }

        // PHPMailer\PHPMailer\ namespace → lib/PHPMailer/
        if (str_starts_with($class, 'PHPMailer\\PHPMailer\\')) {
            $short = substr($class, strlen('PHPMailer\\PHPMailer\\'));
            $file  = BASE_PATH . '/lib/PHPMailer/' . $short . '.php';
            if (file_exists($file)) {
                require $file;
            }
            return;
        }
    });
}

// ── Sesión ────────────────────────────────────────────────────
\App\Core\Session::getInstance()->start();

return [
    'name'     => $_ENV['APP_NAME'],
    'env'      => $_ENV['APP_ENV'],
    'debug'    => $_ENV['APP_DEBUG'] === 'true',
    'url'      => $_ENV['APP_URL'],
    'timezone' => $_ENV['TIMEZONE'],
];
