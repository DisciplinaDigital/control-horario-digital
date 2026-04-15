<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Festivo;
use App\Models\Notificacion;
use App\Models\Configuracion;
use App\Services\AuditService;

class FestivoController
{
    private Festivo $festivoModel;
    private AuditService $auditService;
    private Configuracion $config;
    private Session $session;

    public function __construct()
    {
        $this->festivoModel = new Festivo();
        $this->auditService = new AuditService();
        $this->config       = new Configuracion();
        $this->session      = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user = $this->session->getUser();
        $year = (int)$request->get('year', date('Y'));

        $festivos   = $this->festivoModel->findAll($year);
        $notifCount = (new Notificacion())->countUnread($user['id']);

        Response::view('admin/festivos/index', [
            'user'        => $user,
            'festivos'    => $festivos,
            'year'        => $year,
            'notifCount'  => $notifCount,
            'theme'       => $this->config->getTheme(),
            'companyName' => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'        => $this->config->get('logo'),
            'pageTitle'   => 'Gestión de Festivos',
            'success'     => $this->session->getFlash('success'),
            'error'       => $this->session->getFlash('error'),
        ], 'admin');
    }

    public function store(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/festivos');
        }

        $fecha       = $request->post('fecha', '');
        $descripcion = trim($request->post('descripcion', ''));
        $tipo        = $request->post('tipo', 'nacional');
        $year        = date('Y', strtotime($fecha));

        if (empty($fecha) || empty($descripcion)) {
            $this->session->flash('error', 'La fecha y descripción son obligatorias.');
            Response::redirectToRoute("admin/festivos?year={$year}");
        }

        if (!in_array($tipo, ['nacional', 'autonomico', 'local', 'empresa'])) {
            $tipo = 'nacional';
        }

        $this->festivoModel->create([
            'fecha'       => $fecha,
            'descripcion' => $descripcion,
            'tipo'        => $tipo,
        ]);

        $this->auditService->log('festivo.creado', 'festivos', null, null, compact('fecha', 'descripcion', 'tipo'));
        $this->session->flash('success', "Festivo '{$descripcion}' añadido correctamente.");

        Response::redirectToRoute("admin/festivos?year={$year}");
    }

    public function delete(Request $request, string $id): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/festivos');
        }

        $festivoId = (int)$id;
        $year      = $request->post('year', date('Y'));

        $ok = $this->festivoModel->delete($festivoId);

        if ($ok) {
            $this->auditService->log('festivo.eliminado', 'festivos', $festivoId);
            $this->session->flash('success', 'Festivo eliminado correctamente.');
        } else {
            $this->session->flash('error', 'Error al eliminar el festivo.');
        }

        Response::redirectToRoute("admin/festivos?year={$year}");
    }

    public function importarNacionales(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/festivos');
        }

        $year = (int)$request->post('year', date('Y'));

        if ($year < 2020 || $year > 2035) {
            $this->session->flash('error', 'Año inválido.');
            Response::redirectToRoute('admin/festivos');
        }

        $imported = $this->festivoModel->importarNacionalesEspana($year);

        $this->auditService->log('festivos.importados', 'festivos', null, null, [
            'year'     => $year,
            'imported' => $imported,
        ]);

        $this->session->flash('success', "{$imported} festivos nacionales importados para {$year}.");
        Response::redirectToRoute("admin/festivos?year={$year}");
    }
}
