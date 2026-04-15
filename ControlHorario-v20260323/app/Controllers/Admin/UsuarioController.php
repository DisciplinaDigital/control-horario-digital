<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Usuario;
use App\Models\Notificacion;
use App\Models\Configuracion;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\MailService;

class UsuarioController
{
    private Usuario $usuarioModel;
    private AuditService $auditService;
    private NotificationService $notificationService;
    private Configuracion $config;
    private Session $session;

    public function __construct()
    {
        $this->usuarioModel        = new Usuario();
        $this->auditService        = new AuditService();
        $this->notificationService = new NotificationService();
        $this->config              = new Configuracion();
        $this->session             = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user = $this->session->getUser();

        $filters = [
            'search'       => $request->get('search', ''),
            'role'         => $request->get('role', ''),
            'departamento' => $request->get('departamento', ''),
        ];

        $usuarios      = $this->usuarioModel->all($filters);
        $departamentos = $this->usuarioModel->getDepartamentos();
        $notifCount    = (new Notificacion())->countUnread($user['id']);

        Response::view('admin/usuarios/index', [
            'user'          => $user,
            'usuarios'      => $usuarios,
            'filters'       => $filters,
            'departamentos' => $departamentos,
            'notifCount'    => $notifCount,
            'theme'         => $this->config->getTheme(),
            'companyName'   => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'          => $this->config->get('logo'),
            'pageTitle'     => 'Gestión de Usuarios',
            'success'       => $this->session->getFlash('success'),
            'error'         => $this->session->getFlash('error'),
        ], 'admin');
    }

    public function store(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/usuarios');
        }

        $nombre    = trim($request->post('nombre', ''));
        $apellidos = trim($request->post('apellidos', ''));
        $email     = trim($request->post('email', ''));
        $password  = $request->post('password', '');
        $role      = $request->post('role', 'usuario');
        $departamento = trim($request->post('departamento', ''));
        $telefono  = trim($request->post('telefono', ''));
        $diasVac   = (int)$request->post('dias_vacaciones_anuales', 22);

        // Validate
        if (empty($nombre) || empty($apellidos) || empty($email) || empty($password)) {
            $this->session->flash('error', 'Nombre, apellidos, email y contraseña son obligatorios.');
            Response::redirectToRoute('admin/usuarios');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'El email no es válido.');
            Response::redirectToRoute('admin/usuarios');
        }

        if (strlen($password) < 8) {
            $this->session->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            Response::redirectToRoute('admin/usuarios');
        }

        if ($this->usuarioModel->findByEmail($email)) {
            $this->session->flash('error', 'El email ya está registrado en el sistema.');
            Response::redirectToRoute('admin/usuarios');
        }

        $id = $this->usuarioModel->create([
            'nombre'                  => $nombre,
            'apellidos'               => $apellidos,
            'email'                   => $email,
            'password'                => $password,
            'role'                    => in_array($role, ['admin', 'usuario']) ? $role : 'usuario',
            'departamento'            => $departamento ?: null,
            'telefono'                => $telefono ?: null,
            'dias_vacaciones_anuales' => max(0, $diasVac),
        ]);

        if ($id) {
            $this->auditService->logUsuarioCreado($id, compact('nombre', 'apellidos', 'email', 'role'));
            $this->notificationService->onUsuarioCreado($id, $nombre);
            $this->session->flash('success', "Usuario {$nombre} {$apellidos} creado correctamente.");
        } else {
            $this->session->flash('error', 'Error al crear el usuario.');
        }

        Response::redirectToRoute('admin/usuarios');
    }

    public function update(Request $request, string $id): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/usuarios');
        }

        $userId = (int)$id;
        $user   = $this->session->getUser();

        $oldData = $this->usuarioModel->findById($userId);
        if (!$oldData) {
            $this->session->flash('error', 'Usuario no encontrado.');
            Response::redirectToRoute('admin/usuarios');
        }

        // max_distance_override: vacío = NULL (usa global), 0 = sin límite, >0 = metros específicos
        $rawMaxDist = $request->post('max_distance_override', '');
        $maxDistanceOverride = ($rawMaxDist !== '' && $rawMaxDist !== null)
            ? max(0, (int)$rawMaxDist)
            : null;

        $updateData = [
            'nombre'                  => trim($request->post('nombre', $oldData['nombre'])),
            'apellidos'               => trim($request->post('apellidos', $oldData['apellidos'])),
            'telefono'                => trim($request->post('telefono', '')) ?: null,
            'departamento'            => trim($request->post('departamento', '')) ?: null,
            'role'                    => $request->post('role', $oldData['role']),
            'activo'                  => (int)$request->post('activo', $oldData['activo']),
            'dias_vacaciones_anuales' => (int)$request->post('dias_vacaciones_anuales', $oldData['dias_vacaciones_anuales']),
            'max_distance_override'   => $maxDistanceOverride,
        ];

        // Prevent demoting yourself if only admin
        if ($userId === $user['id'] && $updateData['role'] !== 'admin') {
            $this->session->flash('error', 'No puedes quitarte el rol de administrador a ti mismo.');
            Response::redirectToRoute('admin/usuarios');
        }

        $newPassword = $request->post('password', '');
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                $this->session->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
                Response::redirectToRoute('admin/usuarios');
            }
            $updateData['password'] = $newPassword;
        }

        $updated = $this->usuarioModel->update($userId, $updateData);

        if ($updated) {
            $this->auditService->logUsuarioModificado($userId, $oldData, $updateData);
            $this->session->flash('success', 'Usuario actualizado correctamente.');
        } else {
            $this->session->flash('success', 'No hay cambios que guardar.');
        }

        Response::redirectToRoute('admin/usuarios');
    }

    public function reactivar(Request $request, string $id): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/usuarios');
        }

        $userId = (int)$id;
        $target = $this->usuarioModel->findById($userId);
        if (!$target) {
            $this->session->flash('error', 'Usuario no encontrado.');
            Response::redirectToRoute('admin/usuarios');
        }

        $ok = $this->usuarioModel->reactivate($userId);
        if ($ok) {
            $this->auditService->log('usuario.reactivado', 'usuarios', $userId, null, ['nombre' => $target['nombre']]);
            $this->session->flash('success', "Usuario {$target['nombre']} {$target['apellidos']} reactivado correctamente.");
        } else {
            $this->session->flash('error', 'No se pudo reactivar el usuario.');
        }

        Response::redirectToRoute('admin/usuarios');
    }

    public function delete(Request $request, string $id): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/usuarios');
        }

        $userId = (int)$id;
        $user   = $this->session->getUser();

        if ($userId === $user['id']) {
            $this->session->flash('error', 'No puedes eliminar tu propia cuenta.');
            Response::redirectToRoute('admin/usuarios');
        }

        $target = $this->usuarioModel->findById($userId);
        if (!$target) {
            $this->session->flash('error', 'Usuario no encontrado.');
            Response::redirectToRoute('admin/usuarios');
        }

        // Soft delete only (legal requirement - 4 year retention)
        $ok = $this->usuarioModel->softDelete($userId);

        if ($ok) {
            $this->auditService->log('usuario.eliminado', 'usuarios', $userId, $target);
            $this->session->flash('success', "Usuario {$target['nombre']} {$target['apellidos']} desactivado (conservado para auditoría).");
        } else {
            $this->session->flash('error', 'Error al desactivar el usuario.');
        }

        Response::redirectToRoute('admin/usuarios');
    }

    public function resetPassword(Request $request, string $id): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/usuarios');
        }

        $userId = (int)$id;
        $target = $this->usuarioModel->findById($userId);

        if (!$target) {
            $this->session->flash('error', 'Usuario no encontrado.');
            Response::redirectToRoute('admin/usuarios');
        }

        $password        = $request->post('password', '');
        $passwordConfirm = $request->post('password_confirm', '');

        if (strlen($password) < 8) {
            $this->session->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            Response::redirectToRoute('admin/usuarios');
        }

        if ($password !== $passwordConfirm) {
            $this->session->flash('error', 'Las contraseñas no coinciden.');
            Response::redirectToRoute('admin/usuarios');
        }

        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->usuarioModel->update($userId, [
            'password_hash'        => $newHash,
            'must_change_password' => 1,
        ]);

        // Enviar email de notificación con contraseña temporal (siempre, sin excepción)
        try {
            $mailer = new MailService();
            $mailer->sendPasswordResetByAdmin($target['email'],
                $target['nombre'] . ' ' . $target['apellidos'], $password);
        } catch (\Exception $e) {
            error_log('MailService error on admin reset: ' . $e->getMessage());
        }

        $this->auditService->log('usuario.password_reset', 'usuarios', $userId, null,
            ['reset_by_admin' => $this->session->get('user_id'), 'email_enviado' => true]);

        $this->session->flash('success',
            "Contraseña de {$target['nombre']} {$target['apellidos']} restablecida. Se ha enviado un email de notificación al empleado.");

        Response::redirectToRoute('admin/usuarios');
    }
}
