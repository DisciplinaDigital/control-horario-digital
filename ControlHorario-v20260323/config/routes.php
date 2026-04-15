<?php

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\ApiMiddleware;

// Auth routes (no middleware)
Router::get('/login', [\App\Controllers\AuthController::class, 'showLogin']);
Router::post('/login', [\App\Controllers\AuthController::class, 'login']);
Router::get('/logout', [\App\Controllers\AuthController::class, 'logout']);
Router::post('/logout', [\App\Controllers\AuthController::class, 'logout']);
Router::get('/forgot-password', [\App\Controllers\AuthController::class, 'showForgotPassword']);
Router::post('/forgot-password', [\App\Controllers\AuthController::class, 'forgotPassword']);
Router::get('/terminos', [\App\Controllers\AuthController::class, 'showTerminos']);
Router::post('/terminos', [\App\Controllers\AuthController::class, 'aceptarTerminos']);
Router::get('/cambiar-password', [\App\Controllers\AuthController::class, 'showCambiarPassword']);
Router::post('/cambiar-password', [\App\Controllers\AuthController::class, 'cambiarPassword']);
Router::get('/reset-password/{token}', [\App\Controllers\AuthController::class, 'showResetPassword']);
Router::post('/reset-password/{token}', [\App\Controllers\AuthController::class, 'resetPassword']);

// =============================================
// User routes (require auth)
// =============================================
Router::group('', function () {

    // Dashboard
    Router::get('/', [\App\Controllers\DashboardController::class, 'index']);
    Router::get('/dashboard', [\App\Controllers\DashboardController::class, 'index']);

    // Fichajes views
    Router::get('/fichajes', [\App\Controllers\FichajeController::class, 'index']);
    Router::get('/fichajes/exportar', [\App\Controllers\FichajeController::class, 'exportar']);
    Router::get('/fichajes/exportar/pdf', [\App\Controllers\FichajeController::class, 'exportarPDF']);

    // Incidencias
    Router::get('/incidencias', [\App\Controllers\IncidenciaController::class, 'index']);
    Router::post('/incidencias', [\App\Controllers\IncidenciaController::class, 'crear']);
    Router::post('/incidencias/{id}/cancelar', [\App\Controllers\IncidenciaController::class, 'cancelar']);

    // Vacaciones
    Router::get('/vacaciones', [\App\Controllers\VacacionController::class, 'index']);
    Router::post('/vacaciones', [\App\Controllers\VacacionController::class, 'crear']);
    Router::post('/vacaciones/{id}/cancelar', [\App\Controllers\VacacionController::class, 'cancelar']);

    // Perfil
    Router::get('/perfil', [\App\Controllers\PerfilController::class, 'index']);
    Router::post('/perfil', [\App\Controllers\PerfilController::class, 'actualizar']);
    Router::post('/perfil/password', [\App\Controllers\PerfilController::class, 'cambiarPassword']);

    // ── API endpoints (requieren auth + cabecera AJAX) ───────────
    Router::group('', function () {

        // Fichajes API
        Router::post('/api/fichajes', [\App\Controllers\FichajeController::class, 'registrar']);
        Router::get('/api/fichajes/estado', [\App\Controllers\FichajeController::class, 'estado']);
        Router::get('/api/fichajes/ultimos', [\App\Controllers\FichajeController::class, 'ultimos']);

        // Notificaciones API
        Router::get('/api/notificaciones/count', [\App\Controllers\FichajeController::class, 'notifCount']);
        Router::get('/api/notificaciones', [\App\Controllers\FichajeController::class, 'notificaciones']);
        Router::post('/api/notificaciones/{id}/leer', [\App\Controllers\FichajeController::class, 'marcarLeida']);
        Router::post('/api/notificaciones/leer-todas', [\App\Controllers\FichajeController::class, 'marcarTodasLeidas']);

        // Vacaciones API
        Router::get('/api/vacaciones/no-disponibles', [\App\Controllers\VacacionController::class, 'diasNoDisponibles']);

    }, [ApiMiddleware::class]);

}, [AuthMiddleware::class]);

// =============================================
// Admin routes (require auth + admin role)
// =============================================
Router::group('/admin', function () {

    // Admin dashboard (HTML)
    Router::get('', [\App\Controllers\Admin\DashboardController::class, 'index']);

    // Admin dashboard API
    Router::group('', function () {
        Router::get('/api/stats', [\App\Controllers\Admin\DashboardController::class, 'apiStats']);
    }, [ApiMiddleware::class]);

    // Admin users
    Router::get('/usuarios', [\App\Controllers\Admin\UsuarioController::class, 'index']);
    Router::post('/usuarios', [\App\Controllers\Admin\UsuarioController::class, 'store']);
    Router::post('/usuarios/{id}', [\App\Controllers\Admin\UsuarioController::class, 'update']);
    Router::post('/usuarios/{id}/eliminar', [\App\Controllers\Admin\UsuarioController::class, 'delete']);
    Router::post('/usuarios/{id}/reactivar', [\App\Controllers\Admin\UsuarioController::class, 'reactivar']);
    Router::post('/usuarios/{id}/reset-password', [\App\Controllers\Admin\UsuarioController::class, 'resetPassword']);

    // Admin fichajes
    Router::get('/fichajes', [\App\Controllers\Admin\FichajeController::class, 'index']);
    Router::get('/fichajes/integridad', [\App\Controllers\Admin\FichajeController::class, 'verificarIntegridad']);
    Router::get('/fichajes/exportar', [\App\Controllers\Admin\FichajeController::class, 'exportar']);

    // Admin incidencias
    Router::get('/incidencias', [\App\Controllers\Admin\IncidenciaController::class, 'index']);
    Router::post('/incidencias/{id}/resolver', [\App\Controllers\Admin\IncidenciaController::class, 'resolver']);

    // Admin vacaciones
    Router::get('/vacaciones', [\App\Controllers\Admin\VacacionController::class, 'index']);
    Router::post('/vacaciones/{id}/resolver', [\App\Controllers\Admin\VacacionController::class, 'resolver']);
    Router::post('/vacaciones/asignar', [\App\Controllers\Admin\VacacionController::class, 'asignar']);

    // Admin festivos
    Router::get('/festivos', [\App\Controllers\Admin\FestivoController::class, 'index']);
    Router::post('/festivos', [\App\Controllers\Admin\FestivoController::class, 'store']);
    Router::post('/festivos/{id}/eliminar', [\App\Controllers\Admin\FestivoController::class, 'delete']);
    Router::post('/festivos/importar', [\App\Controllers\Admin\FestivoController::class, 'importarNacionales']);

    // Admin configuracion
    Router::get('/configuracion', [\App\Controllers\Admin\ConfiguracionController::class, 'index']);
    Router::post('/configuracion', [\App\Controllers\Admin\ConfiguracionController::class, 'update']);
    Router::post('/configuracion/logo', [\App\Controllers\Admin\ConfiguracionController::class, 'uploadLogo']);
    Router::post('/configuracion/logo/eliminar', [\App\Controllers\Admin\ConfiguracionController::class, 'deleteLogo']);

}, [AdminMiddleware::class]);
