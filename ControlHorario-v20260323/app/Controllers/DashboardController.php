<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Fichaje;
use App\Models\Notificacion;
use App\Models\Configuracion;
use App\Services\FichajeService;

class DashboardController
{
    private FichajeService $fichajeService;
    private Fichaje $fichajeModel;
    private Notificacion $notificacionModel;
    private Configuracion $config;
    private Session $session;

    public function __construct()
    {
        $this->fichajeService    = new FichajeService();
        $this->fichajeModel      = new Fichaje();
        $this->notificacionModel = new Notificacion();
        $this->config            = new Configuracion();
        $this->session           = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user   = $this->session->getUser();
        $userId = $user['id'];

        $estado        = $this->fichajeModel->getEstadoActual($userId);
        $resumenDia    = $this->fichajeService->getResumenDia($userId);
        $resumenSemana = $this->fichajeService->getResumenSemana($userId);
        $ultimosFichajes = $this->fichajeModel->getLastNByUsuario($userId, 10);
        $notifCount    = $this->notificacionModel->countUnread($userId);
        $theme         = $this->config->getTheme();
        $companyName   = $this->config->get('company_name', 'Control Horario Digital');
        $logo          = $this->config->get('logo');

        Response::view('dashboard', [
            'user'            => $user,
            'estado'          => $estado,
            'resumenDia'      => $resumenDia,
            'resumenSemana'   => $resumenSemana,
            'ultimosFichajes' => $ultimosFichajes,
            'notifCount'      => $notifCount,
            'theme'           => $theme,
            'companyName'     => $companyName,
            'logo'            => $logo,
            'pageTitle'       => 'Dashboard',
            'officeLat'            => $this->config->get('default_lat', '36.62906'),
            'officeLon'            => $this->config->get('default_lon', '-4.82644'),
            'maxDistance'          => $this->config->get('max_distance', '30'),
            'maxDistanceOverride'  => isset($user['max_distance_override']) && $user['max_distance_override'] !== null
                                        ? (int)$user['max_distance_override']
                                        : null,
            'success'         => $this->session->getFlash('success'),
            'error'           => $this->session->getFlash('error'),
        ], 'app');
    }
}
