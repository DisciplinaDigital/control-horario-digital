<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 mb-0">
            <i class="bi bi-shield-check me-2 text-success"></i>
            Verificación de Integridad de Fichajes
        </h2>
        <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/fichajes/integridad?format=pdf"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>Descargar informe PDF
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <div class="h2 text-primary"><?= $report['total_users'] ?></div>
                    <small>Total Empleados</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <div class="h2 text-success"><?= $report['valid_users'] ?></div>
                    <small>Cadenas Válidas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <div class="h2 text-danger"><?= $report['invalid_users'] ?></div>
                    <small>Cadenas con Errores</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body">
                    <div class="h2 text-info"><?= $report['total_records'] ?></div>
                    <small>Total Registros</small>
                </div>
            </div>
        </div>
    </div>

    <?php if ($report['invalid_users'] === 0): ?>
        <div class="alert alert-success">
            <i class="bi bi-shield-check me-2 fs-4"></i>
            <strong>Integridad verificada correctamente.</strong>
            Todos los <?= $report['total_records'] ?> registros de fichaje están íntegros.
            <br><small class="text-muted">Informe generado el <?= htmlspecialchars($report['generated_at']) ?></small>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <i class="bi bi-shield-exclamation me-2 fs-4"></i>
            <strong>¡Atención! Se han detectado problemas de integridad.</strong>
            <?= $report['invalid_users'] ?> empleado(s) tienen registros con incidencias de verificación.
            Conserve este informe.
            <br><small>Informe generado el <?= htmlspecialchars($report['generated_at']) ?></small>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Estado por Empleado</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Empleado</th>
                            <th>Email</th>
                            <th class="text-center">Total Registros</th>
                            <th class="text-center">Verificados</th>
                            <th class="text-center">Estado Cadena</th>
                            <th>Errores</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['chains'] as $chain): ?>
                        <tr class="<?= !$chain['valid'] ? 'table-danger' : '' ?>">
                            <td><strong><?= htmlspecialchars(trim($chain['nombre'] . ' ' . $chain['apellidos'])) ?></strong></td>
                            <td><?= htmlspecialchars($chain['email']) ?></td>
                            <td class="text-center"><?= $chain['total'] ?></td>
                            <td class="text-center">
                                <span class="<?= $chain['verified'] < $chain['total'] ? 'text-danger fw-bold' : 'text-success' ?>">
                                    <?= $chain['verified'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($chain['valid']): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-shield-check me-1"></i>Válida
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="bi bi-shield-x me-1"></i>Comprometida
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($chain['errors'])): ?>
                                    <button class="btn btn-link btn-sm text-danger p-0"
                                            onclick="mostrarErrores(<?= htmlspecialchars(json_encode($chain['errors'], JSON_UNESCAPED_UNICODE)) ?>)">
                                        Ver <?= count($chain['errors']) ?> error(es)
                                    </button>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="erroresModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-danger">
                <h5 class="modal-title text-danger"><i class="bi bi-shield-x me-2"></i>Errores de Integridad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="erroresBody"></div>
        </div>
    </div>
</div>

<script>
function mostrarErrores(errors) {
    let html = '<div class="table-responsive"><table class="table table-sm">';
    html += '<thead><tr><th>ID</th><th>Fecha</th><th>Tipo</th><th>Error</th></tr></thead><tbody>';
    errors.forEach(e => {
        html += `<tr class="table-danger">
            <td>${e.id}</td>
            <td>${e.fecha}</td>
            <td>${e.tipo}</td>
            <td class="text-danger">${e.error}</td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('erroresBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('erroresModal')).show();
}
</script>
