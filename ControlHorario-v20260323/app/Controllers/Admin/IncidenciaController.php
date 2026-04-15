<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Incidencia;
use App\Models\Usuario;
use App\Models\Notificacion;
use App\Models\Configuracion;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\FichajeService;

class IncidenciaController
{
    private Incidencia $incidenciaModel;
    private AuditService $auditService;
    private NotificationService $notificationService;
    private FichajeService $fichajeService;
    private Configuracion $config;
    private Session $session;

    public function __construct()
    {
        $this->incidenciaModel     = new Incidencia();
        $this->auditService        = new AuditService();
        $this->notificationService = new NotificationService();
        $this->fichajeService      = new FichajeService();
        $this->config              = new Configuracion();
        $this->session             = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user = $this->session->getUser();

        $filters = [
            'estado'     => $request->get('estado', ''),
            'usuario_id' => $request->get('usuario_id', ''),
        ];

        $incidencias = $this->incidenciaModel->findAll($filters);
        $usuarios    = (new Usuario())->all();
        $notifCount  = (new Notificacion())->countUnread($user['id']);

        Response::view('admin/incidencias/index', [
            'user'        => $user,
            'incidencias' => $incidencias,
            'usuarios'    => $usuarios,
            'filters'     => $filters,
            'notifCount'  => $notifCount,
            'theme'       => $this->config->getTheme(),
            'companyName' => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'        => $this->config->get('logo'),
            'pageTitle'   => 'Gestión de Incidencias',
            'success'     => $this->session->getFlash('success'),
            'error'       => $this->session->getFlash('error'),
        ], 'admin');
    }

    public function resolver(Request $request, string $id): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/incidencias');
        }

        $user         = $this->session->getUser();
        $incidenciaId = (int)$id;

        $estado    = $request->post('estado', '');
        $comentario = trim($request->post('comentario', ''));

        if (!in_array($estado, ['aceptada', 'rechazada'])) {
            $this->session->flash('error', 'Estado inválido.');
            Response::redirectToRoute('admin/incidencias');
        }

        $incidencia = $this->incidenciaModel->findById($incidenciaId);
        if (!$incidencia) {
            $this->session->flash('error', 'Incidencia no encontrada.');
            Response::redirectToRoute('admin/incidencias');
        }

        $ok = $this->incidenciaModel->updateEstado($incidenciaId, $estado, $user['id'], $comentario);

        if ($ok) {
            // If accepted and has correction, create corrective fichaje
            if ($estado === 'aceptada' && $incidencia['hora_solicitada']) {
                $fechaHora = $incidencia['fecha_fichaje'] . ' ' . $incidencia['hora_solicitada'];
                $tipo      = str_contains($incidencia['tipo'], 'entrada') ? 'entrada' : 'salida';

                $this->fichajeService->registrar(
                    $incidencia['usuario_id'],
                    $tipo,
                    null, null, null,
                    'manual_admin',
                    "Corrección por incidencia {$incidencia['numero_incidencia']}: {$comentario}",
                    null,
                    $fechaHora,
                    $incidenciaId
                );
            }

            $this->auditService->logIncidencia('resuelta', $incidenciaId, [
                'estado'    => $estado,
                'comentario' => $comentario,
            ]);

            $this->notificationService->onIncidenciaResuelta(
                $incidenciaId,
                $incidencia['usuario_id'],
                $estado,
                $incidencia['numero_incidencia']
            );

            $estadoLabel = $estado === 'aceptada' ? 'aceptada' : 'rechazada';
            $this->session->flash('success', "Incidencia {$estadoLabel} correctamente.");
        } else {
            $this->session->flash('error', 'No se pudo resolver la incidencia. Puede que ya esté resuelta.');
        }

        Response::redirectToRoute('admin/incidencias');
    }
}
