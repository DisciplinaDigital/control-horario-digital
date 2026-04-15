<div class="auth-form-wrap text-center">
    <div class="mb-4">
        <i class="bi bi-shield-lock" style="font-size:3.5rem; color:var(--color-primary)"></i>
    </div>

    <h1 class="auth-title">¿Olvidaste tu contraseña?</h1>
    <p class="auth-subtitle mb-4">
        Por seguridad, el restablecimiento de contraseñas lo realiza únicamente el administrador del sistema.
    </p>

    <div class="alert alert-info text-start">
        <i class="bi bi-info-circle me-2"></i>
        <strong>¿Qué hacer?</strong><br>
        Contacta con tu administrador para que restablezca tu contraseña.
        Podrás cambiarla tú mismo desde tu perfil una vez hayas iniciado sesión.
    </div>

    <div class="text-center mt-4">
        <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/login" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1"></i> Volver al inicio de sesión
        </a>
    </div>
</div>
