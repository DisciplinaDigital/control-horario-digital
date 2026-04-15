<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * ApiMiddleware
 *
 * Protege los endpoints /api/* para que SOLO sean accesibles
 * desde peticiones AJAX/fetch (XMLHttpRequest o Accept: application/json).
 *
 * Si alguien navega directamente a /api/... desde el navegador,
 * recibe un 404 silencioso — no revela que el endpoint existe.
 */
class ApiMiddleware
{
    public function handle(Request $request): void
    {
        if (!$request->wantsJson()) {
            // Devolver 404 (no 403) para no revelar la existencia del endpoint
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Not Found']);
            exit;
        }
    }
}
