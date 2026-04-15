/**
 * Control Horario Digital - Main App JS
 * Handles: CSRF, notifications, clock, fetch wrapper, flash messages
 */
(function () {
    'use strict';

    // =============================================
    // CONFIGURATION
    // =============================================
    const APP_URL = document.querySelector('meta[name="app-url"]')?.content ?? '';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // =============================================
    // FETCH API WRAPPER WITH CSRF
    // =============================================
    window.apiFetch = async function(url, options = {}) {
        const defaults = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
            },
        };

        const config = {
            ...defaults,
            ...options,
            headers: { ...defaults.headers, ...(options.headers || {}) },
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            return { ok: response.ok, status: response.status, data };
        } catch (err) {
            console.error('apiFetch error:', err);
            return { ok: false, status: 0, data: { success: false, message: 'Error de red' } };
        }
    };

    // =============================================
    // REAL-TIME CLOCK
    // =============================================
    function updateClock() {
        const clockEl = document.getElementById('clockDisplay');
        const dateEl  = document.getElementById('clockDate');
        if (!clockEl) return;

        const now = new Date();

        const hours   = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        clockEl.textContent = `${hours}:${minutes}:${seconds}`;

        if (dateEl) {
            const days    = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            const months  = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                             'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            dateEl.textContent = `${days[now.getDay()]}, ${now.getDate()} de ${months[now.getMonth()]} de ${now.getFullYear()}`;
        }
    }

    if (document.getElementById('clockDisplay')) {
        updateClock();
        setInterval(updateClock, 1000);
    }

    // =============================================
    // NOTIFICATIONS
    // Works with both desktop (#notifDropdown) and mobile (#notifDropdownMobile)
    // Badge class: .notif-badge   List class: .notif-list
    // =============================================

    function buildNotifHtml(items) {
        if (!items || !items.length) {
            return '<div class="dropdown-item text-muted text-center py-3">' +
                   '<i class="bi bi-bell-slash d-block fs-3 mb-1"></i>Sin notificaciones</div>';
        }
        return items.slice(0, 8).map(n => `
            <a class="dropdown-item py-2 ${n.leida ? '' : 'bg-light'}"
               href="#"
               onclick="marcarNotifLeida(${n.id}, event)">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi ${getTipoIcon(n.tipo)} mt-1 flex-shrink-0"
                       style="color: var(--color-${getTipoColor(n.tipo)})"></i>
                    <div class="flex-1 min-w-0">
                        <div class="fw-${n.leida ? 'normal' : 'bold'} small text-truncate">${escHtml(n.titulo)}</div>
                        <div class="text-muted" style="font-size:0.75rem">${formatDate(n.created_at)}</div>
                    </div>
                    ${!n.leida ? '<span class="badge bg-primary rounded-pill ms-auto" style="font-size:0.6rem">Nuevo</span>' : ''}
                </div>
            </a>
        `).join('');
    }

    async function loadNotifications() {
        const lists = document.querySelectorAll('.notif-list');
        if (!lists.length) return;

        try {
            const res  = await fetch(APP_URL + '/api/notificaciones', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            const html = buildNotifHtml(json.success ? json.data : []);
            lists.forEach(el => { el.innerHTML = html; });
        } catch (e) {
            const html = '<div class="dropdown-item text-muted text-center">Error al cargar</div>';
            lists.forEach(el => { el.innerHTML = html; });
        }
    }

    async function updateNotifCount() {
        try {
            const res  = await fetch(APP_URL + '/api/notificaciones/count', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            const count = json.count || 0;
            // Update ALL badge elements (desktop + mobile)
            document.querySelectorAll('.notif-badge').forEach(badge => {
                badge.textContent = count;
                badge.classList.toggle('d-none', count === 0);
            });
        } catch (e) {}
    }

    window.marcarNotifLeida = async function(id, e) {
        e.preventDefault();
        await fetch(APP_URL + '/api/notificaciones/' + id + '/leer', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        loadNotifications();
        updateNotifCount();
    };

    window.marcarTodasLeidas = async function() {
        await fetch(APP_URL + '/api/notificaciones/leer-todas', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        loadNotifications();
        updateNotifCount();
    };

    // Load notifications when ANY notification dropdown opens
    document.addEventListener('show.bs.dropdown', (e) => {
        const id = e.relatedTarget?.id ?? e.target?.id ?? '';
        if (id === 'notifDropdown' || id === 'notifDropdownMobile') {
            loadNotifications();
        }
    });

    // Poll notification count every 30 seconds
    updateNotifCount();
    setInterval(updateNotifCount, 30000);

    // =============================================
    // HELPER FUNCTIONS
    // =============================================
    function getTipoIcon(tipo) {
        const icons = {
            incidencia: 'bi-exclamation-triangle-fill',
            vacacion:   'bi-calendar-check-fill',
            sistema:    'bi-info-circle-fill',
            fichaje:    'bi-clock-fill',
        };
        return icons[tipo] || 'bi-bell-fill';
    }

    function getTipoColor(tipo) {
        const colors = {
            incidencia: 'warning',
            vacacion:   'success',
            sistema:    'primary',
            fichaje:    'accent',
        };
        return colors[tipo] || 'secondary';
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr.replace(' ', 'T'));
        const now = new Date();
        const diff = Math.floor((now - d) / 1000);

        if (diff < 60)  return 'Ahora mismo';
        if (diff < 3600) return `Hace ${Math.floor(diff / 60)} min`;
        if (diff < 86400) return `Hace ${Math.floor(diff / 3600)}h`;
        return d.toLocaleDateString('es-ES');
    }

    // =============================================
    // AUTO-DISMISS FLASH MESSAGES
    // =============================================
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // =============================================
    // EXPOSE GLOBALS
    // =============================================
    window.APP_URL    = APP_URL;
    window.CSRF_TOKEN = CSRF_TOKEN;

})();
