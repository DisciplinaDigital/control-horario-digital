<div class="auth-form-wrap">
    <h1 class="auth-title">Iniciar Sesión</h1>
    <p class="auth-subtitle">Sistema de Control Horario Digital</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/login" class="auth-form" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">

        <div class="mb-3">
            <label for="email" class="form-label">
                <i class="bi bi-envelope me-1"></i> Correo Electrónico
            </label>
            <input
                type="email"
                class="form-control form-control-lg"
                id="email"
                name="email"
                value="<?= htmlspecialchars($email ?? '') ?>"
                placeholder="nombre@empresa.com"
                required
                autofocus
                autocomplete="email"
            >
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label mb-0">
                    <i class="bi bi-lock me-1"></i> Contraseña
                </label>
                <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/forgot-password" class="text-muted small">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
            <div class="input-group">
                <input
                    type="password"
                    class="form-control form-control-lg"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Entrar
            </button>
        </div>
    </form>
</div>

<script>
function togglePassword() {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
