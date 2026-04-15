<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0"><i class="bi bi-people me-2"></i>Gestión de Usuarios</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
            <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
        </button>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="bi bi-check-circle me-2"></i><?= $success ?>
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
                <div class="col-12 col-sm-5 col-md-4">
                    <input type="search" name="search" class="form-control form-control-sm"
                           placeholder="Buscar por nombre o email..."
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <select name="role" class="form-select form-select-sm">
                        <option value="">Todos los roles</option>
                        <option value="usuario" <?= ($filters['role'] ?? '') === 'usuario' ? 'selected' : '' ?>>Empleados</option>
                        <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admins</option>
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <select name="departamento" class="form-select form-select-sm">
                        <option value="">Todos los deptos.</option>
                        <?php foreach ($departamentos as $d): ?>
                            <option value="<?= htmlspecialchars($d['departamento']) ?>"
                                    <?= ($filters['departamento'] ?? '') === $d['departamento'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['departamento']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-1 col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search"></i><span class="d-none d-sm-inline ms-1">Filtrar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 usuarios-table">
                    <thead class="table-light">
                        <tr>
                            <th class="d-none d-lg-table-cell">#</th>
                            <th>Nombre</th>
                            <th class="d-none d-md-table-cell">Email</th>
                            <th>Rol</th>
                            <th class="d-none d-lg-table-cell">Departamento</th>
                            <th class="d-none d-xl-table-cell">Vacaciones</th>
                            <th class="d-none d-lg-table-cell">Último Acceso</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No se encontraron usuarios</td></tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u): ?>
                            <tr class="<?= !$u['activo'] ? 'table-secondary opacity-75' : '' ?>">
                                <td class="d-none d-lg-table-cell"><small class="text-muted"><?= $u['id'] ?></small></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?></div>
                                    <div class="d-md-none text-muted" style="font-size:0.75rem"><?= htmlspecialchars($u['email']) ?></div>
                                </td>
                                <td class="d-none d-md-table-cell"><small><?= htmlspecialchars($u['email']) ?></small></td>
                                <td>
                                    <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                        <?= $u['role'] === 'admin' ? 'Admin' : 'Empleado' ?>
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell"><small><?= htmlspecialchars($u['departamento'] ?? '—') ?></small></td>
                                <td class="d-none d-xl-table-cell">
                                    <small><?= $u['vacaciones_usadas'] ?? 0 ?>/<?= $u['vacaciones_totales'] ?? 22 ?> días</small>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <small class="text-muted">
                                        <?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca' ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge <?= $u['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-outline-primary btn-sm"
                                                onclick='editarUsuario(<?= json_encode($u) ?>)'
                                                title="Editar usuario">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm"
                                                onclick='resetPassword(<?= $u["id"] ?>, <?= json_encode($u["nombre"] . " " . $u["apellidos"]) ?>)'
                                                title="Resetear contraseña">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        <?php if ($u['activo']): ?>
                                        <form method="POST"
                                              action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/usuarios/<?= $u['id'] ?>/eliminar"
                                              onsubmit="return confirm('¿Desactivar este usuario? Sus datos se conservarán por requisito legal.')">
                                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                                            <button type="submit" class="btn btn-outline-warning btn-sm" title="Desactivar">
                                                <i class="bi bi-person-slash"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST"
                                              action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/usuarios/<?= $u['id'] ?>/reactivar"
                                              onsubmit="return confirm('¿Reactivar este usuario?')">
                                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm" title="Reactivar">
                                                <i class="bi bi-person-check"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small">
            <?= count($usuarios) ?> usuario(s) &mdash; Los usuarios eliminados se conservan por requisito legal (4 años)
        </div>
    </div>
</div>

<!-- Nuevo Usuario Modal -->
<div class="modal fade" id="nuevoUsuarioModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/admin/usuarios">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="apellidos" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rol</label>
                            <select name="role" class="form-select">
                                <option value="usuario">Empleado</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Departamento</label>
                            <input type="text" name="departamento" class="form-control" list="deptoList" placeholder="Ej: Marketing">
                            <datalist id="deptoList">
                                <?php foreach ($departamentos as $d): ?>
                                    <option value="<?= htmlspecialchars($d['departamento']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control" placeholder="+34 600...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Días Vacaciones/Año</label>
                            <input type="number" name="dias_vacaciones_anuales" class="form-control" value="22" min="0" max="60">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Editar Usuario Modal -->
<div class="modal fade" id="editarUsuarioModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editarUsuarioForm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="apellidos" id="edit_apellidos" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" id="edit_email" class="form-control" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nueva Contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label>
                            <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rol</label>
                            <select name="role" id="edit_role" class="form-select">
                                <option value="usuario">Empleado</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Departamento</label>
                            <input type="text" name="departamento" id="edit_departamento" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" id="edit_telefono" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Días Vacaciones/Año</label>
                            <input type="number" name="dias_vacaciones_anuales" id="edit_diasvac" class="form-control" min="0" max="60">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select name="activo" id="edit_activo" class="form-select">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <hr class="my-1">
                            <label class="form-label">
                                Radio máx. GPS personal (metros)
                                <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip"
                                   title="Anula el radio global. Usa 0 para sin límite (comerciales). Vacío = usa el radio global del sistema."></i>
                            </label>
                            <div class="input-group">
                                <input type="number" name="max_distance_override" id="edit_maxdist"
                                       class="form-control" min="0" max="999999"
                                       placeholder="Vacío = usar configuración global">
                                <span class="input-group-text">m</span>
                            </div>
                            <small class="text-muted">
                                <strong>0</strong> = sin límite de distancia &nbsp;|&nbsp;
                                <strong>Vacío</strong> = usar radio global del sistema
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Resetear Contraseña -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="resetPasswordForm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-key me-2"></i>Resetear Contraseña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="reset_nombre_text"></p>

                    <!-- Generador de contraseña segura -->
                    <div class="card border-0 bg-light mb-3 p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold small"><i class="bi bi-magic me-1"></i>Generar contraseña segura</span>
                            <button type="button" class="btn btn-primary btn-sm" onclick="generarPassword()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Generar
                            </button>
                        </div>
                        <div class="input-group" id="generada_wrap" style="display:none!important">
                            <input type="text" id="password_generada" class="form-control form-control-sm font-monospace"
                                   readonly style="letter-spacing:1px">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copiarPassword()"
                                    title="Copiar al portapapeles" id="btn_copiar">
                                <i class="bi bi-clipboard" id="icon_copiar"></i>
                            </button>
                            <button type="button" class="btn btn-success btn-sm" onclick="usarPassword()"
                                    title="Usar esta contraseña">
                                <i class="bi bi-check-lg me-1"></i>Usar
                            </button>
                        </div>
                    </div>

                    <!-- Entrada manual -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nueva contraseña</label>
                        <div class="input-group">
                            <input type="password" name="password" id="reset_password" class="form-control"
                                   minlength="8" required autocomplete="new-password"
                                   placeholder="Mínimo 8 caracteres">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleVer('reset_password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Confirmar contraseña</label>
                        <div class="input-group">
                            <input type="password" name="password_confirm" id="reset_password_confirm" class="form-control"
                                   minlength="8" required autocomplete="new-password"
                                   placeholder="Repite la contraseña">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleVer('reset_password_confirm', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm text-dark">
                        <i class="bi bi-key me-1"></i> Establecer Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const APP_URL_BASE = '<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>';
function editarUsuario(u) {
    document.getElementById('editarUsuarioForm').action = APP_URL_BASE + '/admin/usuarios/' + u.id;
    document.getElementById('edit_nombre').value = u.nombre;
    document.getElementById('edit_apellidos').value = u.apellidos;
    document.getElementById('edit_email').value = u.email;
    document.getElementById('edit_role').value = u.role;
    document.getElementById('edit_departamento').value = u.departamento || '';
    document.getElementById('edit_telefono').value = u.telefono || '';
    document.getElementById('edit_diasvac').value = u.dias_vacaciones_anuales;
    document.getElementById('edit_activo').value = u.activo;
    // Radio GPS personal: null/undefined → vacío (usa global)
    document.getElementById('edit_maxdist').value = (u.max_distance_override !== null && u.max_distance_override !== undefined)
        ? u.max_distance_override : '';
    // Activar tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    new bootstrap.Modal(document.getElementById('editarUsuarioModal')).show();
}

function resetPassword(id, nombre) {
    document.getElementById('resetPasswordForm').action = APP_URL_BASE + '/admin/usuarios/' + id + '/reset-password';
    document.getElementById('reset_nombre_text').textContent = 'Establecer nueva contraseña para: ' + nombre;
    document.querySelectorAll('#resetPasswordForm input[type="password"]').forEach(i => i.value = '');
    document.getElementById('password_generada').value = '';
    document.getElementById('generada_wrap').style.setProperty('display', 'none', 'important');
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

function generarPassword() {
    const upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lower  = 'abcdefghjkmnpqrstuvwxyz';
    const digits = '23456789';
    const syms   = '!@#$%&*?';
    const all    = upper + lower + digits + syms;
    const arr    = new Uint32Array(16);
    crypto.getRandomValues(arr);
    // Garantizar al menos uno de cada tipo
    let pwd = [
        upper[arr[0]  % upper.length],
        upper[arr[1]  % upper.length],
        lower[arr[2]  % lower.length],
        lower[arr[3]  % lower.length],
        digits[arr[4] % digits.length],
        digits[arr[5] % digits.length],
        syms[arr[6]   % syms.length],
        syms[arr[7]   % syms.length],
    ];
    for (let i = 8; i < 16; i++) pwd.push(all[arr[i] % all.length]);
    // Fisher-Yates shuffle
    const rnd = new Uint32Array(pwd.length);
    crypto.getRandomValues(rnd);
    for (let i = pwd.length - 1; i > 0; i--) {
        const j = rnd[i] % (i + 1);
        [pwd[i], pwd[j]] = [pwd[j], pwd[i]];
    }
    const pass = pwd.join('');
    document.getElementById('password_generada').value = pass;
    document.getElementById('generada_wrap').style.removeProperty('display');
    document.getElementById('icon_copiar').className = 'bi bi-clipboard';
    document.getElementById('btn_copiar').className = 'btn btn-outline-secondary btn-sm';
}

function usarPassword() {
    const pass = document.getElementById('password_generada').value;
    document.getElementById('reset_password').value         = pass;
    document.getElementById('reset_password_confirm').value = pass;
    // Mostrar en claro para que el admin confirme visualmente
    document.getElementById('reset_password').type         = 'text';
    document.getElementById('reset_password_confirm').type = 'text';
}

function copiarPassword() {
    const pass = document.getElementById('password_generada').value;
    if (!pass) return;
    navigator.clipboard.writeText(pass).then(() => {
        document.getElementById('icon_copiar').className = 'bi bi-clipboard-check';
        document.getElementById('btn_copiar').className  = 'btn btn-success btn-sm';
        setTimeout(() => {
            document.getElementById('icon_copiar').className = 'bi bi-clipboard';
            document.getElementById('btn_copiar').className  = 'btn btn-outline-secondary btn-sm';
        }, 2000);
    });
}

function toggleVer(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
