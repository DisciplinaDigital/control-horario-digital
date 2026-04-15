<div class="container-fluid py-3">
    <h2 class="h4 mb-4"><i class="bi bi-person me-2"></i>Mi Perfil</h2>

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

    <div class="row g-4">
        <!-- Profile info -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Datos Personales</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/perfil">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control"
                                   value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="apellidos" class="form-control"
                                   value="<?= htmlspecialchars($user['apellidos'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                            <small class="text-muted">El email no puede modificarse. Contacta al administrador.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control"
                                   value="<?= htmlspecialchars($user['telefono'] ?? '') ?>"
                                   placeholder="+34 600 000 000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Departamento</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['departamento'] ?? '—') ?>" disabled>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change password -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-key me-2"></i>Cambiar Contraseña</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/perfil/password">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(\App\Core\Session::getInstance()->getCsrfToken()) ?>">

                        <div class="mb-3">
                            <label class="form-label">Contraseña Actual <span class="text-danger">*</span></label>
                            <input type="password" name="password_actual" class="form-control" required autocomplete="current-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                            <input type="password" name="password_nueva" class="form-control" required
                                   minlength="8" autocomplete="new-password">
                            <small class="text-muted">Mínimo 8 caracteres</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmar Nueva Contraseña <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmacion" class="form-control" required
                                   minlength="8" autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key me-1"></i> Cambiar Contraseña
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account info -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información de Cuenta</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Rol</dt>
                        <dd class="col-sm-7">
                            <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                <?= $user['role'] === 'admin' ? 'Administrador' : 'Empleado' ?>
                            </span>
                        </dd>
                        <dt class="col-sm-5">Estado</dt>
                        <dd class="col-sm-7">
                            <span class="badge <?= $user['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $user['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </dd>
                        <dt class="col-sm-5">Días Vac.</dt>
                        <dd class="col-sm-7"><?= $user['dias_vacaciones_anuales'] ?> días/año</dd>
                        <dt class="col-sm-5">Último Acceso</dt>
                        <dd class="col-sm-7">
                            <small><?= $user['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($user['ultimo_acceso'])) : 'Este es tu primer acceso' ?></small>
                        </dd>
                        <dt class="col-sm-5">Miembro desde</dt>
                        <dd class="col-sm-7">
                            <small><?= date('d/m/Y', strtotime($user['created_at'])) ?></small>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
