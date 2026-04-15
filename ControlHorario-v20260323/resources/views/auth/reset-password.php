<div class="auth-form-wrap">
    <h1 class="auth-title">Nueva Contraseña</h1>
    <p class="auth-subtitle">Elige una contraseña segura para tu cuenta</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/reset-password/<?= htmlspecialchars(urlencode($token)) ?>"
          class="auth-form">
        <input type="hidden" name="_csrf_token"
               value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">

        <div class="mb-3">
            <label for="password" class="form-label">
                <i class="bi bi-lock me-1"></i> Nueva Contraseña
            </label>
            <div class="input-group">
                <input type="password"
                       class="form-control form-control-lg"
                       id="password"
                       name="password"
                       placeholder="Mínimo 8 caracteres"
                       minlength="8"
                       required
                       autofocus>
                <button class="btn btn-outline-secondary" type="button" id="togglePass"
                        onclick="togglePwd('password', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <div id="strengthBar" class="mt-2" style="display:none">
                <div class="progress" style="height:4px">
                    <div id="strengthFill" class="progress-bar" role="progressbar" style="width:0%"></div>
                </div>
                <small id="strengthText" class="text-muted"></small>
            </div>
        </div>

        <div class="mb-4">
            <label for="password_confirm" class="form-label">
                <i class="bi bi-lock-fill me-1"></i> Confirmar Contraseña
            </label>
            <div class="input-group">
                <input type="password"
                       class="form-control form-control-lg"
                       id="password_confirm"
                       name="password_confirm"
                       placeholder="Repite la contraseña"
                       minlength="8"
                       required>
                <button class="btn btn-outline-secondary" type="button"
                        onclick="togglePwd('password_confirm', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <div id="matchMsg" class="mt-1" style="display:none">
                <small id="matchText"></small>
            </div>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                <i class="bi bi-shield-check me-2"></i>
                Guardar nueva contraseña
            </button>
        </div>
    </form>

    <div class="text-center mt-3">
        <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/login" class="text-muted">
            <i class="bi bi-arrow-left me-1"></i> Volver al inicio de sesión
        </a>
    </div>
</div>

<script>
function togglePwd(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon  = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

(function () {
    const pwdField    = document.getElementById('password');
    const confirmField= document.getElementById('password_confirm');
    const strengthBar = document.getElementById('strengthBar');
    const strengthFill= document.getElementById('strengthFill');
    const strengthText= document.getElementById('strengthText');
    const matchMsg    = document.getElementById('matchMsg');
    const matchText   = document.getElementById('matchText');
    const submitBtn   = document.getElementById('submitBtn');

    function getStrength(pwd) {
        let score = 0;
        if (pwd.length >= 8)  score++;
        if (pwd.length >= 12) score++;
        if (/[A-Z]/.test(pwd)) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[^A-Za-z0-9]/.test(pwd)) score++;
        return score;
    }

    pwdField.addEventListener('input', function () {
        const val   = this.value;
        strengthBar.style.display = val ? 'block' : 'none';
        const score = getStrength(val);
        const pct   = (score / 5) * 100;
        const labels= ['Muy débil','Débil','Regular','Fuerte','Muy fuerte'];
        const colors= ['#dc2626','#f97316','#eab308','#22c55e','#16a34a'];
        strengthFill.style.width      = pct + '%';
        strengthFill.style.background = colors[score - 1] || '#dc2626';
        strengthText.textContent      = labels[score - 1] || '';
        checkMatch();
    });

    confirmField.addEventListener('input', checkMatch);

    function checkMatch() {
        const pwd     = pwdField.value;
        const confirm = confirmField.value;
        if (!confirm) {
            matchMsg.style.display = 'none';
            submitBtn.disabled = false;
            return;
        }
        matchMsg.style.display = 'block';
        if (pwd === confirm) {
            matchText.textContent  = '✓ Las contraseñas coinciden';
            matchText.className    = 'text-success';
            submitBtn.disabled     = false;
        } else {
            matchText.textContent  = '✗ Las contraseñas no coinciden';
            matchText.className    = 'text-danger';
            submitBtn.disabled     = true;
        }
    }
})();
</script>
