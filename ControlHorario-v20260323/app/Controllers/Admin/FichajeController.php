<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Fichaje;
use App\Models\Usuario;
use App\Models\Notificacion;
use App\Models\Configuracion;
use App\Services\IntegrityService;
use App\Services\ExportService;
use App\Services\AuditService;

class FichajeController
{
    private Fichaje $fichajeModel;
    private IntegrityService $integrityService;
    private ExportService $exportService;
    private AuditService $auditService;
    private Configuracion $config;
    private Session $session;

    public function __construct()
    {
        $this->fichajeModel     = new Fichaje();
        $this->integrityService = new IntegrityService();
        $this->exportService    = new ExportService();
        $this->auditService     = new AuditService();
        $this->config           = new Configuracion();
        $this->session          = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user = $this->session->getUser();

        $filters = [
            'usuario_id'  => $request->get('usuario_id', ''),
            'fecha_desde' => $request->get('fecha_desde', date('Y-m-d')),
            'fecha_hasta' => $request->get('fecha_hasta', date('Y-m-d')),
            'tipo'        => $request->get('tipo', ''),
            'limit'       => 200,
        ];

        $fichajes  = $this->fichajeModel->getAllWithFilters($filters);
        $usuarios  = (new Usuario())->all(['role' => 'usuario']);
        $notifCount = (new Notificacion())->countUnread($user['id']);

        Response::view('admin/fichajes/index', [
            'user'        => $user,
            'fichajes'    => $fichajes,
            'usuarios'    => $usuarios,
            'filters'     => $filters,
            'notifCount'  => $notifCount,
            'theme'       => $this->config->getTheme(),
            'companyName' => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'        => $this->config->get('logo'),
            'pageTitle'   => 'Gestión de Fichajes',
        ], 'admin');
    }

    public function verificarIntegridad(Request $request): void
    {
        $user = $this->session->getUser();

        $report = $this->integrityService->generateVerificationReport();
        $notifCount = (new Notificacion())->countUnread($user['id']);
        $format = $request->get('format', '');

        $this->auditService->log('integridad.verificacion', 'fichajes', null, null, [
            'total_users'   => $report['total_users'],
            'valid_users'   => $report['valid_users'],
            'total_records' => $report['total_records'],
            'format'        => $format ?: 'html',
        ]);

        if ($format === 'pdf') {
            $this->exportService->exportIntegridadPDF($report);
            return;
        }

        Response::view('admin/fichajes/integridad', [
            'user'        => $user,
            'report'      => $report,
            'notifCount'  => $notifCount,
            'theme'       => $this->config->getTheme(),
            'companyName' => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'        => $this->config->get('logo'),
            'pageTitle'   => 'Verificación de Integridad',
        ], 'admin');
    }

    public function exportar(Request $request): void
    {
        $filters = [
            'usuario_id'  => $request->get('usuario_id', ''),
            'fecha_desde' => $request->get('fecha_desde', date('Y-m-01')),
            'fecha_hasta' => $request->get('fecha_hasta', date('Y-m-d')),
        ];
        $format = $request->get('format', 'csv');

        $this->auditService->log('fichajes.exportados', 'fichajes', null, null, array_merge($filters, ['format' => $format]));

        if ($format === 'pdf') {
            $this->exportService->exportInspeccionPDF($filters);
            return;
        }

        $csv      = $this->exportService->exportInspeccionCSV($filters);
        $filename = 'registro_jornada_inspeccion_' . date('Ymd_His') . '.csv';

        Response::download("\xEF\xBB\xBF" . $csv, $filename, 'text/csv; charset=utf-8');
    }
}
