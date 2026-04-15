<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Control Horario') ?> - <?= htmlspecialchars($companyName ?? 'Control Horario Digital') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/favicon.svg">
    <link rel="stylesheet" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/css/app.css">
    <?php
    // Dynamic theme from database
    $configModel = new \App\Models\Configuracion();
    $theme = $configModel->getTheme();
    if (!empty($theme)) {
        echo '<style>:root{';
        foreach ($theme as $var => $value) {
            echo htmlspecialchars($var) . ':' . htmlspecialchars($value) . ';';
        }
        echo '}</style>';
    }
    $logoFile    = $configModel->get('logo');
    $companyName = $companyName ?? $configModel->get('company_name', 'Control Horario Digital');
    ?>
</head>
<body class="auth-body">
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <?php if ($logoFile): ?>
                <img src="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/uploads/<?= htmlspecialchars($logoFile) ?>" alt="Logo" class="auth-logo-img">
            <?php else: ?>
                <div class="auth-logo-text">
                    <i class="bi bi-clock-history"></i>
                    <span><?= htmlspecialchars($companyName) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?= $content ?>

    </div>
    <p class="auth-footer">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($companyName) ?> &mdash;
        Sistema de Control Horario Digital<br>
        <small>Conforme a la Ley de Registro de Jornada (RD-Ley 8/2019) &mdash;
        <a href="https://www.disciplinadigital.es" target="_blank" rel="noopener">www.disciplinadigital.es</a></small>
    </p>
</div>
<script src="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
