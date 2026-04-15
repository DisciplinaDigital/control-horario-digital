/**
 * Control Horario Digital - Admin JS
 * Handles: Dashboard stats refresh, color pickers, logo preview, modals
 */
(function () {
    'use strict';

    const APP_URL    = window.APP_URL ?? document.querySelector('meta[name="app-url"]')?.content ?? '';
    const CSRF_TOKEN = window.CSRF_TOKEN ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // =============================================
    // DASHBOARD STATS AUTO-REFRESH
    // =============================================
    function refreshStats() {
        fetch(APP_URL + '/admin/api/stats', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(json => {
            if (!json.success) return;
            const d = json.data;

            const mappings = {
                'stat-activos':     d.usuarios_activos_hoy,
                'stat-incidencias': d.incidencias_pendientes,
                'stat-fichajes':    d.fichajes_hoy,
                'stat-total':       d.total_usuarios,
            };

            Object.entries(mappings).forEach(([id, value]) => {
                const el = document.getElementById(id);
                if (el && el.textContent != value) {
                    el.textContent = value;
                    el.closest('.stats-card')?.classList.add('stats-updated');
                    setTimeout(() => el.closest('.stats-card')?.classList.remove('stats-updated'), 1000);
                }
            });

            // Update incidencias badge in sidebar
            const pending = d.incidencias_pendientes;
            const sidebarBadge = document.querySelector('.sidebar-link[href*="incidencias"] .badge');
            if (sidebarBadge) {
                sidebarBadge.textContent = pending;
                sidebarBadge.style.display = pending > 0 ? '' : 'none';
            }
        })
        .catch(() => {}); // Silently fail
    }

    // Only refresh stats on dashboard page
    if (document.getElementById('stat-activos')) {
        refreshStats();
        setInterval(refreshStats, 60000); // Refresh every minute
    }

    // =============================================
    // COLOR PICKER LIVE PREVIEW
    // =============================================
    const cssVarMap = {
        color_primary:   '--color-primary',
        color_secondary: '--color-secondary',
        color_accent:    '--color-accent',
        color_success:   '--color-success',
        color_warning:   '--color-warning',
        color_danger:    '--color-danger',
        color_bg:        '--color-bg',
        color_text:      '--color-text',
    };

    // Apply live color preview as user picks colors
    document.querySelectorAll('input[type="color"]').forEach(input => {
        const key    = input.name;
        const cssVar = cssVarMap[key];

        if (cssVar) {
            input.addEventListener('input', (e) => {
                document.documentElement.style.setProperty(cssVar, e.target.value);

                // Sync hex input
                const hexInput = document.getElementById('hex_' + key);
                if (hexInput) hexInput.value = e.target.value;
            });
        }
    });

    // =============================================
    // LOGO UPLOAD PREVIEW
    // =============================================
    const logoInput = document.getElementById('logoInput');
    if (logoInput) {
        logoInput.addEventListener('change', function() {
            const file    = this.files[0];
            const preview = document.getElementById('logoPreview');
            const img     = document.getElementById('logoPreviewImg');

            if (file && preview && img) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    img.src             = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // =============================================
    // Sidebar: gestionado por el script inline de admin.php
    // (toggleSidebar / closeSidebar definidos allí para evitar conflictos)
    // =============================================
    // TABLE SEARCH (Admin tables)
    // =============================================
    const tableSearch = document.getElementById('adminTableSearch');
    if (tableSearch) {
        tableSearch.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('[data-searchable] tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // =============================================
    // CONFIRM DIALOGS
    // =============================================
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const msg = el.dataset.confirm;
            if (!confirm(msg)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // =============================================
    // DATE RANGE SHORTCUTS (for filters)
    // =============================================
    window.setDateRange = function(days) {
        const to   = new Date();
        const from = new Date();
        from.setDate(from.getDate() - days);

        const fromStr = from.toISOString().slice(0, 10);
        const toStr   = to.toISOString().slice(0, 10);

        const fromInput = document.querySelector('input[name="fecha_desde"]');
        const toInput   = document.querySelector('input[name="fecha_hasta"]');

        if (fromInput) fromInput.value = fromStr;
        if (toInput)   toInput.value   = toStr;
    };

})();
