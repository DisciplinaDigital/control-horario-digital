<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Usuario;
use App\Models\Fichaje;
use App\Models\Incidencia;
use App\Models\Vacacion;
use App\Models\Notificacion;
use App\Models\Configuracion;

class DashboardController
{
    private Configuracion $config;
    private Session $session;

    public function __construct()
    {
        $this->config  = new Configuracion();
        $this->session = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user = $this->session->getUser();

        $usuarioModel   = new Usuario();
        $fichajeModel   = new Fichaje();
        $incidenciaModel = new Incidencia();
        $vacacionModel  = new Vacacion();

        $stats = [
            'usuarios_activos_hoy' => $usuarioModel->getActiveToday(),
            'incidencias_pendientes' => $incidenciaModel->countPendientes(),
            'fichajes_hoy'           => $fichajeModel->countToday(),
            'total_usuarios'         => $usuarioModel->count(['role' => 'usuario']),
            'vacaciones_pendientes'  => $vacacionModel->countPendientes(),
        ];

        $incidenciasPendientes = $incidenciaModel->findAll(['estado' => 'pendiente', 'limit' => 10]);
        $ultimosFichajes       = $fichajeModel->getAllWithFilters([
            'fecha_desde' => date('Y-m-d'),
            'limit'       => 20,
        ]);

        $notifCount = (new Notificacion())->countUnread($user['id']);

        Response::view('admin/dashboard', [
            'user'                   => $user,
            'stats'                  => $stats,
            'incidenciasPendientes'  => $incidenciasPendientes,
            'ultimosFichajes'        => $ultimosFichajes,
            'notifCount'             => $notifCount,
            'theme'                  => $this->config->getTheme(),
            'companyName'            => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'                   => $this->config->get('logo'),
            'pageTitle'              => 'Panel de Administración',
        ], 'admin');
    }

    public function apiStats(Request $request): void
    {
        $fichajeModel    = new Fichaje();
        $incidenciaModel = new Incidencia();
        $vacacionModel   = new Vacacion();
        $usuarioModel    = new Usuario();

        Response::json([
            'success' => true,
            'data'    => [
                'usuarios_activos_hoy'   => $usuarioModel->getActiveToday(),
                'incidencias_pendientes' => $incidenciaModel->countPendientes(),
                'fichajes_hoy'           => $fichajeModel->countToday(),
                'total_usuarios'         => $usuarioModel->count(['role' => 'usuario']),
                'vacaciones_pendientes'  => $vacacionModel->countPendientes(),
                'timestamp'              => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
