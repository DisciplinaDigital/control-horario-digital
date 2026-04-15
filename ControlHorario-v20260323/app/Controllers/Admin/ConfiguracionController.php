<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Configuracion;
use App\Models\Notificacion;
use App\Services\AuditService;

class ConfiguracionController
{
    private Configuracion $config;
    private AuditService $auditService;
    private Session $session;

    public function __construct()
    {
        $this->config       = new Configuracion();
        $this->auditService = new AuditService();
        $this->session      = Session::getInstance();
    }

    public function index(Request $request): void
    {
        $user       = $this->session->getUser();
        $all        = $this->config->getAll();
        $defaults   = $this->config->defaults();
        $notifCount = (new Notificacion())->countUnread($user['id']);

        // Merge with defaults for any missing keys
        foreach ($defaults as $key => $default) {
            if (!isset($all[$key])) {
                $all[$key] = ['clave' => $key, 'valor' => $default, 'tipo' => 'text'];
            }
        }

        Response::view('admin/configuracion/index', [
            'user'        => $user,
            'config'      => $all,
            'notifCount'  => $notifCount,
            'theme'       => $this->config->getTheme(),
            'companyName' => $this->config->get('company_name', 'Control Horario Digital'),
            'logo'        => $this->config->get('logo'),
            'pageTitle'   => 'Configuración del Sistema',
            'success'     => $this->session->getFlash('success'),
            'error'       => $this->session->getFlash('error'),
        ], 'admin');
    }

    public function update(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/configuracion');
        }

        $allowed = [
            'company_name', 'company_address', 'company_cif',
            'color_primary', 'color_secondary', 'color_accent',
            'color_success', 'color_warning', 'color_danger',
            'color_bg', 'color_text',
            'default_lat', 'default_lon', 'max_distance', 'min_accuracy',
            'default_vacation_days', 'work_start', 'work_end',
        ];

        $data = [];
        foreach ($allowed as $key) {
            $value = $request->post($key, '');
            if ($value !== '') {
                $data[$key] = $value;
            }
        }

        // Validate colors (must be valid hex colors)
        $colorKeys = ['color_primary', 'color_secondary', 'color_accent', 'color_success', 'color_warning', 'color_danger', 'color_bg', 'color_text'];
        foreach ($colorKeys as $key) {
            if (isset($data[$key]) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data[$key])) {
                $this->session->flash('error', "Color inválido para {$key}.");
                Response::redirectToRoute('admin/configuracion');
            }
        }

        $this->config->setMultiple($data);
        $this->auditService->log('configuracion.actualizada', 'configuracion', null, null, $data);

        $this->session->flash('success', 'Configuración guardada correctamente.');
        Response::redirectToRoute('admin/configuracion');
    }

    public function uploadLogo(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/configuracion');
        }

        $file = $request->file('logo');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->session->flash('error', 'Error al subir el archivo.');
            Response::redirectToRoute('admin/configuracion');
        }

        // Validate file type
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'];
        $finfo        = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType     = $finfo->file($file['tmp_name']);

        // For SVG, also check the extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($mimeType, $allowedTypes) && !($ext === 'svg')) {
            $this->session->flash('error', 'Tipo de archivo no permitido. Solo PNG, JPG o SVG.');
            Response::redirectToRoute('admin/configuracion');
        }

        // Validate size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->session->flash('error', 'El archivo es demasiado grande. Máximo 2MB.');
            Response::redirectToRoute('admin/configuracion');
        }

        // Delete old logo
        $oldLogo = $this->config->get('logo');
        if ($oldLogo) {
            $oldPath = BASE_PATH . '/public/assets/uploads/' . $oldLogo;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Garantizar que el directorio existe
        $uploadsDir = BASE_PATH . '/public/assets/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        // Save new logo
        $newFilename = 'logo_' . time() . '.' . $ext;
        $uploadPath  = $uploadsDir . '/' . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $this->config->set('logo', $newFilename, 'file');
            $this->auditService->log('configuracion.logo_subido', 'configuracion', null, null, ['filename' => $newFilename]);
            $this->session->flash('success', 'Logo actualizado correctamente.');
        } else {
            $this->session->flash('error', 'Error al guardar el archivo.');
        }

        Response::redirectToRoute('admin/configuracion');
    }

    public function deleteLogo(Request $request): void
    {
        if (!$request->validateCsrf()) {
            $this->session->flash('error', 'Token de seguridad inválido.');
            Response::redirectToRoute('admin/configuracion');
        }

        $logo = $this->config->get('logo');

        if ($logo) {
            $path = BASE_PATH . '/public/assets/uploads/' . $logo;
            if (file_exists($path)) {
                unlink($path);
            }
            $this->config->set('logo', null, 'file');
            $this->auditService->log('configuracion.logo_eliminado', 'configuracion');
            $this->session->flash('success', 'Logo eliminado correctamente.');
        } else {
            $this->session->flash('error', 'No hay logo que eliminar.');
        }

        Response::redirectToRoute('admin/configuracion');
    }
}
