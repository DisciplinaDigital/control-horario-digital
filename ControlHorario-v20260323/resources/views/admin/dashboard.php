<div class="container-fluid py-3">
    <h2 class="h4 mb-4"><i class="bi bi-speedometer2 me-2"></i>Panel de Administración</h2>

    <!-- Stats cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stats-card stats-card-primary">
                <div class="stats-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stats-value" id="stat-activos"><?= $stats['usuarios_activos_hoy'] ?></div>
                <div class="stats-label">Activos Hoy</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card stats-card-warning">
                <div class="stats-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="stats-value" id="stat-incidencias"><?= $stats['incidencias_pendientes'] ?></div>
                <div class="stats-label">Incidencias Pendientes</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card stats-card-success">
                <div class="stats-icon"><i class="bi bi-clock-fill"></i></div>
                <div class="stats-value" id="stat-fichajes"><?= $stats['fichajes_hoy'] ?></div>
                <div class="stats-label">Fichajes Hoy</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card stats-card-info">
                <div class="stats-icon"><i class="bi bi-person-badge-fill"></i></div>
                <div class="stats-value" id="stat-total"><?= $stats['total_usuarios'] ?></div>
                <div class="stats-label">Total Empleados</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Pending incidents -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Incidencias Pendientes
                    </h6>
                    <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/incidencias?estado=pendiente"
                       class="btn btn-outline-warning btn-sm">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($incidenciasPendientes)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-check-circle fs-3 d-block mb-2 text-success"></i>
                            Sin incidencias pendientes
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($incidenciasPendientes as $inc): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <code class="small"><?= htmlspecialchars($inc['numero_incidencia']) ?></code>
                                        <strong class="d-block"><?= htmlspecialchars($inc['nombre'] . ' ' . $inc['apellidos']) ?></strong>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($inc['fecha_fichaje'])) ?> &mdash;
                                            <?= htmlspecialchars(mb_strimwidth($inc['razon'], 0, 60, '...')) ?>
                                        </small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-success btn-sm"
                                                onclick="resolverIncidencia(<?= $inc['id'] ?>, 'aceptada')"
                                                title="Aceptar">
                                            <i class="bi bi-check"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm"
                                                onclick="resolverIncidencia(<?= $inc['id'] ?>, 'rechazada')"
                                                title="Rechazar">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent fichajes -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-clock me-2"></i>Fichajes de Hoy</h6>
                    <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/fichajes?fecha_desde=<?= date('Y-m-d') ?>&fecha_hasta=<?= date('Y-m-d') ?>"
                       class="btn btn-outline-primary btn-sm">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Empleado</th>
                                    <th>Hora</th>
                                    <th>Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ultimosFichajes)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">Sin fichajes hoy</td></tr>
                                <?php else: ?>
                                    <?php foreach ($ultimosFichajes as $f): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($f['nombre'] . ' ' . $f['apellidos']) ?></td>
                                        <td><strong><?= date('H:i', strtotime($f['fecha_hora'])) ?></strong></td>
                                        <td>
                                            <span class="badge <?= $f['tipo'] === 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $f['tipo'] === 'entrada' ? 'E' : 'S' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick links -->
    <div class="row g-3 mt-2">
        <div class="col-12">
            <h6 class="text-muted">Accesos Rápidos</h6>
        </div>
        <?php
        $links = [
            ['admin/usuarios', 'bi-people', 'Gestionar Usuarios', 'primary'],
            ['admin/fichajes/integridad', 'bi-shield-check', 'Verificar Integridad', 'success'],
            ['admin/fichajes/exportar?format=pdf', 'bi-file-earmark-pdf', 'Informe Inspección', 'warning'],
            ['admin/festivos', 'bi-calendar-event', 'Gestionar Festivos', 'info'],
            ['admin/configuracion', 'bi-gear', 'Configuración', 'secondary'],
        ];
        ?>
        <?php foreach ($links as [$path, $icon, $label, $color]): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/<?= $path ?>"
               class="btn btn-outline-<?= $color ?> w-100 d-flex flex-column align-items-center py-3 gap-2">
                <i class="bi <?= $icon ?> fs-4"></i>
                <small><?= $label ?></small>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Resolver incidencia form (hidden) -->
<form id="resolverForm" method="POST" style="display:none">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
    <input type="hidden" name="estado" id="resolverEstado">
    <input type="hidden" name="comentario" value="Resuelto desde el dashboard">
</form>

<script>
function resolverIncidencia(id, estado) {
    if (!confirm(`¿${estado === 'aceptada' ? 'Aceptar' : 'Rechazar'} esta incidencia?`)) return;
    const form = document.getElementById('resolverForm');
    form.action = APP_URL + '/admin/incidencias/' + id + '/resolver';
    document.getElementById('resolverEstado').value = estado;
    form.submit();
}
</script>
