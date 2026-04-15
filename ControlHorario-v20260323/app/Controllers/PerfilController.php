<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Usuario;
use App\Models\Notificacion;
use App\Models\Configuracion;
use App\Services\AuditService;

class PerfilController
{
    private Usuario $usuarioModel;
    private AuditService $auditService;
    private Configuracion $config;
    private Session $session;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->auditService = new AuditService();
        $this->config       = new Configuracion();
        $this->session      = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user       = $this->session->getUser();
        $notifCount = (new Notificacion())->countUnread($user['id']);

        Response::view('perfil/index', [
            'user'        => $user,
            'notifCount'  => $notifCount,
            'theme'       => $this->config->getTheme(),
            'companyName' => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'        => $this->config->get('logo'),
            'pageTitle'   => 'Mi Perfil',
            'success'     => $this->session->getFlash('success'),
            'error'       => $this->session->getFlash('error'),
        ], 'app');
    }

    public function actualizar(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('perfil');
        }

        $user   = $this->session->getUser();
        $userId = $user['id'];

        $nombre    = trim($request->post('nombre', ''));
        $apellidos = trim($request->post('apellidos', ''));
        $telefono  = trim($request->post('telefono', ''));

        if (empty($nombre) || empty($apellidos)) {
            $this->session->flash('error', 'El nombre y apellidos son obligatorios.');
            Response::redirectToRoute('perfil');
        }

        $oldData = $this->usuarioModel->findById($userId);
        $updated = $this->usuarioModel->update($userId, [
            'nombre'    => $nombre,
            'apellidos' => $apellidos,
            'telefono'  => $telefono ?: null,
        ]);

        if ($updated) {
            // Update session data
            $newUser = $this->usuarioModel->findById($userId);
            $this->session->setUser($newUser);
            $this->auditService->logUsuarioModificado($userId, $oldData, $newUser);
            $this->session->flash('success', 'Perfil actualizado correctamente.');
        } else {
            $this->session->flash('error', 'Error al actualizar el perfil.');
        }

        Response::redirectToRoute('perfil');
    }

    public function cambiarPassword(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('perfil');
        }

        $user   = $this->session->getUser();
        $userId = $user['id'];

        $actual      = $request->post('password_actual', '');
        $nueva       = $request->post('password_nueva', '');
        $confirmacion = $request->post('password_confirmacion', '');

        if (empty($actual) || empty($nueva) || empty($confirmacion)) {
            $this->session->flash('error', 'Todos los campos de contraseña son obligatorios.');
            Response::redirectToRoute('perfil');
        }

        if ($nueva !== $confirmacion) {
            $this->session->flash('error', 'La nueva contraseña y la confirmación no coinciden.');
            Response::redirectToRoute('perfil');
        }

        if (strlen($nueva) < 8) {
            $this->session->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            Response::redirectToRoute('perfil');
        }

        $dbUser = $this->usuarioModel->findById($userId);

        if (!$this->usuarioModel->verifyPassword($actual, $dbUser['password_hash'])) {
            $this->session->flash('error', 'La contraseña actual es incorrecta.');
            Response::redirectToRoute('perfil');
        }

        $updated = $this->usuarioModel->update($userId, ['password' => $nueva]);

        if ($updated) {
            $this->auditService->log('password.cambiada', 'usuarios', $userId);
            $this->session->flash('success', 'Contraseña cambiada correctamente.');
        } else {
            $this->session->flash('error', 'Error al cambiar la contraseña.');
        }

        Response::redirectToRoute('perfil');
    }
}
