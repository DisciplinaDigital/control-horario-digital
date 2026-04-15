<?php
/**
 * Control Horario Digital
 * Copyright (C) 2026 Javier Ortiz - disciplinadigital.es
 * * Este programa es software libre: usted puede redistribuirlo y/o modificarlo 
 * bajo los términos de la Licencia Pública General GNU publicada por 
 * la Free Software Foundation, ya sea la versión 3 de la Licencia, o 
 * (a su elección) cualquier versión posterior.
 *
 * Este programa se distribuye con la esperanza de que sea útil, 
 * pero SIN NINGUNA GARANTÍA; sin siquiera la garantía implícita de 
 * MERCANTILIDAD o APTITUD PARA UN PROPÓSITO PARTICULAR. 
 * Consulte la Licencia Pública General GNU para más detalles.
 *
 * Redirección raíz → /public/
 *
 * El cliente instala la app en la raíz del dominio.
 * APP_URL = https://tudominio.com/public  (donde vive la app)
 *
 * Cualquier petición a tudominio.com/* se redirige a tudominio.com/public/*
 * de forma transparente (301 permanente, SEO-friendly).
 *
 * Ejemplos:
 *   tudominio.com/          → tudominio.com/public/
 *   tudominio.com/login     → tudominio.com/public/login
 *   tudominio.com/admin     → tudominio.com/public/admin
 *
 * Las peticiones que ya llevan /public/ en la URL son servidas directamente
 * por nginx/Apache sin pasar por aquí (archivos físicos existentes).
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

if (!file_exists(BASE_PATH . '/.env') && !file_exists(__DIR__ . '/install/.lock')) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    header('Location: ' . $base . '/install/');
    exit;
}

$vendorAutoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
}
// Load app configuration: carga .env, registra autoloader PSR-4, inicia sesión
require BASE_PATH . '/config/app.php';

$_appUrlPath = parse_url($_ENV['APP_URL'] ?? '', PHP_URL_PATH) ?? '';
define('APP_BASE_PATH', rtrim($_appUrlPath, '/'));

if (APP_BASE_PATH === '') {
    $_reqUri = $_SERVER['REQUEST_URI'] ?? '/';
    if ($_reqUri === '/public' || str_starts_with($_reqUri, '/public/')) {
        $_cleanPath = str_starts_with($_reqUri, '/public/')
            ? substr($_reqUri, strlen('/public'))
            : '/';
        $_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        header('Location: ' . $_scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_cleanPath, true, 301);
        exit;
    }
}

// ── Cabeceras de seguridad HTTP ───────────────────────────────
\App\Core\Security::applyHeaders();

// ── Rutas ─────────────────────────────────────────────────────
require BASE_PATH . '/config/routes.php';

// ── Dispatch ──────────────────────────────────────────────────
try {
    $request = new \App\Core\Request();
    \App\Core\Router::dispatch($request);
} catch (\Throwable $e) {
    error_log('Application error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        http_response_code(500);
        echo '<h1>Error de Aplicación</h1>';
        echo '<p><strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>';
        echo '<p>Archivo: ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        \App\Core\Response::serverError($e->getMessage());
    }
}
