<div class="container-fluid py-3">
    <h2 class="h4 mb-4"><i class="bi bi-exclamation-triangle me-2"></i>Gestión de Incidencias</h2>

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
                        <option value="aceptada" <?= ($filters['estado'] ?? '') === 'aceptada' ? 'selected' : '' ?>>Aceptadas</option>
                        <option value="rechazada" <?= ($filters['estado'] ?? '') === 'rechazada' ? 'selected' : '' ?>>Rechazadas</option>
                    </select>
                </div>
                <div class="col-6 col-md-4">
                    <select name="usuario_id" class="form-select form-select-sm">
                        <option value="">Todos los empleados</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filters['usuario_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
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
                            <th class="d-none d-md-table-cell">Núm.</th>
                            <th>Empleado</th>
                            <th class="d-none d-lg-table-cell">Tipo</th>
                            <th class="d-none d-md-table-cell">Fecha</th>
                            <th class="d-none d-lg-table-cell">H. Sol.</th>
                            <th class="d-none d-xl-table-cell">Motivo</th>
                            <th>Estado</th>
                            <th class="d-none d-lg-table-cell">Solicitada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($incidencias)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No hay incidencias</td></tr>
                        <?php else: ?>
                            <?php foreach ($incidencias as $inc): ?>
                            <?php
                            $tipoLabels = [
                                'olvido_entrada'  => 'Olvido Entrada',
                                'olvido_salida'   => 'Olvido Salida',
                                'error_ubicacion' => 'Error Ubic.',
                                'otro'            => 'Otro',
                            ];
                            ?>
                            <tr>
                                <td class="d-none d-md-table-cell"><code class="small"><?= htmlspecialchars($inc['numero_incidencia']) ?></code></td>
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($inc['nombre'] . ' ' . $inc['apellidos']) ?></strong>
                                    <!-- Tipo + fecha visible solo en móvil -->
                                    <span class="d-lg-none text-muted" style="font-size:0.75rem">
                                        <?= $tipoLabels[$inc['tipo']] ?? $inc['tipo'] ?>
                                        <span class="d-md-none"> · <?= date('d/m/Y', strtotime($inc['fecha_fichaje'])) ?></span>
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <small><?= $tipoLabels[$inc['tipo']] ?? $inc['tipo'] ?></small>
                                </td>
                                <td class="d-none d-md-table-cell"><?= date('d/m/Y', strtotime($inc['fecha_fichaje'])) ?></td>
                                <td class="d-none d-lg-table-cell"><?= $inc['hora_solicitada'] ? date('H:i', strtotime($inc['hora_solicitada'])) : '—' ?></td>
                                <td class="d-none d-xl-table-cell">
                                    <span title="<?= htmlspecialchars($inc['razon']) ?>">
                                        <?= htmlspecialchars(mb_strimwidth($inc['razon'], 0, 45, '...')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $estMap = [
                                        'pendiente' => ['bg-warning text-dark', 'Pendiente'],
                                        'aceptada'  => ['bg-success', 'Aceptada'],
                                        'rechazada' => ['bg-danger', 'Rechazada'],
                                    ];
                                    $est = $estMap[$inc['estado']] ?? ['bg-secondary', $inc['estado']];
                                    ?>
                                    <span class="badge <?= $est[0] ?>"><?= $est[1] ?></span>
                                    <?php if ($inc['comentario_admin']): ?>
                                        <i class="bi bi-chat-left text-muted ms-1 d-none d-md-inline"
                                           title="<?= htmlspecialchars($inc['comentario_admin']) ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-lg-table-cell"><small><?= date('d/m/Y H:i', strtotime($inc['fecha_solicitud'])) ?></small></td>
                                <td>
                                    <?php if ($inc['estado'] === 'pendiente'): ?>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-success btn-sm"
                                                    onclick='abrirResolverModal(<?= $inc["id"] ?>, "aceptada", "<?= htmlspecialchars($inc["nombre"] . " " . $inc["apellidos"]) ?>")'
                                                    title="Aceptar">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm"
                                                    onclick='abrirResolverModal(<?= $inc["id"] ?>, "rechazada", "<?= htmlspecialchars($inc["nombre"] . " " . $inc["apellidos"]) ?>")'
                                                    title="Rechazar">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">Resuelta</small>
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
<div class="modal fade" id="resolverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="resolverModalHeader">
                <h5 class="modal-title" id="resolverModalTitle">Resolver Incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="resolverForm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                <input type="hidden" name="estado" id="resolverEstado">
                <div class="modal-body">
                    <p id="resolverDesc"></p>
                    <div class="mb-3">
                        <label class="form-label">Comentario para el empleado</label>
                        <textarea name="comentario" class="form-control" rows="3"
                                  placeholder="Explica el motivo de la resolución..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn" id="resolverBtn">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const APP_URL = document.querySelector('meta[name="app-url"]').content;
function abrirResolverModal(id, estado, nombre) {
    const form   = document.getElementById('resolverForm');
    const header = document.getElementById('resolverModalHeader');
    const btn    = document.getElementById('resolverBtn');
    const desc   = document.getElementById('resolverDesc');
    const title  = document.getElementById('resolverModalTitle');

    form.action = APP_URL + '/admin/incidencias/' + id + '/resolver';
    document.getElementById('resolverEstado').value = estado;

    if (estado === 'aceptada') {
        title.textContent = 'Aceptar Incidencia';
        header.className = 'modal-header border-success';
        btn.className = 'btn btn-success';
        btn.textContent = 'Aceptar';
        desc.innerHTML = `<span class="text-success"><i class="bi bi-check-circle"></i></span> Aceptar la incidencia de <strong>${nombre}</strong>. Si tiene hora solicitada, se creará el fichaje correspondiente.`;
    } else {
        title.textContent = 'Rechazar Incidencia';
        header.className = 'modal-header border-danger';
        btn.className = 'btn btn-danger';
        btn.textContent = 'Rechazar';
        desc.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle"></i></span> Rechazar la incidencia de <strong>${nombre}</strong>.`;
    }
    new bootstrap.Modal(document.getElementById('resolverModal')).show();
}
</script>
