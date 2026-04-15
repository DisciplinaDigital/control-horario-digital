<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Incidencia;
use App\Models\Notificacion;
use App\Models\Configuracion;
use App\Services\AuditService;
use App\Services\NotificationService;

class IncidenciaController
{
    private Incidencia $incidenciaModel;
    private AuditService $auditService;
    private NotificationService $notificationService;
    private Configuracion $config;
    private Session $session;

    public function __construct()
    {
        $this->incidenciaModel     = new Incidencia();
        $this->auditService        = new AuditService();
        $this->notificationService = new NotificationService();
        $this->config              = new Configuracion();
        $this->session             = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user   = $this->session->getUser();
        $userId = $user['id'];

        $incidencias = $this->incidenciaModel->findByUsuario($userId);
        $notifCount  = (new Notificacion())->countUnread($userId);

        Response::view('incidencias/index', [
            'user'        => $user,
            'incidencias' => $incidencias,
            'notifCount'  => $notifCount,
            'theme'       => $this->config->getTheme(),
            'companyName' => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'        => $this->config->get('logo'),
            'pageTitle'   => 'Mis Incidencias',
            'success'     => $this->session->getFlash('success'),
            'error'       => $this->session->getFlash('error'),
        ], 'app');
    }

    public function crear(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('incidencias');
        }

        $user   = $this->session->getUser();
        $userId = $user['id'];

        $tipo          = $request->post('tipo', 'otro');
        $fechaFichaje  = $request->post('fecha_fichaje', '');
        $horaSolicitada = $request->post('hora_solicitada', '');
        $razon         = trim($request->post('razon', ''));

        if (empty($fechaFichaje) || empty($razon)) {
            $this->session->flash('error', 'La fecha y el motivo son obligatorios.');
            Response::redirectToRoute('incidencias');
        }

        if (strlen($razon) < 10) {
            $this->session->flash('error', 'El motivo debe tener al menos 10 caracteres.');
            Response::redirectToRoute('incidencias');
        }

        $id = $this->incidenciaModel->create([
            'usuario_id'     => $userId,
            'tipo'           => $tipo,
            'fecha_fichaje'  => $fechaFichaje,
            'hora_solicitada' => $horaSolicitada ?: null,
            'razon'          => $razon,
        ]);

        if ($id) {
            $incidencia = $this->incidenciaModel->findById($id);
            $this->auditService->logIncidencia('creada', $id, ['tipo' => $tipo]);
            $this->notificationService->onIncidenciaCreada($id, $userId, $incidencia['numero_incidencia']);
            $this->session->flash('success', 'Incidencia creada correctamente. El administrador la revisará en breve.');
        } else {
            $this->session->flash('error', 'Error al crear la incidencia. Inténtalo de nuevo.');
        }

        Response::redirectToRoute('incidencias');
    }

    public function cancelar(Request $request, string $id): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('incidencias');
        }

        $user   = $this->session->getUser();
        $userId = $user['id'];

        $ok = $this->incidenciaModel->cancelar((int)$id, $userId);

        if ($ok) {
            $this->auditService->logIncidencia('cancelada', (int)$id);
            $this->session->flash('success', 'Incidencia cancelada.');
        } else {
            $this->session->flash('error', 'No se pudo cancelar la incidencia.');
        }

        Response::redirectToRoute('incidencias');
    }
}
