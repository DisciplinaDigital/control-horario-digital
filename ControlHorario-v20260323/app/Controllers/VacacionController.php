<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Vacacion;
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
        $user   = $this->session->getUser();
        $userId = $user['id'];
        $year   = (int)$request->get('year', date('Y'));

        $vacaciones = $this->vacacionModel->findByUsuario($userId, $year);
        $diasInfo   = $this->vacacionModel->getDiasInfo($userId, $year);
        $notifCount = (new Notificacion())->countUnread($userId);

        Response::view('vacaciones/index', [
            'user'        => $user,
            'vacaciones'  => $vacaciones,
            'diasInfo'    => $diasInfo,
            'year'        => $year,
            'notifCount'  => $notifCount,
            'theme'       => $this->config->getTheme(),
            'companyName' => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'        => $this->config->get('logo'),
            'pageTitle'   => 'Mis Vacaciones',
            'success'     => $this->session->getFlash('success'),
            'error'       => $this->session->getFlash('error'),
        ], 'app');
    }

    public function crear(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('vacaciones');
        }

        $user   = $this->session->getUser();
        $userId = $user['id'];

        $fechaInicio = $request->post('fecha_inicio', '');
        $fechaFin    = $request->post('fecha_fin', '');
        $tipo        = $request->post('tipo', 'normal');
        $comentario  = trim($request->post('comentario', ''));

        // Validate
        if (empty($fechaInicio) || empty($fechaFin)) {
            $this->session->flash('error', 'Las fechas de inicio y fin son obligatorias.');
            Response::redirectToRoute('vacaciones');
        }

        if ($fechaFin < $fechaInicio) {
            $this->session->flash('error', 'La fecha de fin debe ser posterior a la de inicio.');
            Response::redirectToRoute('vacaciones');
        }

        if ($fechaInicio < date('Y-m-d')) {
            $this->session->flash('error', 'No puedes solicitar vacaciones en fechas pasadas.');
            Response::redirectToRoute('vacaciones');
        }

        // Check for overlapping requests
        if (!$this->vacacionModel->isDateAvailable($userId, $fechaInicio)) {
            $this->session->flash('error', 'Ya tienes una solicitud de vacaciones en esas fechas.');
            Response::redirectToRoute('vacaciones');
        }

        $id = $this->vacacionModel->create([
            'usuario_id'         => $userId,
            'fecha_inicio'       => $fechaInicio,
            'fecha_fin'          => $fechaFin,
            'tipo'               => $tipo,
            'comentario_usuario' => $comentario ?: null,
        ]);

        if ($id) {
            $this->auditService->logVacacion('solicitada', $id, [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
            ]);
            $this->notificationService->onVacacionSolicitada($id, $userId, $fechaInicio, $fechaFin);
            $this->session->flash('success', 'Solicitud de vacaciones enviada. Pendiente de aprobación.');
        } else {
            $this->session->flash('error', 'Error al crear la solicitud de vacaciones.');
        }

        Response::redirectToRoute('vacaciones');
    }

    public function cancelar(Request $request, string $id): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('vacaciones');
        }

        $user   = $this->session->getUser();
        $userId = $user['id'];

        $ok = $this->vacacionModel->cancelar((int)$id, $userId);

        if ($ok) {
            $this->auditService->logVacacion('cancelada', (int)$id);
            $this->session->flash('success', 'Solicitud de vacaciones cancelada.');
        } else {
            $this->session->flash('error', 'No se pudo cancelar la solicitud.');
        }

        Response::redirectToRoute('vacaciones');
    }

    /**
     * GET /api/vacaciones/no-disponibles - for calendar (JSON)
     */
    public function diasNoDisponibles(Request $request): void
    {
        $user   = $this->session->getUser();
        $userId = $user['id'];
        $year   = (int)$request->get('year', date('Y'));

        $noDisponibles = $this->vacacionModel->getNoDisponibles($userId, $year);

        Response::json(['success' => true, 'data' => $noDisponibles]);
    }
}
