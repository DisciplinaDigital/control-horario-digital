<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 mb-0"><i class="bi bi-clock me-2"></i>Gestión de Fichajes</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/fichajes/integridad"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-shield-check me-1"></i><span class="d-none d-sm-inline">Verificar </span>Integridad
            </a>
            <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/fichajes/exportar?fecha_desde=<?= htmlspecialchars($filters['fecha_desde']) ?>&fecha_hasta=<?= htmlspecialchars($filters['fecha_hasta']) ?>&usuario_id=<?= htmlspecialchars($filters['usuario_id']) ?>"
               class="btn btn-outline-warning btn-sm">
                <i class="bi bi-download me-1"></i><span class="d-none d-sm-inline">Exportar </span>Inspección
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label form-label-sm mb-1">Empleado</label>
                    <select name="usuario_id" class="form-select form-select-sm">
                        <option value="">Todos los empleados</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $filters['usuario_id'] == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <label class="form-label form-label-sm mb-1">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['fecha_desde']) ?>">
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <label class="form-label form-label-sm mb-1">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['fecha_hasta']) ?>">
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <label class="form-label form-label-sm mb-1">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="entrada" <?= $filters['tipo'] === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                        <option value="salida" <?= $filters['tipo'] === 'salida' ? 'selected' : '' ?>>Salida</option>
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <label class="form-label form-label-sm mb-1 d-none d-sm-block">&nbsp;</label>
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
                <table class="table table-hover align-middle mb-0 table-sm">
                    <thead class="table-light">
                        <tr>
                            <th class="d-none d-xl-table-cell">#</th>
                            <th>Empleado</th>
                            <th class="d-none d-md-table-cell">Fecha</th>
                            <th class="d-none d-md-table-cell">Hora</th>
                            <th>Tipo</th>
                            <th class="d-none d-lg-table-cell">Método</th>
                            <th class="d-none d-xl-table-cell">IP</th>
                            <th class="text-center">Integr.</th>
                            <th class="d-none d-lg-table-cell">Correc.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fichajes)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No se encontraron fichajes</td></tr>
                        <?php else: ?>
                            <?php foreach ($fichajes as $f): ?>
                            <tr>
                                <td class="d-none d-xl-table-cell"><small class="text-muted"><?= $f['id'] ?></small></td>
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($f['nombre'] . ' ' . $f['apellidos']) ?></strong>
                                    <!-- Fecha/hora visible solo en móvil -->
                                    <span class="d-md-none text-muted" style="font-size:0.75rem">
                                        <?= date('d/m/Y H:i', strtotime($f['fecha_hora'])) ?>
                                    </span>
                                    <?php if ($f['departamento']): ?>
                                        <small class="d-none d-md-block text-muted"><?= htmlspecialchars($f['departamento']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell"><?= date('d/m/Y', strtotime($f['fecha_hora'])) ?></td>
                                <td class="d-none d-md-table-cell"><strong><?= date('H:i', strtotime($f['fecha_hora'])) ?></strong></td>
                                <td>
                                    <span class="badge <?= $f['tipo'] === 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= ucfirst($f['tipo']) ?>
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell"><small><?= htmlspecialchars($f['metodo_registro']) ?></small></td>
                                <td class="d-none d-xl-table-cell"><small class="text-muted"><?= htmlspecialchars($f['ip_address'] ?? '—') ?></small></td>
                                <td class="text-center">
                                    <span class="integrity-badge integrity-ok"
                                          title="Hash: <?= htmlspecialchars(substr($f['hash_integridad'], 0, 16)) ?>...">
                                        <i class="bi bi-shield-check"></i>
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php if ($f['es_correccion']): ?>
                                        <span class="badge bg-warning text-dark small" title="<?= htmlspecialchars($f['correccion_justificacion'] ?? '') ?>">
                                            Admin
                                        </span>
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
        <div class="card-footer text-muted small">
            <?= count($fichajes) ?> fichaje(s) &mdash;
            Registros inmutables con cadena de hash HMAC-SHA256 conforme al RD-Ley 8/2019
        </div>
    </div>
</div>
