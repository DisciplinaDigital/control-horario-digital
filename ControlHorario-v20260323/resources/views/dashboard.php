<div class="container-fluid py-3">

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

        <!-- Left column: Clock + Fichaje button -->
        <div class="col-lg-5">

            <!-- Digital Clock Card -->
            <div class="card dashboard-clock-card mb-4">
                <div class="card-body text-center py-4">
                    <div class="clock-display" id="clockDisplay">--:--:--</div>
                    <div class="clock-date" id="clockDate">---</div>

                    <!-- Location Status -->
                    <div class="location-status mt-3" id="locationStatus">
                        <span class="location-indicator" id="locationIndicator">
                            <i class="bi bi-geo-alt-fill"></i>
                        </span>
                        <small id="locationText" class="ms-2">Obteniendo ubicación...</small>
                    </div>

                    <!-- GPS Debug Panel (ayuda a diagnosticar coordenadas) -->
                    <div class="mt-2 text-center">
                        <button class="btn btn-link btn-sm p-0 text-white-50" type="button"
                                data-bs-toggle="collapse" data-bs-target="#gpsDebugPanel" style="font-size:.75rem">
                            <i class="bi bi-bug me-1"></i>Info GPS
                        </button>
                        <div class="collapse" id="gpsDebugPanel">
                            <div class="mt-2 p-2 rounded text-start" style="background:#f1f5f9;color:#1e293b;font-size:.72rem;line-height:1.6">
                                <div><strong>Mi posición:</strong>
                                    <span id="dbgMyLat">—</span>, <span id="dbgMyLon">—</span>
                                    <span style="color:#64748b">(±<span id="dbgAcc">—</span>m)</span>
                                </div>
                                <div><strong>Oficina config:</strong>
                                    <?= number_format((float)($officeLat ?? 0), 6) ?>,
                                    <?= number_format((float)($officeLon ?? 0), 6) ?>
                                </div>
                                <div><strong>Distancia calc.:</strong> <span id="dbgDist">—</span> m</div>
                                <div><strong>Radio máx.:</strong>
                                    <?php if ($maxDistanceOverride !== null): ?>
                                        <?= $maxDistanceOverride === 0 ? '∞ (sin límite — override usuario)' : $maxDistanceOverride . 'm (override usuario)' ?>
                                    <?php else: ?>
                                        <?= (int)($maxDistance ?? 30) ?>m (global)
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fichaje Button Card -->
            <div class="card fichaje-card mb-4">
                <div class="card-body text-center py-4">
                    <?php
                    $isEntrada = $estado === 'puede_entrada';
                    $btnClass  = $isEntrada ? 'btn-fichaje-entrada' : 'btn-fichaje-salida';
                    $btnIcon   = $isEntrada ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right';
                    $btnLabel  = $isEntrada ? 'FICHAR ENTRADA' : 'FICHAR SALIDA';
                    $tipoFichaje = $isEntrada ? 'entrada' : 'salida';
                    ?>

                    <div class="status-badge mb-3 <?= $isEntrada ? 'status-fuera' : 'status-dentro' ?>" id="estadoBadge">
                        <i class="bi <?= $isEntrada ? 'bi-circle' : 'bi-circle-fill' ?>"></i>
                        <?= $isEntrada ? 'FUERA DE LA OFICINA' : 'EN LA OFICINA' ?>
                    </div>

                    <button
                        class="btn-fichaje <?= $btnClass ?>"
                        id="fichajeBtn"
                        onclick="realizarFichaje('<?= $tipoFichaje ?>')"
                        data-tipo="<?= $tipoFichaje ?>"
                    >
                        <i class="bi <?= $btnIcon ?>" id="fichajeIcon"></i>
                        <span id="fichajeBtnLabel"><?= $btnLabel ?></span>
                    </button>

                    <div class="mt-3">
                        <small class="text-muted" id="fichajeStatus">Haz clic para registrar tu <?= $tipoFichaje ?></small>
                    </div>
                </div>
            </div>

            <!-- Today's Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-calendar-day me-2"></i>Hoy</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="summary-stat">
                                <div class="summary-value text-success">
                                    <?= $resumenDia['primera_entrada'] ? date('H:i', strtotime($resumenDia['primera_entrada'])) : '--:--' ?>
                                </div>
                                <small class="text-muted">Entrada</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="summary-stat">
                                <div class="summary-value text-danger">
                                    <?= $resumenDia['ultima_salida'] ? date('H:i', strtotime($resumenDia['ultima_salida'])) : '--:--' ?>
                                </div>
                                <small class="text-muted">Salida</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="summary-stat">
                                <div class="summary-value text-primary" id="horasHoy">
                                    <?= number_format($resumenDia['horas_trabajadas'] ?? 0, 1) ?>h
                                </div>
                                <small class="text-muted">Horas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column: Week summary + Recent fichajes -->
        <div class="col-lg-7">

            <!-- Week Summary -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Esta Semana</h6>
                    <span class="badge bg-primary"><?= number_format($resumenSemana['total_horas'] ?? 0, 1) ?>h totales</span>
                </div>
                <div class="card-body p-0">
                    <div class="week-grid">
                        <?php
                        $dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                        foreach ($resumenSemana['days'] as $i => $day):
                            $isToday = $day['date'] === date('Y-m-d');
                            $isPast  = $day['date'] < date('Y-m-d');
                            $hasFichajes = !empty($day['fichajes']);
                        ?>
                        <div class="week-day <?= $isToday ? 'today' : ($hasFichajes ? 'worked' : ($isPast ? 'past' : '')) ?>">
                            <div class="week-day-name"><?= $dayNames[$i] ?></div>
                            <div class="week-day-date"><?= date('d', strtotime($day['date'])) ?></div>
                            <div class="week-day-hours"><?= $day['horas'] > 0 ? number_format($day['horas'], 1) . 'h' : '-' ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent fichajes -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Últimos Fichajes</h6>
                    <a href="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/fichajes" class="btn btn-outline-primary btn-sm">
                        Ver todos
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="recentFichajesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Tipo</th>
                                    <th>Método</th>
                                    <th class="text-center">Integridad</th>
                                </tr>
                            </thead>
                            <tbody id="recentFichajesBody">
                                <?php if (empty($ultimosFichajes)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-clock-history fs-3 d-block mb-2"></i>
                                        No hay fichajes registrados
                                    </td></tr>
                                <?php else: ?>
                                    <?php foreach ($ultimosFichajes as $f): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($f['fecha_hora'])) ?></td>
                                        <td><strong><?= date('H:i:s', strtotime($f['fecha_hora'])) ?></strong></td>
                                        <td>
                                            <span class="badge <?= $f['tipo'] === 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                                                <i class="bi <?= $f['tipo'] === 'entrada' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right' ?> me-1"></i>
                                                <?= ucfirst($f['tipo']) ?>
                                            </span>
                                        </td>
                                        <td><small class="text-muted"><?= htmlspecialchars($f['metodo_registro']) ?></small></td>
                                        <td class="text-center">
                                            <i class="bi bi-shield-check text-success" title="Hash verificado"></i>
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
</div>

<script>
// IMPORTANTE: usar window.XXX (no const/let) para que fichajes.js los lea como globales
window.OFFICE_LAT   = <?= (float)($officeLat ?? 36.62906) ?>;
window.OFFICE_LON   = <?= (float)($officeLon ?? -4.82644) ?>;
// Si el usuario tiene un override personal, lo usamos; 0 = sin límite
window.MAX_DISTANCE = <?= $maxDistanceOverride !== null ? (int)$maxDistanceOverride : (int)($maxDistance ?? 30) ?>;
window.CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]').content;
window.APP_URL      = document.querySelector('meta[name="app-url"]').content;
</script>
<script src="<?= rtrim($_ENV['APP_URL'] ?? '', '/') ?>/assets/js/fichajes.js"></script>
