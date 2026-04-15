<?php
$appUrl  = rtrim($_ENV['APP_URL'] ?? '', '/');
$csrf    = htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken());
$logo    = null;
try {
    $logo = (new \App\Models\Configuracion())->get('logo');
    $company = (new \App\Models\Configuracion())->get('company_name', 'Control Horario Digital');
} catch (\Throwable $e) {
    $company = 'Control Horario Digital';
}
?>
<div class="terminos-card">

    <?php if ($logo): ?>
        <div class="text-center mb-3">
            <img src="<?= $appUrl ?>/assets/uploads/<?= htmlspecialchars($logo) ?>"
                 alt="Logo" style="max-height:70px; object-fit:contain;">
        </div>
    <?php else: ?>
        <div class="text-center mb-3">
            <i class="bi bi-shield-check text-primary" style="font-size:3rem"></i>
        </div>
    <?php endif; ?>

    <h2 class="text-center mb-1" style="font-size:1.4rem;font-weight:700">Bienvenido al Sistema</h2>
    <p class="text-center text-muted mb-4" style="font-size:.9rem">
        <?= htmlspecialchars($company) ?> — Control Horario Digital
    </p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="terminos-box mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-file-text me-2 text-primary"></i>Condiciones de Uso del Sistema de Registro de Jornada</h5>

        <p>Antes de acceder al sistema, debes leer y aceptar las siguientes condiciones:</p>

        <h6 class="fw-semibold mt-3">1. Cumplimiento Legal</h6>
        <p>Este sistema cumple con el <strong>Real Decreto-Ley 8/2019</strong> de medidas urgentes de protección social y de lucha contra la precariedad laboral en la jornada de trabajo, que establece la obligatoriedad del registro diario de jornada.</p>

        <h6 class="fw-semibold mt-3">2. Registro de tu Jornada</h6>
        <p>El sistema registrará de forma automática e inmutable la hora de entrada y salida mediante geolocalización GPS. <strong>Estos registros tienen validez legal y no pueden ser modificados</strong> una vez realizados, salvo mediante incidencia justificada y aprobada por el administrador.</p>

        <h6 class="fw-semibold mt-3">3. Inmutabilidad de los Datos</h6>
        <p>Cada fichaje queda sellado criptográficamente (HMAC-SHA256) formando una cadena inalterable. Cualquier intento de manipulación es detectado automáticamente por el sistema de auditoría.</p>

        <h6 class="fw-semibold mt-3">4. Conservación de Datos</h6>
        <p>Los registros de jornada se conservarán durante un mínimo de <strong>4 años</strong> conforme a la legislación vigente, y estarán disponibles para la Inspección de Trabajo en caso de requerimiento.</p>

        <h6 class="fw-semibold mt-3">5. Acceso a tus Datos</h6>
        <p>Tienes derecho a consultar en todo momento tus registros de jornada a través del apartado <em>Mis Fichajes</em>. También puedes solicitar rectificaciones mediante el sistema de incidencias.</p>

        <h6 class="fw-semibold mt-3">6. Protección de Datos (RGPD)</h6>
        <p>Los datos recogidos (nombre, email, geolocalización en el momento del fichaje) se tratan conforme al <strong>Reglamento General de Protección de Datos (RGPD)</strong> y la LOPDGDD. La base legal del tratamiento es la obligación legal del registro de jornada. No se cederán datos a terceros salvo requerimiento legal.</p>

        <h6 class="fw-semibold mt-3">7. Uso Correcto del Sistema</h6>
        <p>Te comprometes a fichar de forma honesta: solo cuando te encuentres en tu puesto de trabajo y dentro del horario establecido. El uso fraudulento del sistema puede ser causa de sanción disciplinaria.</p>

        <div class="alert alert-info mt-3 mb-0" style="font-size:.85rem">
            <i class="bi bi-info-circle me-2"></i>
            Si tienes alguna duda sobre estas condiciones, contacta con el departamento de Recursos Humanos o con tu administrador del sistema antes de aceptar.
        </div>
    </div>

    <form method="POST" action="<?= $appUrl ?>/terminos">
        <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="acepto" value="1">

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="checkAcepto" required
                   onchange="document.getElementById('btnAceptar').disabled = !this.checked">
            <label class="form-check-label" for="checkAcepto">
                He leído y acepto las condiciones de uso del sistema de registro de jornada, así como el tratamiento de mis datos personales conforme a lo indicado.
            </label>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg" id="btnAceptar" disabled>
                <i class="bi bi-check-circle me-2"></i>Aceptar y Entrar al Sistema
            </button>
            <a href="<?= $appUrl ?>/logout" class="btn btn-outline-secondary">
                <i class="bi bi-box-arrow-left me-2"></i>Cancelar y Salir
            </a>
        </div>
    </form>

    <p class="text-center text-muted mt-4 mb-0" style="font-size:.75rem">
        Fecha de aceptación: <?= date('d/m/Y H:i') ?> — IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '') ?>
    </p>
</div>

<style>
.terminos-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 30px rgba(0,0,0,.10);
    padding: 2.5rem;
    max-width: 680px;
    margin: 2rem auto;
}
.terminos-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1.5rem;
    max-height: 380px;
    overflow-y: auto;
    font-size: .875rem;
    line-height: 1.6;
}
.terminos-box h6 { color: #1e293b; }
.terminos-box p  { color: #475569; }
</style>
