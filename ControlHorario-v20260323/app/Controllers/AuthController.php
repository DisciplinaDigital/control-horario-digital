<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\Usuario;
use App\Services\AuditService;
use App\Services\LoginRateLimiter;
use App\Services\MailService;

class AuthController
{
    private Usuario          $usuarioModel;
    private Session          $session;
    private AuditService     $auditService;
    private LoginRateLimiter $rateLimiter;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->session      = Session::getInstance();
        $this->auditService = new AuditService();
        $this->rateLimiter  = new LoginRateLimiter();
    }

    // ══════════════════════════════════════════════════════════════════════
    // LOGIN
    // ══════════════════════════════════════════════════════════════════════

    public function showLogin(Request $request): void
    {
        if ($this->session->isLoggedIn()) {
            Response::redirectToRoute($this->session->isAdmin() ? 'admin' : '');
        }

        Response::view('auth/login', [
            'error'      => $this->session->getFlash('error'),
            'success'    => $this->session->getFlash('success'),
            'email'      => $this->session->getFlash('email'),
            'pageTitle'  => 'Iniciar Sesión',
        ], 'auth');
    }

    public function login(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido. Recarga la página.');
            Response::redirectToRoute('login');
        }

        $email    = trim($request->post('email', ''));
        $password = $request->post('password', '');
        $ip       = $request->ip();

        if (empty($email) || empty($password)) {
            $this->session->flash('error', 'Por favor introduce tu email y contraseña.');
            $this->session->flash('email', $email);
            Response::redirectToRoute('login');
        }

        // ── 1. Brute-force check ─────────────────────────────────────────
        $remaining = $this->rateLimiter->remainingLockout($ip, $email);
        if ($remaining !== null) {
            $this->session->flash(
                'error',
                'Demasiados intentos fallidos. Espera ' . LoginRateLimiter::formatRemaining($remaining) . ' e inténtalo de nuevo.'
            );
            $this->session->flash('email', $email);
            Response::redirectToRoute('login');
        }

        // ── 2. Buscar usuario ────────────────────────────────────────────
        $user = $this->usuarioModel->findByEmail($email);

        // ── 3. Anti-timing attack ────────────────────────────────────────
        // Si el usuario no existe ejecutamos password_verify igualmente con un
        // hash ficticio. Así el tiempo de respuesta es idéntico en ambos casos
        // y no es posible detectar si un email está registrado midiendo tiempos.
        if (!$user) {
            password_verify($password, '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012345');
            $this->rateLimiter->record($ip, $email, false);
            $this->auditService->logLogin(0, $email, false);
            $this->session->flash('error', 'Email o contraseña incorrectos.');
            $this->session->flash('email', $email);
            Response::redirectToRoute('login');
        }

        if (!$this->usuarioModel->verifyPassword($password, $user['password_hash'])) {
            $this->rateLimiter->record($ip, $email, false);
            $this->auditService->logLogin($user['id'], $email, false);
            $this->session->flash('error', 'Email o contraseña incorrectos.');
            $this->session->flash('email', $email);
            Response::redirectToRoute('login');
        }

        if (!$user['activo']) {
            $this->session->flash('error', 'Tu cuenta está desactivada. Contacta con el administrador.');
            Response::redirectToRoute('login');
        }

        // ── 4. Login exitoso ─────────────────────────────────────────────
        $this->rateLimiter->record($ip, $email, true);

        $this->session->regenerate();
        $this->session->setUser($user);

        // Datos de seguridad de sesión
        $this->session->set('login_at', time());
        $this->session->set('ua_hash', hash('sha256', $request->userAgent()));

        $this->usuarioModel->updateLastAccess($user['id']);
        $this->registerSession($user, $request);
        $this->auditService->logLogin($user['id'], $email, true);

        // Redirect a aceptación de términos si no los ha aceptado
        if (empty($user['terminos_aceptados'])) {
            Response::redirectToRoute('terminos');
        }

        $intended = $this->session->getFlash('intended_url', '');

        if ($user['role'] === 'admin') {
            Response::redirectToRoute(trim($intended, '/') ?: 'admin');
        } else {
            Response::redirectToRoute(trim($intended, '/') ?: '');
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // TÉRMINOS
    // ══════════════════════════════════════════════════════════════════════

    public function showTerminos(Request $request): void
    {
        if (!$this->session->isLoggedIn()) {
            Response::redirectToRoute('login');
        }

        $user = $this->session->getUser();
        if (!empty($user['terminos_aceptados'])) {
            Response::redirectToRoute($user['role'] === 'admin' ? 'admin' : '');
        }

        Response::view('auth/terminos', [
            'user'      => $user,
            'pageTitle' => 'Aceptar Términos de Uso',
        ], 'auth');
    }

    public function aceptarTerminos(Request $request): void
    {
        if (!$this->session->isLoggedIn()) {
            Response::redirectToRoute('login');
        }

        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('terminos');
        }

        $user = $this->session->getUser();

        if ($request->post('acepto') !== '1') {
            $this->session->flash('error', 'Debes aceptar los términos para continuar.');
            Response::redirectToRoute('terminos');
        }

        $this->usuarioModel->update($user['id'], [
            'terminos_aceptados'       => 1,
            'fecha_aceptacion_terminos' => date('Y-m-d H:i:s'),
        ]);

        $updatedUser = $this->usuarioModel->findById($user['id']);
        if ($updatedUser) {
            $this->session->setUser($updatedUser);
        }

        $this->auditService->log('terminos.aceptados', 'usuarios', $user['id']);

        Response::redirectToRoute($user['role'] === 'admin' ? 'admin' : '');
    }

    // ══════════════════════════════════════════════════════════════════════
    // LOGOUT
    // ══════════════════════════════════════════════════════════════════════

    public function logout(Request $request): void
    {
        $userId = $this->session->get('user_id');

        if ($userId) {
            $this->auditService->logLogout($userId);
            $this->terminateSession();
        }

        $this->session->destroy();
        Response::redirectToRoute('login');
    }

    // ══════════════════════════════════════════════════════════════════════
    // CAMBIO OBLIGATORIO DE CONTRASEÑA (reset por admin)
    // ══════════════════════════════════════════════════════════════════════

    public function showCambiarPassword(Request $request): void
    {
        if (!$this->session->isLoggedIn()) {
            Response::redirectToRoute('login');
        }

        $user = $this->session->getUser();
        if (empty($user['must_change_password'])) {
            Response::redirectToRoute($user['role'] === 'admin' ? 'admin' : '');
        }

        Response::view('auth/cambiar-password', [
            'error'     => $this->session->getFlash('error'),
            'pageTitle' => 'Cambiar Contraseña',
        ], 'auth');
    }

    public function cambiarPassword(Request $request): void
    {
        if (!$this->session->isLoggedIn()) {
            Response::redirectToRoute('login');
        }

        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('cambiar-password');
        }

        $user            = $this->session->getUser();
        $password        = $request->post('password', '');
        $passwordConfirm = $request->post('password_confirm', '');

        if (strlen($password) < 8) {
            $this->session->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            Response::redirectToRoute('cambiar-password');
        }

        if ($password !== $passwordConfirm) {
            $this->session->flash('error', 'Las contraseñas no coinciden.');
            Response::redirectToRoute('cambiar-password');
        }

        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->usuarioModel->update($user['id'], [
            'password_hash'        => $newHash,
            'must_change_password' => 0,
        ]);

        // Refrescar sesión con los nuevos datos
        $updatedUser = $this->usuarioModel->findById($user['id']);
        if ($updatedUser) {
            $this->session->setUser($updatedUser);
        }

        $this->auditService->log('usuario.password_cambiado', 'usuarios', $user['id']);

        $this->session->flash('success', 'Contraseña cambiada correctamente. Bienvenido/a.');
        Response::redirectToRoute($user['role'] === 'admin' ? 'admin' : '');
    }

    // ══════════════════════════════════════════════════════════════════════
    // RECUPERACIÓN DE CONTRASEÑA
    // ══════════════════════════════════════════════════════════════════════

    public function showForgotPassword(Request $request): void
    {
        Response::view('auth/forgot-password', [
            'error'     => $this->session->getFlash('error'),
            'success'   => $this->session->getFlash('success'),
            'pageTitle' => 'Recuperar Contraseña',
        ], 'auth');
    }

    public function forgotPassword(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('forgot-password');
        }

        $email = trim($request->post('email', ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Introduce un email válido.');
            Response::redirectToRoute('forgot-password');
        }

        // Siempre mostramos éxito para evitar enumeración de emails
        $this->session->flash(
            'success',
            'Si el email está registrado, recibirás las instrucciones en unos minutos. Revisa también la carpeta de spam.'
        );

        $user = $this->usuarioModel->findByEmail($email);

        if ($user && $user['activo']) {
            $this->sendPasswordResetEmail($user);
            $this->auditService->log('password.reset_requested', 'usuarios', $user['id']);
        }

        Response::redirectToRoute('forgot-password');
    }

    public function showResetPassword(Request $request, string $token): void
    {
        // Validar token antes de mostrar el formulario
        if (!$this->validateResetToken($token)) {
            $this->session->flash('error', 'El enlace de recuperación no es válido o ha caducado. Solicita uno nuevo.');
            Response::redirectToRoute('forgot-password');
        }

        Response::view('auth/reset-password', [
            'token'     => $token,
            'error'     => $this->session->getFlash('error'),
            'pageTitle' => 'Nueva Contraseña',
        ], 'auth');
    }

    public function resetPassword(Request $request, string $token): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirect(rtrim($_ENV['APP_URL'] ?? '', '/') . '/reset-password/' . urlencode($token));
        }

        $password        = $request->post('password', '');
        $passwordConfirm = $request->post('password_confirm', '');

        // Validar contraseña
        if (strlen($password) < 8) {
            $this->session->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            Response::redirect(rtrim($_ENV['APP_URL'] ?? '', '/') . '/reset-password/' . urlencode($token));
        }

        if ($password !== $passwordConfirm) {
            $this->session->flash('error', 'Las contraseñas no coinciden.');
            Response::redirect(rtrim($_ENV['APP_URL'] ?? '', '/') . '/reset-password/' . urlencode($token));
        }

        // Validar y consumir token
        $db        = Database::getInstance();
        $tokenHash = hash('sha256', $token);

        $reset = $db->fetchOne(
            "SELECT * FROM password_resets
              WHERE token_hash = ? AND usado = 0 AND expires_at > NOW()
              LIMIT 1",
            [$tokenHash]
        );

        if (!$reset) {
            $this->session->flash('error', 'El enlace de recuperación no es válido o ha caducado. Solicita uno nuevo.');
            Response::redirectToRoute('forgot-password');
        }

        $user = $this->usuarioModel->findByEmail($reset['email']);

        if (!$user) {
            $this->session->flash('error', 'Usuario no encontrado.');
            Response::redirectToRoute('forgot-password');
        }

        // Actualizar contraseña
        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->usuarioModel->update($user['id'], ['password_hash' => $newHash]);

        // Marcar token como usado
        $db->execute(
            "UPDATE password_resets SET usado = 1 WHERE token_hash = ?",
            [$tokenHash]
        );

        // Invalidar todas las sesiones activas del usuario
        $db->execute(
            "UPDATE sesiones SET terminada_en = NOW(), activa = 0 WHERE usuario_id = ?",
            [$user['id']]
        );

        $this->auditService->log('password.reset_completed', 'usuarios', $user['id']);

        $this->session->flash('success', 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.');
        Response::redirectToRoute('login');
    }

    // ══════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════════

    private function sendPasswordResetEmail(array $user): void
    {
        // Generar token seguro de 64 chars (32 bytes → hex)
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hora

        $db = Database::getInstance();

        // Invalidar tokens anteriores no usados para este email
        $db->execute(
            "UPDATE password_resets SET usado = 1 WHERE email = ? AND usado = 0",
            [$user['email']]
        );

        // Guardar nuevo token (solo el hash, nunca el token plano)
        $db->execute(
            "INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)",
            [$user['email'], $tokenHash, $expiresAt]
        );

        $appUrl   = rtrim($_ENV['APP_URL'] ?? '', '/');
        $resetUrl = "{$appUrl}/reset-password/{$token}";
        $nombre   = $user['nombre'] . ' ' . $user['apellidos'];

        try {
            if (MailService::isConfigured()) {
                $mailer = new MailService();
                $mailer->sendPasswordReset($user['email'], $nombre, $resetUrl);
            } else {
                error_log('[AuthController] SMTP no configurado — email de recuperación no enviado a ' . $user['email']);
            }
        } catch (\Throwable $e) {
            error_log('[MailService] sendPasswordReset error: ' . $e->getMessage());
        }
    }

    private function validateResetToken(string $token): bool
    {
        if (empty($token) || strlen($token) !== 64) {
            return false;
        }

        $db        = Database::getInstance();
        $tokenHash = hash('sha256', $token);

        $reset = $db->fetchOne(
            "SELECT id FROM password_resets
              WHERE token_hash = ? AND usado = 0 AND expires_at > NOW()
              LIMIT 1",
            [$tokenHash]
        );

        return $reset !== null;
    }

    private function registerSession(array $user, Request $request): void
    {
        try {
            $db = Database::getInstance();
            $db->execute(
                "INSERT INTO sesiones (id, usuario_id, ip_address, user_agent, iniciada_en, ultimo_acceso, activa)
                 VALUES (?, ?, ?, ?, NOW(), NOW(), 1)
                 ON DUPLICATE KEY UPDATE ultimo_acceso = NOW(), activa = 1",
                [
                    session_id(),
                    $user['id'],
                    $request->ip(),
                    $request->userAgent(),
                ]
            );
        } catch (\Exception $e) {
            error_log('Session registration error: ' . $e->getMessage());
        }
    }

    private function terminateSession(): void
    {
        try {
            $db = Database::getInstance();
            $db->execute(
                "UPDATE sesiones SET terminada_en = NOW(), activa = 0 WHERE id = ?",
                [session_id()]
            );
        } catch (\Exception $e) {
            error_log('Session termination error: ' . $e->getMessage());
        }
    }
}
