<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 mb-0"><i class="bi bi-calendar-week me-2"></i>Gestión de Vacaciones</h2>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#asignarModal">
            <i class="bi bi-calendar-plus me-1"></i><span class="d-none d-sm-inline">Asignar </span>Vacaciones
        </button>
    </div>

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

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?= ($filters['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="aprobada" <?= ($filters['estado'] ?? '') === 'aprobada' ? 'selected' : '' ?>>Aprobadas</option>
                        <option value="rechazada" <?= ($filters['estado'] ?? '') === 'rechazada' ? 'selected' : '' ?>>Rechazadas</option>
                        <option value="cancelada" <?= ($filters['estado'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <select name="usuario_id" class="form-select form-select-sm">
                        <option value="">Todos los empleados</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filters['usuario_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-4 col-sm-2 col-md-2">
                    <select name="year" class="form-select form-select-sm">
                        <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                            <option value="<?= $y ?>" <?= ($filters['year'] ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-8 col-sm-2 col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search"></i><span class="d-none d-sm-inline ms-1">Filtrar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Empleado</th>
                            <th class="d-none d-md-table-cell">Inicio</th>
                            <th class="d-none d-md-table-cell">Fin</th>
                            <th class="d-none d-md-table-cell">Días</th>
                            <th class="d-none d-lg-table-cell">Tipo</th>
                            <th class="d-none d-lg-table-cell">Origen</th>
                            <th>Estado</th>
                            <th class="d-none d-lg-table-cell">Solicitada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vacaciones)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No hay solicitudes de vacaciones</td></tr>
                        <?php else: ?>
                            <?php foreach ($vacaciones as $v): ?>
                            <?php
                            $dias = (new \DateTime($v['fecha_inicio']))->diff(new \DateTime($v['fecha_fin']))->days + 1;
                            ?>
                            <tr>
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($v['nombre'] . ' ' . $v['apellidos']) ?></strong>
                                    <!-- Fechas visibles solo en móvil -->
                                    <span class="d-md-none text-muted" style="font-size:0.75rem">
                                        <?= date('d/m/Y', strtotime($v['fecha_inicio'])) ?> → <?= date('d/m/Y', strtotime($v['fecha_fin'])) ?>
                                        (<?= $dias ?> d.)
                                    </span>
                                    <?php if ($v['departamento']): ?>
                                        <small class="d-none d-md-block text-muted"><?= htmlspecialchars($v['departamento']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell"><?= date('d/m/Y', strtotime($v['fecha_inicio'])) ?></td>
                                <td class="d-none d-md-table-cell"><?= date('d/m/Y', strtotime($v['fecha_fin'])) ?></td>
                                <td class="d-none d-md-table-cell"><strong><?= $dias ?></strong></td>
                                <td class="d-none d-lg-table-cell"><small><?= ucfirst($v['tipo']) ?></small></td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge <?= $v['origen'] === 'empresa' ? 'bg-info text-dark' : 'bg-light text-dark border' ?>">
                                        <?= $v['origen'] === 'empresa' ? 'Empresa' : 'Empleado' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $estMap = [
                                        'pendiente' => ['bg-warning text-dark', 'Pendiente'],
                                        'aprobada'  => ['bg-success', 'Aprobada'],
                                        'rechazada' => ['bg-danger', 'Rechazada'],
                                        'cancelada' => ['bg-secondary', 'Cancelada'],
                                    ];
                                    $est = $estMap[$v['estado']] ?? ['bg-secondary', $v['estado']];
                                    ?>
                                    <span class="badge <?= $est[0] ?>"><?= $est[1] ?></span>
                                </td>
                                <td class="d-none d-lg-table-cell"><small><?= date('d/m/Y', strtotime($v['fecha_solicitud'])) ?></small></td>
                                <td>
                                    <?php if ($v['estado'] === 'pendiente'): ?>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-success btn-sm"
                                                    onclick='resolverVacacion(<?= $v["id"] ?>, "aprobada")'
                                                    title="Aprobar">
                                                <i class="bi bi-check"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm"
                                                    onclick='resolverVacacion(<?= $v["id"] ?>, "rechazada")'
                                                    title="Rechazar">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">—</small>
                                    <?php endif; ?>
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

<!-- Resolver Modal -->
<div class="modal fade" id="resolverVacModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="resolverVacHeader">
                <h5 class="modal-title" id="resolverVacTitle">Resolver Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="resolverVacForm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                <input type="hidden" name="estado" id="resolverVacEstado">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Comentario</label>
                        <textarea name="comentario" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn" id="resolverVacBtn">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Asignar Vacaciones Modal -->
<div class="modal fade" id="asignarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Asignar Vacaciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/vacaciones/asignar">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Empleado <span class="text-danger">*</span></label>
                        <select name="usuario_id" class="form-select" required>
                            <option value="">Seleccionar empleado...</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Fecha Inicio <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_inicio" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fecha Fin <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_fin" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="normal">Vacaciones</option>
                            <option value="permiso">Permiso</option>
                            <option value="baja">Baja</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comentario</label>
                        <textarea name="comentario_admin" class="form-control" rows="2"
                                  placeholder="Motivo de la asignación..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-calendar-check me-1"></i> Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const APP_URL = document.querySelector('meta[name="app-url"]').content;
function resolverVacacion(id, estado) {
    const form  = document.getElementById('resolverVacForm');
    const title = document.getElementById('resolverVacTitle');
    const btn   = document.getElementById('resolverVacBtn');
    const hdr   = document.getElementById('resolverVacHeader');

    form.action = APP_URL + '/admin/vacaciones/' + id + '/resolver';
    document.getElementById('resolverVacEstado').value = estado;

    if (estado === 'aprobada') {
        title.textContent = 'Aprobar Vacaciones';
        hdr.className = 'modal-header border-success';
        btn.className = 'btn btn-success';
        btn.textContent = 'Aprobar';
    } else {
        title.textContent = 'Rechazar Vacaciones';
        hdr.className = 'modal-header border-danger';
        btn.className = 'btn btn-danger';
        btn.textContent = 'Rechazar';
    }
    new bootstrap.Modal(document.getElementById('resolverVacModal')).show();
}
</script>
