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
<body class="app-body">

<!-- Navbar -->
<nav class="app-navbar navbar navbar-expand-lg">
    <div class="container-fluid">

        <!-- Brand -->
        <a class="navbar-brand" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/">
            <?php if (!empty($logo)): ?>
                <img src="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/uploads/<?= htmlspecialchars($logo) ?>" alt="Logo" class="navbar-logo">
            <?php else: ?>
                <i class="bi bi-clock-history"></i>
                <span class="d-none d-sm-inline"><?= htmlspecialchars($companyName ?? 'Control Horario') ?></span>
            <?php endif; ?>
        </a>

        <!-- ── Mobile only: bell + avatar (always visible, left of hamburger) ── -->
        <div class="d-flex d-lg-none align-items-center gap-1 ms-auto me-2">
            <!-- Bell (mobile) -->
            <div class="dropdown">
                <button class="navbar-icon-btn position-relative"
                        id="notifDropdownMobile"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
                        aria-label="Notificaciones">
                    <i class="bi bi-bell"></i>
                    <span class="notif-badge notification-badge badge rounded-pill <?= ($notifCount ?? 0) > 0 ? '' : 'd-none' ?>">
                        <?= (int)($notifCount ?? 0) ?>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown shadow-lg"
                     aria-labelledby="notifDropdownMobile">
                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                        <strong>Notificaciones</strong>
                        <button class="btn btn-link btn-sm p-0 text-primary" onclick="marcarTodasLeidas()">
                            Marcar leídas
                        </button>
                    </div>
                    <div class="notif-list" id="notifListMobile">
                        <div class="dropdown-item text-muted text-center py-3">Cargando...</div>
                    </div>
                </div>
            </div>

            <!-- Avatar → toggle hamburger menu -->
            <button class="navbar-avatar-btn"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarMain"
                    aria-controls="navbarMain"
                    aria-expanded="false"
                    aria-label="Menú de usuario">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['nombre'] ?? 'U', 0, 1)) ?>
                </div>
            </button>
        </div>

        <!-- Hamburger -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible content -->
        <div class="collapse navbar-collapse" id="navbarMain">

            <!-- Main navigation links -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/fichajes') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin') ? 'active' : '' ?>"
                       href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/fichajes">
                        <i class="bi bi-clock me-1"></i> Fichajes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/incidencias') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin') ? 'active' : '' ?>"
                       href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/incidencias">
                        <i class="bi bi-exclamation-triangle me-1"></i> Incidencias
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/vacaciones') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin') ? 'active' : '' ?>"
                       href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/vacaciones">
                        <i class="bi bi-calendar-week me-1"></i> Vacaciones
                    </a>
                </li>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link text-warning" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin">
                        <i class="bi bi-shield-lock me-1"></i> Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- ── Desktop right side (hidden on mobile) ── -->
            <ul class="navbar-nav align-items-center d-none d-lg-flex">
                <!-- Bell (desktop) -->
                <li class="nav-item dropdown me-2">
                    <button class="btn btn-link nav-link position-relative"
                            id="notifDropdown"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notif-badge notification-badge badge rounded-pill <?= ($notifCount ?? 0) > 0 ? '' : 'd-none' ?>">
                            <?= (int)($notifCount ?? 0) ?>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <strong>Notificaciones</strong>
                            <button class="btn btn-link btn-sm p-0" onclick="marcarTodasLeidas()">
                                Marcar todas leídas
                            </button>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="dropdown-item text-muted text-center py-3">Cargando...</div>
                        </div>
                    </div>
                </li>

                <!-- User dropdown (desktop) -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user['nombre'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span><?= htmlspecialchars(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? '')) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/perfil">
                                <i class="bi bi-person me-2"></i> Mi Perfil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/logout">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>

            <!-- ── Mobile only: user info + perfil + logout (inside hamburger) ── -->
            <div class="d-lg-none mobile-menu-footer">
                <div class="mobile-menu-user">
                    <div class="user-avatar user-avatar-lg">
                        <?= strtoupper(substr($user['nombre'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="mobile-menu-user-info">
                        <div class="fw-semibold"><?= htmlspecialchars(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? '')) ?></div>
                        <small class="text-white-50"><?= htmlspecialchars($user['email'] ?? '') ?></small>
                    </div>
                </div>
                <a class="mobile-menu-item" href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/perfil">
                    <i class="bi bi-person-circle"></i>
                    <span>Mi Perfil</span>
                </a>
                <div class="mobile-menu-divider"></div>
                <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/logout">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                    <button type="submit" class="mobile-menu-item mobile-menu-logout w-100">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>

        </div><!-- /.navbar-collapse -->
    </div>
</nav>

<!-- Main content -->
<main class="app-main">
    <?= $content ?>
</main>

<!-- Footer -->
<footer class="app-footer">
    <div class="container-fluid">
        <small class="text-muted">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($companyName ?? '') ?> &mdash;
            Sistema de Control Horario Digital &mdash;
            Conforme RD-Ley 8/2019 &mdash;
            <a href="https://www.disciplinadigital.es" target="_blank" rel="noopener" class="text-muted">www.disciplinadigital.es</a>
        </small>
    </div>
</footer>

<script src="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/js/app.js"></script>
</body>
</html>
