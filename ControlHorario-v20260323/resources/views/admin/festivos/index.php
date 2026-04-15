<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 mb-0"><i class="bi bi-calendar-event me-2"></i>Gestión de Festivos</h2>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/festivos/importar">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                <input type="hidden" name="year" value="<?= $year ?>">
                <button type="submit" class="btn btn-outline-info btn-sm"
                        onclick="return confirm('¿Importar festivos nacionales para <?= $year ?>?')">
                    <i class="bi bi-download me-1"></i><span class="d-none d-sm-inline">Importar Nacionales </span><?= $year ?>
                </button>
            </form>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#nuevoFestivoModal">
                <i class="bi bi-plus me-1"></i><span class="d-none d-sm-inline">Añadir </span>Festivo
            </button>
        </div>
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

    <!-- Year Selector -->
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        <span class="text-muted small">Año:</span>
        <?php for ($y = date('Y') - 1; $y <= date('Y') + 3; $y++): ?>
            <a href="?year=<?= $y ?>"
               class="btn btn-sm <?= $y == $year ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= $y ?>
            </a>
        <?php endfor; ?>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Festivos <?= $year ?> <span class="text-muted fw-normal">(<?= count($festivos) ?> días)</span></h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th class="d-none d-md-table-cell">Día</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($festivos)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No hay festivos para <?= $year ?>.<br>
                                    <button class="btn btn-link" data-bs-toggle="modal" data-bs-target="#nuevoFestivoModal">
                                        Añadir manualmente
                                    </button> o
                                    <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/festivos/importar" class="d-inline">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                                        <input type="hidden" name="year" value="<?= $year ?>">
                                        <button type="submit" class="btn btn-link">importar nacionales</button>
                                    </form>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $dayNames = ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                            foreach ($festivos as $f):
                                $dow = (int)date('N', strtotime($f['fecha']));
                            ?>
                            <tr class="<?= $dow >= 6 ? 'table-light' : '' ?>">
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($f['fecha'])) ?></strong>
                                    <!-- Día visible solo en móvil -->
                                    <span class="d-md-none text-muted ms-1" style="font-size:0.8rem"><?= $dayNames[$dow] ?></span>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="text-muted"><?= $dayNames[$dow] ?></span>
                                </td>
                                <td><?= htmlspecialchars($f['descripcion']) ?></td>
                                <td>
                                    <?php
                                    $tipoMap = [
                                        'nacional'   => 'bg-primary',
                                        'autonomico' => 'bg-info text-dark',
                                        'local'      => 'bg-success',
                                        'empresa'    => 'bg-warning text-dark',
                                    ];
                                    $tipoLabel = [
                                        'nacional'   => 'Nacional',
                                        'autonomico' => 'Autonóm.',
                                        'local'      => 'Local',
                                        'empresa'    => 'Empresa',
                                    ];
                                    ?>
                                    <span class="badge <?= $tipoMap[$f['tipo']] ?? 'bg-secondary' ?>">
                                        <?= $tipoLabel[$f['tipo']] ?? $f['tipo'] ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/festivos/<?= $f['id'] ?>/eliminar"
                                          onsubmit="return confirm('¿Eliminar festivo <?= htmlspecialchars($f['descripcion']) ?>?')">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                                        <input type="hidden" name="year" value="<?= $year ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
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

<!-- Nuevo Festivo Modal -->
<div class="modal fade" id="nuevoFestivoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Añadir Festivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/festivos">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción <span class="text-danger">*</span></label>
                        <input type="text" name="descripcion" class="form-control" required
                               placeholder="Ej: Día de Andalucía">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="nacional">Nacional</option>
                            <option value="autonomico">Autonómico</option>
                            <option value="local">Local</option>
                            <option value="empresa">Empresa</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus me-1"></i> Añadir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
