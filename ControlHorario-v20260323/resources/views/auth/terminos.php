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
    <div class="terminos-grid">
        <aside class="terminos-aside">
            <?php if ($logo): ?>
                <div class="terminos-logo-wrap">
                    <img src="<?= $appUrl ?>/assets/uploads/<?= htmlspecialchars($logo) ?>"
                         alt="Logo"
                         class="terminos-logo">
                </div>
            <?php else: ?>
                <div class="terminos-icon-wrap">
                    <i class="bi bi-shield-check text-primary"></i>
                </div>
            <?php endif; ?>

            <div class="terminos-intro">
                <span class="terminos-eyebrow">Acceso inicial</span>
                <h2>Bienvenido al Sistema</h2>
                <p><?= htmlspecialchars($company) ?> — Control Horario Digital</p>
            </div>

            <div class="terminos-summary">
                <div class="terminos-summary-item">
                    <strong>Registro legal</strong>
                    <span>Base preparada para el cumplimiento del RD-Ley 8/2019.</span>
                </div>
                <div class="terminos-summary-item">
                    <strong>Trazabilidad</strong>
                    <span>Fichajes sellados y protegidos frente a modificaciones no autorizadas.</span>
                </div>
                <div class="terminos-summary-item">
                    <strong>Protección de datos</strong>
                    <span>Tratamiento alineado con RGPD y LOPDGDD.</span>
                </div>
            </div>

            <p class="terminos-meta">
                Fecha de aceptación: <?= date('d/m/Y H:i') ?> — IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '') ?>
            </p>
        </aside>

        <section class="terminos-main">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="terminos-box">
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

                <div class="alert alert-info mt-3 mb-0 terminos-note">
                    <i class="bi bi-info-circle me-2"></i>
                    Si tienes alguna duda sobre estas condiciones, contacta con el departamento de Recursos Humanos o con tu administrador del sistema antes de aceptar.
                </div>
            </div>

            <form method="POST" action="<?= $appUrl ?>/terminos" class="terminos-form">
                <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="acepto" value="1">

                <div class="form-check terminos-check">
                    <input class="form-check-input" type="checkbox" id="checkAcepto" required>
                    <label class="form-check-label" for="checkAcepto">
                        He leído y acepto las condiciones de uso del sistema de registro de jornada, así como el tratamiento de mis datos personales conforme a lo indicado.
                    </label>
                </div>

                <div class="terminos-actions">
                    <button type="submit" class="btn btn-primary btn-lg" id="btnAceptar" disabled>
                        <i class="bi bi-check-circle me-2"></i>Aceptar y Entrar al Sistema
                    </button>
                    <a href="<?= $appUrl ?>/logout" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-box-arrow-left me-2"></i>Cancelar y Salir
                    </a>
                </div>
            </form>
        </section>
    </div>
</div>

<style>
.auth-container {
    max-width: min(1180px, calc(100vw - 40px));
}

.auth-card {
    padding: 0;
    overflow: hidden;
}

.auth-logo {
    display: none;
}

.terminos-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 30px rgba(0,0,0,.10);
}

.terminos-grid {
    display: grid;
    grid-template-columns: 340px minmax(0, 1fr);
    min-height: min(760px, calc(100vh - 90px));
}

.terminos-aside {
    padding: 34px 30px 26px;
    background: linear-gradient(180deg, rgba(37,99,235,.10) 0%, rgba(14,165,233,.07) 100%);
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
}

.terminos-logo-wrap,
.terminos-icon-wrap {
    min-height: 88px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-bottom: 22px;
}

.terminos-logo {
    max-height: 72px;
    width: auto;
    object-fit: contain;
}

.terminos-icon-wrap i {
    font-size: 3.2rem;
}

.terminos-eyebrow {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    padding: 0 12px;
    border: 1px solid rgba(37,99,235,.16);
    color: #2563eb;
    background: rgba(255,255,255,.75);
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.terminos-intro h2 {
    margin: 16px 0 10px;
    font-size: 2rem;
    line-height: 1.05;
    font-weight: 700;
    color: #1e293b;
}

.terminos-intro p {
    margin: 0;
    color: #64748b;
    font-size: 1rem;
    line-height: 1.6;
}

.terminos-summary {
    display: grid;
    gap: 14px;
    margin-top: 28px;
}

.terminos-summary-item {
    padding: 16px 16px 14px;
    background: rgba(255,255,255,.72);
    border: 1px solid rgba(148,163,184,.22);
    border-radius: 10px;
}

.terminos-summary-item strong {
    display: block;
    margin-bottom: 6px;
    color: #1e293b;
    font-size: .94rem;
}

.terminos-summary-item span {
    display: block;
    color: #475569;
    font-size: .88rem;
    line-height: 1.55;
}

.terminos-meta {
    margin-top: auto;
    margin-bottom: 0;
    padding-top: 20px;
    color: #64748b;
    font-size: .75rem;
    line-height: 1.55;
}

.terminos-main {
    display: flex;
    flex-direction: column;
    min-height: 0;
    padding: 28px 30px 26px;
}

.terminos-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1.5rem;
    max-height: min(460px, calc(100vh - 290px));
    overflow-y: auto;
    font-size: .875rem;
    line-height: 1.6;
}

.terminos-box h6 { color: #1e293b; }
.terminos-box p  { color: #475569; }

.terminos-note {
    font-size: .85rem;
}

.terminos-form {
    margin-top: 20px;
}

.terminos-check {
    margin-bottom: 20px;
}

.terminos-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.terminos-actions .btn {
    min-height: 56px;
}

@media (max-width: 991.98px) {
    .auth-container {
        max-width: 680px;
    }

    .auth-card {
        padding: 2.5rem;
    }

    .terminos-grid {
        display: block;
        min-height: auto;
    }

    .terminos-aside {
        padding: 0;
        background: transparent;
        border-right: 0;
        display: block;
        margin-bottom: 22px;
    }

    .terminos-logo-wrap,
    .terminos-icon-wrap {
        justify-content: center;
        margin-bottom: 14px;
    }

    .terminos-intro,
    .terminos-meta {
        text-align: center;
    }

    .terminos-summary {
        display: none;
    }

    .terminos-main {
        padding: 0;
    }

    .terminos-box {
        max-height: 380px;
    }

    .terminos-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkbox = document.getElementById('checkAcepto');
    var button = document.getElementById('btnAceptar');

    if (!checkbox || !button) {
        return;
    }

    var sync = function () {
        button.disabled = !checkbox.checked;
    };

    checkbox.addEventListener('change', sync);
    sync();
});
</script>
