<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware
{
    /** Tiempo máximo de sesión absoluto: 8 horas */
    private const ABSOLUTE_TIMEOUT = 28800;

    public function handle(Request $request): void
    {
        $session = Session::getInstance();

        if (!$session->isLoggedIn()) {
            // Solo guardar la URL de destino para peticiones de navegador normal,
            // NUNCA para llamadas AJAX/API (evita que el login redirija a /api/...)
            if (!$request->wantsJson()) {
                $session->flash('intended_url', $request->path());
            }

            if ($request->wantsJson()) {
                Response::json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            Response::redirectToRoute('login');
        }

        // ── 1. Timeout absoluto (8h) ──────────────────────────────────────
        // El idle timeout de 1h está en Session::start(). Este es adicional:
        // aunque el usuario esté activo, forzamos re-login cada 8h.
        $loginAt = $session->get('login_at', 0);
        if ($loginAt && (time() - $loginAt > self::ABSOLUTE_TIMEOUT)) {
            $session->destroy();

            if ($request->wantsJson()) {
                Response::json(['success' => false, 'message' => 'Sesión expirada por seguridad'], 401);
            }

            Session::getInstance()->start();
            Session::getInstance()->flash('error', 'Tu sesión ha expirado (8h máximo). Por favor inicia sesión de nuevo.');
            Response::redirectToRoute('login');
        }

        // ── 2. Binding de sesión (anti session-hijacking) ─────────────────
        // Comparamos el hash del User-Agent almacenado al hacer login con el actual.
        // Un cambio de UA con la misma session ID es síntoma de robo de sesión.
        $storedUa  = $session->get('ua_hash');
        $currentUa = hash('sha256', $request->userAgent());

        if ($storedUa !== null && $storedUa !== $currentUa) {
            // Posible session hijacking → destruir sesión silenciosamente
            $session->destroy();

            if ($request->wantsJson()) {
                Response::json(['success' => false, 'message' => 'Sesión inválida'], 401);
            }

            Response::redirectToRoute('login');
        }

        // ── 3. Verificar que el usuario sigue activo ──────────────────────
        $user = $session->getUser();
        if (!$user || empty($user['activo'])) {
            $session->destroy();

            if ($request->wantsJson()) {
                Response::json(['success' => false, 'message' => 'Cuenta desactivada'], 403);
            }

            Response::redirectToRoute('login');
        }

        $path = $request->path();

        // ── 4. Cambio de contraseña obligatorio (reset por admin) ────────
        if (!empty($user['must_change_password']) && !str_starts_with($path, '/cambiar-password')) {
            if ($request->wantsJson()) {
                Response::json(['success' => false, 'message' => 'Debes cambiar tu contraseña'], 403);
            }
            Response::redirectToRoute('cambiar-password');
        }

        // ── 5. Verificar aceptación de términos ───────────────────────────
        if (empty($user['terminos_aceptados'])
            && !str_starts_with($path, '/terminos')
            && !str_starts_with($path, '/cambiar-password')) {
            if ($request->wantsJson()) {
                Response::json(['success' => false, 'message' => 'Debes aceptar los términos de uso'], 403);
            }
            Response::redirectToRoute('terminos');
        }
    }
}
