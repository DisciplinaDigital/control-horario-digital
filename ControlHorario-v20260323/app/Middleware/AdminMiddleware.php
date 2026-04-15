<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AdminMiddleware
{
    public function handle(Request $request): void
    {
        $session = Session::getInstance();

        // First check if logged in
        if (!$session->isLoggedIn()) {
            if ($request->wantsJson()) {
                Response::json(['success' => false, 'message' => 'No autenticado'], 401);
            }
            Response::redirectToRoute('login');
        }

        // Then check if admin
        if (!$session->isAdmin()) {
            if ($request->wantsJson()) {
                Response::json(['success' => false, 'message' => 'Acceso denegado'], 403);
            }
            Response::forbidden();
        }
    }
}
