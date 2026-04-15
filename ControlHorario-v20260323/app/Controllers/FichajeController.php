<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Fichaje;
use App\Models\Notificacion;
use App\Models\Configuracion;
use App\Services\FichajeService;
use App\Services\ExportService;

class FichajeController
{
    private FichajeService $fichajeService;
    private Fichaje $fichajeModel;
    private ExportService $exportService;
    private Configuracion $config;
    private Session $session;

    public function __construct()
    {
        $this->fichajeService = new FichajeService();
        $this->fichajeModel   = new Fichaje();
        $this->exportService  = new ExportService();
        $this->config         = new Configuracion();
        $this->session        = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user   = $this->session->getUser();
        $userId = $user['id'];

        $filters = [
            'fecha_desde' => $request->get('fecha_desde', date('Y-m-01')),
            'fecha_hasta' => $request->get('fecha_hasta', date('Y-m-d')),
            'tipo'        => $request->get('tipo', ''),
            'limit'       => 200,
        ];

        $fichajes  = $this->fichajeModel->findByUsuario($userId, $filters);
        $notifCount = (new Notificacion())->countUnread($userId);

        Response::view('fichajes/index', [
            'user'        => $user,
            'fichajes'    => $fichajes,
            'filters'     => $filters,
            'notifCount'  => $notifCount,
            'theme'       => $this->config->getTheme(),
            'companyName' => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'        => $this->config->get('logo'),
            'pageTitle'   => 'Mis Fichajes',
        ], 'app');
    }

    /**
     * POST /api/fichajes - Register entry/exit via JSON API
     */
    public function registrar(Request $request): void
    {
        if (!$request->validateCsrf()) {
            Response::json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $user   = $this->session->getUser();
        $userId = $user['id'];

        $tipo      = $request->post('tipo', '');
        $rawLat    = $request->post('lat', '');
        $rawLon    = $request->post('lon', '');
        $rawPrec   = $request->post('precision', '');
        // Tratar cadena vacía como null (GPS no disponible), no como 0.0
        $lat       = ($rawLat !== '' && $rawLat !== null) ? (float)$rawLat : null;
        $lon       = ($rawLon !== '' && $rawLon !== null) ? (float)$rawLon : null;
        $precision = ($rawPrec !== '' && $rawPrec !== null) ? (float)$rawPrec : null;

        if (empty($tipo)) {
            Response::json(['success' => false, 'message' => 'Tipo de fichaje requerido'], 400);
        }

        // Pasar override personal de distancia (NULL = usar global)
        $userMaxDist = isset($user['max_distance_override']) && $user['max_distance_override'] !== null
            ? (int)$user['max_distance_override']
            : null;

        $result = $this->fichajeService->registrar($userId, $tipo, $lat, $lon, $precision, 'web', null, $userMaxDist);

        Response::json($result, $result['success'] ? 200 : 400);
    }

    /**
     * GET /api/fichajes/estado - Current status (JSON)
     */
    public function estado(Request $request): void
    {
        $user   = $this->session->getUser();
        $userId = $user['id'];

        $estado   = $this->fichajeModel->getEstadoActual($userId);
        $canCheck = $this->fichajeService->canFichar($userId);
        $resumen  = $this->fichajeService->getResumenDia($userId);

        Response::json([
            'success'         => true,
            'estado'          => $estado,
            'tipo_disponible' => $canCheck['tipo'],
            'horas_hoy'       => $resumen['horas_trabajadas'],
            'primera_entrada' => $resumen['primera_entrada'],
            'ultima_salida'   => $resumen['ultima_salida'],
        ]);
    }

    /**
     * GET /api/fichajes/ultimos - Last N entries (JSON)
     */
    public function ultimos(Request $request): void
    {
        $user   = $this->session->getUser();
        $userId = $user['id'];
        $n      = min(20, (int)$request->get('n', 10));

        $fichajes = $this->fichajeModel->getLastNByUsuario($userId, $n);

        Response::json(['success' => true, 'data' => $fichajes]);
    }

    /**
     * GET /fichajes/exportar - CSV download
     */
    public function exportar(Request $request): void
    {
        $user   = $this->session->getUser();
        $userId = $user['id'];

        $from = $request->get('fecha_desde', date('Y-m-01'));
        $to   = $request->get('fecha_hasta', date('Y-m-d'));

        $csv = $this->exportService->exportFichajesCSV($userId, $from, $to);

        $filename = "fichajes_{$user['apellidos']}_{$from}_{$to}.csv";

        // Add BOM for Excel compatibility with UTF-8
        Response::download("\xEF\xBB\xBF" . $csv, $filename, 'text/csv; charset=utf-8');
    }

    /**
     * GET /fichajes/exportar/pdf - Print-friendly HTML
     */
    public function exportarPDF(Request $request): void
    {
        $user   = $this->session->getUser();
        $userId = $user['id'];

        $from = $request->get('fecha_desde', date('Y-m-01'));
        $to   = $request->get('fecha_hasta', date('Y-m-d'));

        $this->exportService->exportFichajesPDF($userId, $from, $to);
    }

    /**
     * GET /api/notificaciones/count
     */
    public function notifCount(Request $request): void
    {
        $user  = $this->session->getUser();
        $count = (new Notificacion())->countUnread($user['id']);
        Response::json(['success' => true, 'count' => $count]);
    }

    /**
     * GET /api/notificaciones
     */
    public function notificaciones(Request $request): void
    {
        $user   = $this->session->getUser();
        $notifs = (new Notificacion())->findByUsuario($user['id']);
        Response::json(['success' => true, 'data' => $notifs]);
    }

    /**
     * POST /api/notificaciones/{id}/leer
     */
    public function marcarLeida(Request $request, string $id): void
    {
        $notifModel = new Notificacion();
        $notifModel->markRead((int)$id);
        Response::json(['success' => true]);
    }

    /**
     * POST /api/notificaciones/leer-todas
     */
    public function marcarTodasLeidas(Request $request): void
    {
        $user = $this->session->getUser();
        (new Notificacion())->markAllRead($user['id']);
        Response::json(['success' => true]);
    }
}
