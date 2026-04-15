<div class="auth-form-wrap">
    <div class="text-center mb-4">
        <i class="bi bi-shield-lock-fill" style="font-size:3rem;color:var(--color-warning)"></i>
    </div>

    <h1 class="auth-title">Cambio de contraseña obligatorio</h1>
    <p class="auth-subtitle">El administrador ha restablecido tu contraseña. Debes establecer una nueva para continuar.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-warning">
        <i class="bi bi-info-circle me-2"></i>
        Elige una contraseña segura que solo tú conozcas. <strong>Mínimo 8 caracteres.</strong>
    </div>

    <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/cambiar-password" class="auth-form">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">

        <div class="mb-3">
            <label for="password" class="form-label">
                <i class="bi bi-lock me-1"></i> Nueva contraseña
            </label>
            <div class="input-group">
                <input type="password" class="form-control form-control-lg"
                       id="password" name="password"
                       minlength="8" required autofocus
                       placeholder="Mínimo 8 caracteres">
                <button type="button" class="btn btn-outline-secondary" onclick="toggleVer('password',this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label for="password_confirm" class="form-label">
                <i class="bi bi-lock-fill me-1"></i> Confirmar contraseña
            </label>
            <div class="input-group">
                <input type="password" class="form-control form-control-lg"
                       id="password_confirm" name="password_confirm"
                       minlength="8" required
                       placeholder="Repite la contraseña">
                <button type="button" class="btn btn-outline-secondary" onclick="toggleVer('password_confirm',this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <div id="match_msg" class="form-text"></div>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg btn-login" id="btn_submit">
                <i class="bi bi-check-lg me-2"></i> Establecer nueva contraseña
            </button>
        </div>
    </form>
</div>

<script>
function toggleVer(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

// Validación en tiempo real de coincidencia
const p1  = document.getElementById('password');
const p2  = document.getElementById('password_confirm');
const msg = document.getElementById('match_msg');
const btn = document.getElementById('btn_submit');

function checkMatch() {
    if (!p2.value) { msg.textContent = ''; return; }
    if (p1.value === p2.value) {
        msg.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Las contraseñas coinciden</span>';
        btn.disabled  = false;
    } else {
        msg.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Las contraseñas no coinciden</span>';
        btn.disabled  = true;
    }
}
p1.addEventListener('input', checkMatch);
p2.addEventListener('input', checkMatch);
</script>
