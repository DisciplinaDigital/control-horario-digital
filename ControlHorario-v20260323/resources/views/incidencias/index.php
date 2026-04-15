<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Mis Incidencias</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaIncidenciaModal">
            <i class="bi bi-plus-lg me-1"></i> Nueva Incidencia
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

    <!-- Info box -->
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle me-2"></i>
        <strong>¿Qué es una incidencia?</strong>
        Si olvidaste fichar entrada o salida, o si hubo algún error, solicita una corrección aquí.
        El administrador la revisará y, si es aceptada, añadirá el fichaje correspondiente.
    </div>

    <!-- Incidencias table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Número</th>
                            <th>Tipo</th>
                            <th>Fecha Fichaje</th>
                            <th>Hora Sol.</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Fecha Sol.</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($incidencias)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-check-circle fs-2 d-block mb-2"></i>
                                    No tienes incidencias registradas
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($incidencias as $inc): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($inc['numero_incidencia']) ?></code></td>
                                <td>
                                    <?php
                                    $tipoLabels = [
                                        'olvido_entrada' => ['bg-warning text-dark', 'Olvido Entrada'],
                                        'olvido_salida'  => ['bg-warning text-dark', 'Olvido Salida'],
                                        'error_ubicacion' => ['bg-info text-dark', 'Error Ubicación'],
                                        'otro'           => ['bg-secondary', 'Otro'],
                                    ];
                                    $tipoInfo = $tipoLabels[$inc['tipo']] ?? ['bg-secondary', $inc['tipo']];
                                    ?>
                                    <span class="badge <?= $tipoInfo[0] ?>"><?= $tipoInfo[1] ?></span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($inc['fecha_fichaje'])) ?></td>
                                <td><?= $inc['hora_solicitada'] ? date('H:i', strtotime($inc['hora_solicitada'])) : '—' ?></td>
                                <td>
                                    <span title="<?= htmlspecialchars($inc['razon']) ?>">
                                        <?= htmlspecialchars(mb_strimwidth($inc['razon'], 0, 50, '...')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $estadoMap = [
                                        'pendiente' => ['bg-warning text-dark', 'bi-clock', 'Pendiente'],
                                        'aceptada'  => ['bg-success', 'bi-check-circle', 'Aceptada'],
                                        'rechazada' => ['bg-danger', 'bi-x-circle', 'Rechazada'],
                                    ];
                                    $est = $estadoMap[$inc['estado']] ?? ['bg-secondary', 'bi-question', $inc['estado']];
                                    ?>
                                    <span class="badge <?= $est[0] ?>">
                                        <i class="bi <?= $est[1] ?> me-1"></i><?= $est[2] ?>
                                    </span>
                                    <?php if ($inc['comentario_admin']): ?>
                                        <i class="bi bi-chat-left-text text-muted ms-1"
                                           title="<?= htmlspecialchars($inc['comentario_admin']) ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= date('d/m/Y', strtotime($inc['fecha_solicitud'])) ?></small></td>
                                <td>
                                    <?php if ($inc['estado'] === 'pendiente'): ?>
                                        <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/incidencias/<?= $inc['id'] ?>/cancelar"
                                              onsubmit="return confirm('¿Cancelar esta incidencia?')">
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

<!-- Nueva Incidencia Modal -->
<div class="modal fade" id="nuevaIncidenciaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Nueva Incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/incidencias">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipo de Incidencia</label>
                        <select name="tipo" class="form-select" required>
                            <option value="olvido_entrada">Olvido de Fichaje de Entrada</option>
                            <option value="olvido_salida">Olvido de Fichaje de Salida</option>
                            <option value="error_ubicacion">Error de Ubicación</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha del Fichaje</label>
                        <input type="date" name="fecha_fichaje" class="form-control" required
                               max="<?= date('Y-m-d') ?>"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hora Solicitada <small class="text-muted">(opcional)</small></label>
                        <input type="time" name="hora_solicitada" class="form-control">
                        <small class="text-muted">Indica la hora que deberías haber fichado</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo <span class="text-danger">*</span></label>
                        <textarea name="razon" class="form-control" rows="3" required
                                  placeholder="Explica brevemente el motivo de la incidencia (mínimo 10 caracteres)..."
                                  minlength="10"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Enviar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
