<?php

namespace App\Core;

class Response
{
    public static function redirect(string $url, int $code = 302): never
    {
        // Handle relative URLs
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $appUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
            $url = $appUrl . '/' . ltrim($url, '/');
        }
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    public static function redirectToRoute(string $path, int $code = 302): never
    {
        $appUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        $url = $appUrl . '/' . ltrim($path, '/');
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    public static function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function view(string $template, array $data = [], string $layout = 'app'): void
    {
        // Extract data to make available in views
        extract($data);

        // Build view path
        $viewPath = BASE_PATH . '/resources/views/' . $template . '.php';

        if (!file_exists($viewPath)) {
            self::notFound();
        }

        // Capture view content
        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        // Load layout if specified
        if ($layout) {
            $layoutPath = BASE_PATH . '/resources/views/layouts/' . $layout . '.php';
            if (file_exists($layoutPath)) {
                include $layoutPath;
                return;
            }
        }

        echo $content;
    }

    public static function notFound(): never
    {
        http_response_code(404);
        $viewPath = BASE_PATH . '/resources/views/errors/404.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<h1>404 - Página no encontrada</h1>';
        }
        exit;
    }

    public static function forbidden(): never
    {
        http_response_code(403);
        $viewPath = BASE_PATH . '/resources/views/errors/403.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<h1>403 - Acceso denegado</h1>';
        }
        exit;
    }

    public static function serverError(string $message = ''): never
    {
        http_response_code(500);
        $viewPath = BASE_PATH . '/resources/views/errors/500.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<h1>500 - Error interno del servidor</h1>';
            if (!empty($message) && ($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                echo '<p>' . htmlspecialchars($message) . '</p>';
            }
        }
        exit;
    }

    public static function download(string $content, string $filename, string $mimeType = 'text/plain'): never
    {
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, must-revalidate');
        echo $content;
        exit;
    }
}
