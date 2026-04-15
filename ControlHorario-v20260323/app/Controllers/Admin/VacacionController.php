<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Vacacion;
use App\Models\Usuario;
use App\Models\Notificacion;
use App\Models\Configuracion;
use App\Services\AuditService;
use App\Services\NotificationService;

class VacacionController
{
    private Vacacion $vacacionModel;
    private AuditService $auditService;
    private NotificationService $notificationService;
    private Configuracion $config;
    private Session $session;

    public function __construct()
    {
        $this->vacacionModel       = new Vacacion();
        $this->auditService        = new AuditService();
        $this->notificationService = new NotificationService();
        $this->config              = new Configuracion();
        $this->session             = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user = $this->session->getUser();

        $filters = [
            'estado'     => $request->get('estado', ''),
            'usuario_id' => $request->get('usuario_id', ''),
            'year'       => $request->get('year', date('Y')),
        ];

        $vacaciones = $this->vacacionModel->findAll($filters);
        $usuarios   = (new Usuario())->all();
        $notifCount = (new Notificacion())->countUnread($user['id']);

        Response::view('admin/vacaciones/index', [
            'user'        => $user,
            'vacaciones'  => $vacaciones,
            'usuarios'    => $usuarios,
            'filters'     => $filters,
            'notifCount'  => $notifCount,
            'theme'       => $this->config->getTheme(),
            'companyName' => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'        => $this->config->get('logo'),
            'pageTitle'   => 'Gestión de Vacaciones',
            'success'     => $this->session->getFlash('success'),
            'error'       => $this->session->getFlash('error'),
        ], 'admin');
    }

    public function resolver(Request $request, string $id): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/vacaciones');
        }

        $user        = $this->session->getUser();
        $vacacionId  = (int)$id;
        $estado      = $request->post('estado', '');
        $comentario  = trim($request->post('comentario', ''));

        if (!in_array($estado, ['aprobada', 'rechazada'])) {
            $this->session->flash('error', 'Estado inválido.');
            Response::redirectToRoute('admin/vacaciones');
        }

        $vacacion = $this->vacacionModel->findById($vacacionId);
        if (!$vacacion) {
            $this->session->flash('error', 'Solicitud no encontrada.');
            Response::redirectToRoute('admin/vacaciones');
        }

        $ok = $this->vacacionModel->updateEstado($vacacionId, $estado, $user['id'], $comentario);

        if ($ok) {
            $this->auditService->logVacacion('resuelta', $vacacionId, [
                'estado'    => $estado,
                'comentario' => $comentario,
            ]);

            $this->notificationService->onVacacionResuelta($vacacionId, $vacacion['usuario_id'], $estado);

            $estadoLabel = $estado === 'aprobada' ? 'aprobada' : 'rechazada';
            $this->session->flash('success', "Vacación {$estadoLabel} correctamente.");
        } else {
            $this->session->flash('error', 'No se pudo resolver. Puede que ya esté resuelta.');
        }

        Response::redirectToRoute('admin/vacaciones');
    }

    public function asignar(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/vacaciones');
        }

        $user       = $this->session->getUser();
        $userId     = (int)$request->post('usuario_id', 0);
        $fechaInicio = $request->post('fecha_inicio', '');
        $fechaFin   = $request->post('fecha_fin', '');
        $tipo       = $request->post('tipo', 'normal');
        $comentario = trim($request->post('comentario_admin', ''));

        if (!$userId || empty($fechaInicio) || empty($fechaFin)) {
            $this->session->flash('error', 'Usuario y fechas son obligatorios.');
            Response::redirectToRoute('admin/vacaciones');
        }

        if ($fechaFin < $fechaInicio) {
            $this->session->flash('error', 'La fecha fin debe ser posterior a la de inicio.');
            Response::redirectToRoute('admin/vacaciones');
        }

        $id = $this->vacacionModel->asignar([
            'usuario_id'       => $userId,
            'admin_id'         => $user['id'],
            'fecha_inicio'     => $fechaInicio,
            'fecha_fin'        => $fechaFin,
            'tipo'             => $tipo,
            'comentario_admin' => $comentario ?: 'Asignado por administración',
        ]);

        if ($id) {
            $this->auditService->logVacacion('asignada', $id, [
                'usuario_id' => $userId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'  => $fechaFin,
            ]);
            $this->notificationService->onVacacionResuelta($id, $userId, 'aprobada');
            $this->session->flash('success', 'Vacaciones asignadas correctamente.');
        } else {
            $this->session->flash('error', 'Error al asignar vacaciones.');
        }

        Response::redirectToRoute('admin/vacaciones');
    }
}
