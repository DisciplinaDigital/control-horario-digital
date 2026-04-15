<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> - <?= htmlspecialchars($companyName ?? 'Control Horario') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/favicon.svg">
    <link rel="stylesheet" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/css/app.css">
    <?php if (!empty($theme)): ?>
    <style>
        :root {
            <?php foreach ($theme as $var => $value): ?>
            <?= htmlspecialchars($var) ?>: <?= htmlspecialchars($value) ?>;
            <?php endforeach; ?>
        }
    </style>
    <?php endif; ?>
    <meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
    <meta name="app-url" content="<?= htmlspecialchars(rtrim($_ENV['APP_URL'] ?? '', '/')) ?>">
</head>
<body class="admin-body">

<!-- Overlay para cerrar sidebar en móvil tocando fuera -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Admin Layout: Sidebar + Content -->
<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <?php if (!empty($logo)): ?>
                <img src="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/uploads/<?= htmlspecialchars($logo) ?>" alt="Logo" class="sidebar-logo">
            <?php else: ?>
                <div class="sidebar-brand">
                    <i class="bi bi-clock-history"></i>
                    <span><?= htmlspecialchars($companyName ?? 'Control Horario') ?></span>
                </div>
            <?php endif; ?>
            <small class="sidebar-subtitle">Panel de Administración</small>
        </div>

        <nav class="sidebar-nav">
            <?php
            $appUrl  = rtrim($_ENV['APP_URL'] ?? '', '/');
            $current = $_SERVER['REQUEST_URI'] ?? '';
            function isActive(string $path, string $current): string {
                return str_contains($current, $path) ? 'active' : '';
            }
            ?>
            <a href="<?= $appUrl ?>/admin" class="sidebar-link <?= isActive('/admin', $current) && !str_contains($current, '/admin/') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
            <a href="<?= $appUrl ?>/admin/usuarios" class="sidebar-link <?= isActive('/admin/usuarios', $current) ?>">
                <i class="bi bi-people"></i> <span>Usuarios</span>
            </a>
            <a href="<?= $appUrl ?>/admin/fichajes" class="sidebar-link <?= isActive('/admin/fichajes', $current) ?>">
                <i class="bi bi-clock"></i> <span>Fichajes</span>
            </a>
            <a href="<?= $appUrl ?>/admin/incidencias" class="sidebar-link <?= isActive('/admin/incidencias', $current) ?>">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Incidencias</span>
                <?php if (!empty($pendingIncidencias)): ?>
                    <span class="badge bg-danger ms-auto"><?= $pendingIncidencias ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= $appUrl ?>/admin/vacaciones" class="sidebar-link <?= isActive('/admin/vacaciones', $current) ?>">
                <i class="bi bi-calendar-week"></i> <span>Vacaciones</span>
            </a>
            <a href="<?= $appUrl ?>/admin/festivos" class="sidebar-link <?= isActive('/admin/festivos', $current) ?>">
                <i class="bi bi-calendar-event"></i> <span>Festivos</span>
            </a>
            <a href="<?= $appUrl ?>/admin/configuracion" class="sidebar-link <?= isActive('/admin/configuracion', $current) ?>">
                <i class="bi bi-gear"></i> <span>Configuración</span>
            </a>

            <div class="sidebar-divider"></div>

            <a href="<?= $appUrl ?>/" class="sidebar-link">
                <i class="bi bi-house"></i> <span>Área Empleado</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="admin-content">

        <!-- Top bar -->
        <header class="admin-topbar">
            <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list fs-4"></i>
            </button>

            <div class="ms-auto d-flex align-items-center gap-3">
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn btn-link position-relative" id="notifDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notif-badge badge rounded-pill <?= ($notifCount ?? 0) > 0 ? '' : 'd-none' ?>">
                            <?= (int)($notifCount ?? 0) ?>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="min-width:320px">
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <strong>Notificaciones</strong>
                            <button class="btn btn-link btn-sm p-0 text-muted" style="font-size:.75rem"
                                    onclick="marcarTodasLeidas()">Marcar todas leídas</button>
                        </div>
                        <div class="notif-list">
                            <div class="dropdown-item text-muted text-center py-3">
                                <div class="spinner-border spinner-border-sm me-1"></div> Cargando...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User menu -->
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user['nombre'] ?? 'A', 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($user['nombre'] ?? '') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text text-muted small">
                                <?= htmlspecialchars($user['email'] ?? '') ?>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= $appUrl ?>/perfil">
                                <i class="bi bi-person me-2"></i> Mi Perfil
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="<?= $appUrl ?>/logout">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main class="admin-main">
            <?= $content ?>
        </main>
    </div>
</div>

<script src="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/js/app.js"></script>
<script src="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/js/admin.js"></script>
<script>
function toggleSidebar() {
    const sidebar  = document.getElementById('adminSidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const content  = document.querySelector('.admin-content');
    const isMobile = window.innerWidth <= 768;

    if (isMobile) {
        // Móvil: mostrar/ocultar con overlay
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
    } else {
        // Desktop: colapsar a 60px
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('sidebar-collapsed');
    }
}

function closeSidebar() {
    document.getElementById('adminSidebar').classList.remove('mobile-open');
    document.getElementById('sidebarOverlay').classList.remove('active');
}

// Cerrar sidebar en móvil al navegar a otra sección
document.querySelectorAll('.sidebar-link').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) closeSidebar();
    });
});
</script>
</body>
</html>
