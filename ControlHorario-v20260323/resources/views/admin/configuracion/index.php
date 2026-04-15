<?php
$appUrl   = rtrim($_ENV['APP_URL'] ?? '', '/');
$csrf     = htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken());
$getVal   = fn(string $key, string $default = '') => $config[$key]['valor'] ?? $default;
$defaults = (new \App\Models\Configuracion())->defaults();
?>

<div class="container-fluid py-3">
    <h2 class="h4 mb-4"><i class="bi bi-gear me-2"></i>Configuración del Sistema</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ════════════════════════════════════════════ -->
        <!-- LOGO — form independiente (no anidado)      -->
        <!-- ════════════════════════════════════════════ -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-image me-2"></i>Logo de la Empresa</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center g-3">

                        <!-- Preview actual -->
                        <div class="col-md-2 text-center">
                            <?php if (!empty($logo)): ?>
                                <img src="<?= $appUrl ?>/assets/uploads/<?= htmlspecialchars($logo) ?>"
                                     alt="Logo actual" class="img-fluid" style="max-height: 90px;">
                                <p class="text-muted small mt-1 mb-0">Logo actual</p>
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                     style="height: 80px;">
                                    <div class="text-center text-muted">
                                        <i class="bi bi-image fs-2 d-block"></i>
                                        <small>Sin logo</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Form subir logo -->
                        <div class="col-md-6">
                            <form method="POST"
                                  action="<?= $appUrl ?>/admin/configuracion/logo"
                                  enctype="multipart/form-data">
                                <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                                <div class="mb-2">
                                    <input type="file" name="logo" class="form-control"
                                           accept=".png,.jpg,.jpeg,.svg"
                                           id="logoInput" onchange="previewLogo(this)">
                                    <small class="text-muted">PNG, JPG o SVG. Máximo 2 MB.</small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-upload me-1"></i> Subir Logo
                                </button>
                            </form>
                        </div>

                        <!-- Vista previa + eliminar -->
                        <div class="col-md-4">
                            <div id="logoPreview" class="mb-2" style="display:none">
                                <img id="logoPreviewImg" src="" alt="Vista previa"
                                     style="max-height: 70px; border: 1px dashed #ccc; border-radius: 6px; padding: 4px;">
                                <p class="text-muted small mt-1 mb-0">Vista previa</p>
                            </div>
                            <?php if (!empty($logo)): ?>
                            <form method="POST"
                                  action="<?= $appUrl ?>/admin/configuracion/logo/eliminar">
                                <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('¿Eliminar el logo actual?')">
                                    <i class="bi bi-trash me-1"></i> Eliminar Logo
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════ -->
        <!-- RESTO DE CONFIGURACIÓN — un solo form       -->
        <!-- ════════════════════════════════════════════ -->
        <div class="col-12">
        <form method="POST" action="<?= $appUrl ?>/admin/configuracion" id="configForm">
            <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">

            <div class="row g-4">

                <!-- Información de la Empresa -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-building me-2"></i>Información de la Empresa</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nombre de la Empresa</label>
                                <input type="text" name="company_name" class="form-control"
                                       value="<?= htmlspecialchars($getVal('company_name', $defaults['company_name'])) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="company_address" class="form-control"
                                       value="<?= htmlspecialchars($getVal('company_address', '')) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CIF / NIF</label>
                                <input type="text" name="company_cif" class="form-control"
                                       value="<?= htmlspecialchars($getVal('company_cif', '')) ?>"
                                       placeholder="Ej: B12345678">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colores del Sistema -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-palette me-2"></i>Colores del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $colorFields = [
                                ['color_primary',   'Color Principal'],
                                ['color_secondary', 'Color Secundario'],
                                ['color_accent',    'Color Acento'],
                                ['color_success',   'Color Éxito'],
                                ['color_warning',   'Color Advertencia'],
                                ['color_danger',    'Color Peligro'],
                                ['color_bg',        'Color Fondo'],
                                ['color_text',      'Color Texto'],
                            ];
                            ?>
                            <div class="row g-2">
                                <?php foreach ($colorFields as [$key, $label]): ?>
                                <div class="col-6">
                                    <label class="form-label form-label-sm mb-1"><?= $label ?></label>
                                    <div class="input-group input-group-sm">
                                        <input type="color" name="<?= $key ?>"
                                               id="color_<?= $key ?>"
                                               value="<?= htmlspecialchars($getVal($key, $defaults[$key])) ?>"
                                               class="form-control form-control-color"
                                               style="width:40px;padding:2px;"
                                               oninput="syncHex('<?= $key ?>', this.value)">
                                        <input type="text" class="form-control form-control-sm"
                                               id="hex_<?= $key ?>"
                                               value="<?= htmlspecialchars($getVal($key, $defaults[$key])) ?>"
                                               pattern="^#[0-9A-Fa-f]{6}$"
                                               oninput="syncColor('<?= $key ?>', this.value)">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="resetColors()">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Restablecer por defecto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Geolocalización -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Geolocalización</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Latitud de la Oficina</label>
                                <input type="number" name="default_lat" class="form-control"
                                       step="0.000001" min="-90" max="90"
                                       value="<?= htmlspecialchars($getVal('default_lat', $defaults['default_lat'])) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Longitud de la Oficina</label>
                                <input type="number" name="default_lon" class="form-control"
                                       step="0.000001" min="-180" max="180"
                                       value="<?= htmlspecialchars($getVal('default_lon', $defaults['default_lon'])) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Radio Máximo para Fichar (metros)</label>
                                <input type="number" name="max_distance" class="form-control"
                                       min="10" max="999999"
                                       value="<?= htmlspecialchars($getVal('max_distance', $defaults['max_distance'])) ?>">
                                <small class="text-muted">Usa 0 para sin límite. Se puede anular por usuario (útil para comerciales).</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Precisión GPS Mínima (metros)</label>
                                <input type="number" name="min_accuracy" class="form-control"
                                       min="1" max="500"
                                       value="<?= htmlspecialchars($getVal('min_accuracy', $defaults['min_accuracy'])) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Horario y Vacaciones -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Horario y Vacaciones</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Hora de Inicio de Jornada</label>
                                <input type="time" name="work_start" class="form-control"
                                       value="<?= htmlspecialchars($getVal('work_start', $defaults['work_start'])) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hora de Fin de Jornada</label>
                                <input type="time" name="work_end" class="form-control"
                                       value="<?= htmlspecialchars($getVal('work_end', $defaults['work_end'])) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Días de Vacaciones por Defecto / año</label>
                                <input type="number" name="default_vacation_days" class="form-control"
                                       min="0" max="60"
                                       value="<?= htmlspecialchars($getVal('default_vacation_days', $defaults['default_vacation_days'])) ?>">
                                <small class="text-muted">Se aplica a nuevos usuarios.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guardar -->
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-x me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-lg me-2"></i> Guardar Configuración
                        </button>
                    </div>
                </div>

            </div>
        </form>
        </div>

    </div><!-- /row g-4 -->
</div>

<script>
const defaultColors = {
    color_primary:   '#2563eb',
    color_secondary: '#64748b',
    color_accent:    '#0ea5e9',
    color_success:   '#16a34a',
    color_warning:   '#d97706',
    color_danger:    '#dc2626',
    color_bg:        '#f8fafc',
    color_text:      '#1e293b',
};
const cssVarMap = {
    color_primary:   '--color-primary',
    color_secondary: '--color-secondary',
    color_accent:    '--color-accent',
    color_success:   '--color-success',
    color_warning:   '--color-warning',
    color_danger:    '--color-danger',
    color_bg:        '--color-bg',
    color_text:      '--color-text',
};

function syncHex(key, value) {
    document.getElementById('hex_' + key).value = value;
    const cssVar = cssVarMap[key];
    if (cssVar) document.documentElement.style.setProperty(cssVar, value);
}

function syncColor(key, value) {
    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
        document.getElementById('color_' + key).value = value;
        const cssVar = cssVarMap[key];
        if (cssVar) document.documentElement.style.setProperty(cssVar, value);
    }
}

function resetColors() {
    if (!confirm('¿Restablecer todos los colores a los valores por defecto?')) return;
    Object.entries(defaultColors).forEach(([key, value]) => {
        const c = document.getElementById('color_' + key);
        const h = document.getElementById('hex_' + key);
        if (c) c.value = value;
        if (h) h.value = value;
        const cssVar = cssVarMap[key];
        if (cssVar) document.documentElement.style.setProperty(cssVar, value);
    });
}

function previewLogo(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('logoPreviewImg').src = e.target.result;
        document.getElementById('logoPreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
}
</script>
