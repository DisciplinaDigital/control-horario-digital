<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Error del Servidor</title>
    <link rel="stylesheet" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .error-page { text-align: center; padding: 40px; }
        .error-code { font-size: 8rem; font-weight: 900; color: #e2e8f0; line-height: 1; }
        .error-icon { font-size: 4rem; color: #d97706; }
    </style>
</head>
<body>
<div class="error-page">
    <div class="error-code">500</div>
    <i class="bi bi-exclamation-triangle error-icon"></i>
    <h1 class="h3 mt-3">Error Interno del Servidor</h1>
    <p class="text-muted">Ha ocurrido un error inesperado. El equipo técnico ha sido notificado.</p>
    <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/" class="btn btn-primary mt-3">
        <i class="bi bi-house me-2"></i> Volver al inicio
    </a>
</div>
</body>
</html>
