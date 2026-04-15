<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0"><i class="bi bi-clock me-2"></i>Mis Fichajes</h2>
        <div class="d-flex gap-2">
            <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/fichajes/exportar?fecha_desde=<?= htmlspecialchars($filters['fecha_desde']) ?>&fecha_hasta=<?= htmlspecialchars($filters['fecha_hasta']) ?>"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i> Exportar CSV
            </a>
            <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/fichajes/exportar/pdf?fecha_desde=<?= htmlspecialchars($filters['fecha_desde']) ?>&fecha_hasta=<?= htmlspecialchars($filters['fecha_hasta']) ?>"
               class="btn btn-outline-secondary btn-sm" target="_blank">
                <i class="bi bi-printer me-1"></i> Imprimir PDF
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['fecha_desde']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['fecha_hasta']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="entrada" <?= $filters['tipo'] === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                        <option value="salida" <?= $filters['tipo'] === 'salida' ? 'selected' : '' ?>>Salida</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search me-1"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/fichajes" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x me-1"></i> Limpiar
                    </a>
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
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Tipo</th>
                            <th>Método</th>
                            <th>Ubicación</th>
                            <th class="text-center">Integridad</th>
                            <th>Corrección</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fichajes)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-clock-history fs-2 d-block mb-2"></i>
                                    No se encontraron fichajes en el período seleccionado
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fichajes as $f): ?>
                            <tr>
                                <td><small class="text-muted"><?= $f['id'] ?></small></td>
                                <td><?= date('d/m/Y', strtotime($f['fecha_hora'])) ?></td>
                                <td><strong><?= date('H:i:s', strtotime($f['fecha_hora'])) ?></strong></td>
                                <td>
                                    <span class="badge <?= $f['tipo'] === 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                                        <i class="bi <?= $f['tipo'] === 'entrada' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right' ?> me-1"></i>
                                        <?= ucfirst($f['tipo']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($f['metodo_registro']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($f['latitud'] && $f['longitud']): ?>
                                        <a href="https://maps.google.com/?q=<?= $f['latitud'] ?>,<?= $f['longitud'] ?>"
                                           target="_blank" class="btn btn-link btn-sm p-0" title="Ver en mapa">
                                            <i class="bi bi-geo-alt text-primary"></i>
                                            <small><?= round($f['latitud'], 4) ?>, <?= round($f['longitud'], 4) ?></small>
                                        </a>
                                    <?php else: ?>
                                        <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="integrity-badge integrity-ok" title="Hash HMAC-SHA256 verificado: <?= htmlspecialchars(substr($f['hash_integridad'], 0, 16)) ?>...">
                                        <i class="bi bi-shield-check"></i>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($f['es_correccion']): ?>
                                        <span class="badge bg-warning text-dark" title="<?= htmlspecialchars($f['correccion_justificacion'] ?? '') ?>">
                                            <i class="bi bi-pencil me-1"></i> Corr.
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
        <?php if (!empty($fichajes)): ?>
        <div class="card-footer text-muted small">
            Mostrando <?= count($fichajes) ?> fichaje(s) &mdash;
            Hash de integridad HMAC-SHA256 verificado conforme a la Ley de Registro de Jornada
        </div>
        <?php endif; ?>
    </div>
</div>
