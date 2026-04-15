<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0"><i class="bi bi-calendar-week me-2"></i>Mis Vacaciones</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaVacacionModal">
            <i class="bi bi-plus-lg me-1"></i> Solicitar Vacaciones
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

    <!-- Vacation days counter -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h2 text-primary mb-0"><?= $diasInfo['dias_totales'] ?? 22 ?></div>
                    <small class="text-muted">Días Totales <?= $year ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h2 text-success mb-0"><?= $diasInfo['dias_usados'] ?? 0 ?></div>
                    <small class="text-muted">Días Usados</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h2 text-warning mb-0">
                        <?= ($diasInfo['dias_totales'] ?? 22) - ($diasInfo['dias_usados'] ?? 0) ?>
                    </div>
                    <small class="text-muted">Días Disponibles</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Year filter -->
    <div class="d-flex gap-2 mb-3 align-items-center">
        <span class="text-muted">Año:</span>
        <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
            <a href="?year=<?= $y ?>"
               class="btn btn-sm <?= $y == $year ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= $y ?>
            </a>
        <?php endfor; ?>
    </div>

    <!-- Vacaciones table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Días</th>
                            <th>Tipo</th>
                            <th>Origen</th>
                            <th>Estado</th>
                            <th>Comentario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vacaciones)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                    No tienes solicitudes de vacaciones para <?= $year ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vacaciones as $v): ?>
                            <?php
                            $dias = (new \DateTime($v['fecha_inicio']))->diff(new \DateTime($v['fecha_fin']))->days + 1;
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($v['fecha_inicio'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($v['fecha_fin'])) ?></td>
                                <td><span class="badge bg-light text-dark"><?= $dias ?> días</span></td>
                                <td>
                                    <?php
                                    $tipoMap = [
                                        'normal'   => 'Vacaciones',
                                        'trabajo'  => 'Trabajo',
                                        'permiso'  => 'Permiso',
                                        'baja'     => 'Baja',
                                    ];
                                    ?>
                                    <?= $tipoMap[$v['tipo']] ?? $v['tipo'] ?>
                                </td>
                                <td>
                                    <span class="badge <?= $v['origen'] === 'empresa' ? 'bg-info text-dark' : 'bg-light text-dark' ?>">
                                        <?= $v['origen'] === 'empresa' ? 'Empresa' : 'Solicitada' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $estMap = [
                                        'pendiente' => ['bg-warning text-dark', 'bi-clock', 'Pendiente'],
                                        'aprobada'  => ['bg-success', 'bi-check-circle', 'Aprobada'],
                                        'rechazada' => ['bg-danger', 'bi-x-circle', 'Rechazada'],
                                        'cancelada' => ['bg-secondary', 'bi-dash-circle', 'Cancelada'],
                                    ];
                                    $est = $estMap[$v['estado']] ?? ['bg-secondary', 'bi-question', $v['estado']];
                                    ?>
                                    <span class="badge <?= $est[0] ?>">
                                        <i class="bi <?= $est[1] ?> me-1"></i><?= $est[2] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($v['comentario_admin']): ?>
                                        <small title="<?= htmlspecialchars($v['comentario_admin']) ?>">
                                            <i class="bi bi-chat-left-text text-muted"></i>
                                            <?= htmlspecialchars(mb_strimwidth($v['comentario_admin'], 0, 30, '...')) ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (in_array($v['estado'], ['pendiente', 'aprobada'])): ?>
                                        <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/vacaciones/<?= $v['id'] ?>/cancelar"
                                              onsubmit="return confirm('¿Cancelar esta solicitud de vacaciones?')">
                                            <input type="hidden" name="_csrf_token"
                                                   value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-x"></i> Cancelar
                                            </button>
                                        </form>
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

<!-- Nueva Vacacion Modal -->
<div class="modal fade" id="nuevaVacacionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Solicitar Vacaciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/vacaciones">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_inicio" class="form-control" required
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Fin <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_fin" class="form-control" required
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="normal">Vacaciones</option>
                            <option value="permiso">Permiso</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comentario <small class="text-muted">(opcional)</small></label>
                        <textarea name="comentario" class="form-control" rows="2"
                                  placeholder="Información adicional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Solicitar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
