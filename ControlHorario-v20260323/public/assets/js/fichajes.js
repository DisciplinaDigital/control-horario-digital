/**
 * Control Horario Digital - Fichajes JS
 * Handles: Geolocation, fichaje button, real-time status updates
 */
(function () {
    'use strict';

    // Configuration (set from PHP view)
    const OFFICE_LAT    = typeof window.OFFICE_LAT !== 'undefined' ? window.OFFICE_LAT : 36.62906;
    const OFFICE_LON    = typeof window.OFFICE_LON !== 'undefined' ? window.OFFICE_LON : -4.82644;
    const MAX_DISTANCE  = typeof window.MAX_DISTANCE !== 'undefined' ? window.MAX_DISTANCE : 30;
    const APP_URL_LOCAL = typeof window.APP_URL !== 'undefined' ? window.APP_URL : '';
    const CSRF          = typeof window.CSRF_TOKEN !== 'undefined' ? window.CSRF_TOKEN :
                          document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // State
    let currentLat       = null;
    let currentLon       = null;
    let currentPrecision = null;
    let watchId          = null;
    let isFichando       = false;

    // =============================================
    // GEOLOCATION
    // =============================================
    function initGeolocation() {
        const indicator = document.getElementById('locationIndicator');
        const text      = document.getElementById('locationText');

        if (!indicator || !text) return;

        if (!navigator.geolocation) {
            setLocationStatus('error', 'GPS no disponible en este dispositivo');
            return;
        }

        text.textContent = 'Obteniendo ubicación...';
        indicator.className = 'location-indicator';
        indicator.innerHTML = '<i class="bi bi-geo-alt"></i>';

        watchId = navigator.geolocation.watchPosition(
            onPositionSuccess,
            onPositionError,
            {
                enableHighAccuracy: true,
                timeout:            15000,
                maximumAge:         10000,
            }
        );
    }

    function onPositionSuccess(position) {
        currentLat       = position.coords.latitude;
        currentLon       = position.coords.longitude;
        currentPrecision = position.coords.accuracy;

        const distance = haversineDistance(currentLat, currentLon, OFFICE_LAT, OFFICE_LON);

        // Actualizar panel de debug GPS
        const elLat  = document.getElementById('dbgMyLat');
        const elLon  = document.getElementById('dbgMyLon');
        const elAcc  = document.getElementById('dbgAcc');
        const elDist = document.getElementById('dbgDist');
        if (elLat)  elLat.textContent  = currentLat.toFixed(6);
        if (elLon)  elLon.textContent  = currentLon.toFixed(6);
        if (elAcc)  elAcc.textContent  = Math.round(currentPrecision);
        if (elDist) elDist.textContent = Math.round(distance);

        // MAX_DISTANCE === 0 → sin límite (comercial viajero)
        if (MAX_DISTANCE === 0) {
            setLocationStatus('ok', `Ubicación obtenida (sin restricción de radio)`);
        } else if (distance <= MAX_DISTANCE) {
            setLocationStatus('ok', `En la oficina (${Math.round(distance)}m)`);
        } else if (distance <= MAX_DISTANCE * 3) {
            setLocationStatus('warning', `Fuera del área (${Math.round(distance)}m)`);
        } else {
            setLocationStatus('error', `Lejos de la oficina (${Math.round(distance)}m)`);
        }
    }

    function onPositionError(error) {
        currentLat = null;
        currentLon = null;

        const messages = {
            1: 'Permiso de ubicación denegado',
            2: 'No se puede obtener la ubicación',
            3: 'Tiempo de espera agotado',
        };
        setLocationStatus('error', messages[error.code] || 'Error de GPS');
    }

    function setLocationStatus(type, message) {
        const indicator = document.getElementById('locationIndicator');
        const text      = document.getElementById('locationText');
        if (!indicator || !text) return;

        const icons = {
            ok:      'bi-geo-alt-fill',
            warning: 'bi-geo-alt',
            error:   'bi-geo-alt',
        };

        indicator.className = `location-indicator ${type}`;
        indicator.innerHTML = `<i class="bi ${icons[type] || 'bi-geo-alt'}"></i>`;
        text.textContent    = message;
    }

    // =============================================
    // FICHAJE ACTION
    // =============================================
    window.realizarFichaje = async function(tipo) {
        if (isFichando) return;

        const btn       = document.getElementById('fichajeBtn');
        const icon      = document.getElementById('fichajeIcon');
        const label     = document.getElementById('fichajeBtnLabel');
        const status    = document.getElementById('fichajeStatus');

        isFichando = true;

        // Show loading state
        if (btn) btn.disabled = true;
        if (icon) icon.className = 'bi bi-arrow-repeat spin';
        if (label) label.textContent = 'Registrando...';
        if (status) status.textContent = 'Por favor espera...';

        try {
            const body = new URLSearchParams({
                tipo:      tipo,
                lat:       currentLat ?? '',
                lon:       currentLon ?? '',
                precision: currentPrecision ?? '',
                _csrf_token: CSRF,
            });

            const response = await fetch(APP_URL_LOCAL + '/api/fichajes', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN':      CSRF,
                    'X-Requested-With':  'XMLHttpRequest',
                },
                body: body.toString(),
            });

            const data = await response.json();

            if (data.success) {
                onFichajeSuccess(data, tipo);
            } else {
                onFichajeError(data.message || 'Error al registrar fichaje');
            }
        } catch (e) {
            onFichajeError('Error de red. Comprueba tu conexión.');
        } finally {
            isFichando = false;
        }
    };

    function onFichajeSuccess(data, tipoRealizado) {
        const btn     = document.getElementById('fichajeBtn');
        const icon    = document.getElementById('fichajeIcon');
        const label   = document.getElementById('fichajeBtnLabel');
        const status  = document.getElementById('fichajeStatus');
        const estadoBadge = document.getElementById('estadoBadge');

        // Brief success animation
        if (btn) {
            btn.style.transform = 'scale(1.1)';
            setTimeout(() => {
                if (btn) btn.style.transform = '';
            }, 300);
        }

        // Switch button to opposite action
        const nuevoTipo = tipoRealizado === 'entrada' ? 'salida' : 'entrada';
        const esEntrada = nuevoTipo === 'entrada';

        if (btn) {
            btn.dataset.tipo = nuevoTipo;
            btn.className = `btn-fichaje ${esEntrada ? 'btn-fichaje-entrada' : 'btn-fichaje-salida'}`;
            btn.onclick = () => realizarFichaje(nuevoTipo);
            btn.disabled = false;
        }

        if (icon) {
            icon.className = `bi ${esEntrada ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right'}`;
        }

        if (label) {
            label.textContent = esEntrada ? 'FICHAR ENTRADA' : 'FICHAR SALIDA';
        }

        if (status) {
            status.textContent = data.message || 'Fichaje registrado correctamente';
        }

        // Update status badge
        if (estadoBadge) {
            if (!esEntrada) {
                // Just checked in (was entrada, now need salida)
                estadoBadge.className = 'status-badge mb-3 status-dentro';
                estadoBadge.innerHTML = '<i class="bi bi-circle-fill"></i> EN LA OFICINA';
            } else {
                estadoBadge.className = 'status-badge mb-3 status-fuera';
                estadoBadge.innerHTML = '<i class="bi bi-circle"></i> FUERA DE LA OFICINA';
            }
        }

        // Update hours display
        if (data.fichaje) {
            addRowToTable(data.fichaje, tipoRealizado);
        }

        // Show success toast
        showToast(data.message || 'Fichaje registrado', 'success');
    }

    function onFichajeError(message) {
        const btn    = document.getElementById('fichajeBtn');
        const icon   = document.getElementById('fichajeIcon');
        const label  = document.getElementById('fichajeBtnLabel');
        const status = document.getElementById('fichajeStatus');
        const tipo   = btn ? btn.dataset.tipo : 'entrada';
        const esEntrada = tipo === 'entrada';

        if (btn) btn.disabled = false;
        if (icon) icon.className = `bi ${esEntrada ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right'}`;
        if (label) label.textContent = esEntrada ? 'FICHAR ENTRADA' : 'FICHAR SALIDA';
        if (status) {
            status.textContent = message;
            status.style.color = 'var(--color-danger)';
            setTimeout(() => {
                if (status) status.style.color = '';
            }, 3000);
        }

        showToast(message, 'error');
    }

    function addRowToTable(fichaje, tipo) {
        const tbody = document.getElementById('recentFichajesBody');
        if (!tbody) return;

        const date = new Date(fichaje.fecha_hora.replace(' ', 'T'));
        const dateStr = `${String(date.getDate()).padStart(2,'0')}/${String(date.getMonth()+1).padStart(2,'0')}/${date.getFullYear()}`;
        const timeStr = `${String(date.getHours()).padStart(2,'0')}:${String(date.getMinutes()).padStart(2,'0')}:${String(date.getSeconds()).padStart(2,'0')}`;

        const badgeClass = tipo === 'entrada' ? 'bg-success' : 'bg-danger';
        const icon       = tipo === 'entrada' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${dateStr}</td>
            <td><strong>${timeStr}</strong></td>
            <td><span class="badge ${badgeClass}"><i class="bi ${icon} me-1"></i>${tipo.charAt(0).toUpperCase() + tipo.slice(1)}</span></td>
            <td><small class="text-muted">web</small></td>
            <td class="text-center"><i class="bi bi-shield-check text-success"></i></td>
        `;
        row.style.background = 'rgba(37,99,235,0.05)';
        row.style.transition = 'background 1s ease';

        tbody.insertBefore(row, tbody.firstChild);
        setTimeout(() => { row.style.background = ''; }, 2000);

        // Remove "no records" row if exists
        const emptyRow = tbody.querySelector('td[colspan]');
        if (emptyRow) emptyRow.closest('tr').remove();
    }

    // =============================================
    // TOAST NOTIFICATIONS
    // =============================================
    function showToast(message, type = 'info') {
        const container = getOrCreateToastContainer();
        const id   = 'toast-' + Date.now();
        const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };
        const colors = { success: 'text-success', error: 'text-danger', info: 'text-primary' };

        const toastEl = document.createElement('div');
        toastEl.id = id;
        toastEl.className = 'toast align-items-center border-0 show';
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${icons[type] || 'bi-info-circle-fill'} ${colors[type] || ''} fs-5"></i>
                    ${escHtml(message)}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(toastEl);

        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function getOrCreateToastContainer() {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return container;
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = String(str);
        return d.innerHTML;
    }

    // =============================================
    // DISTANCE CALCULATOR
    // =============================================
    function haversineDistance(lat1, lon1, lat2, lon2) {
        const R  = 6371000;
        const dL = (lat2 - lat1) * Math.PI / 180;
        const dG = (lon2 - lon1) * Math.PI / 180;
        const a  = Math.sin(dL/2) * Math.sin(dL/2) +
                   Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                   Math.sin(dG/2) * Math.sin(dG/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    // =============================================
    // SPINNING ANIMATION
    // =============================================
    const style = document.createElement('style');
    style.textContent = `
        .spin { animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    `;
    document.head.appendChild(style);

    // =============================================
    // INIT
    // =============================================
    document.addEventListener('DOMContentLoaded', () => {
        initGeolocation();
    });

    // Also init immediately if DOM is already loaded
    if (document.readyState !== 'loading') {
        initGeolocation();
    }

})();
