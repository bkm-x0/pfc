/**
 * public/js/api.js — Shared Fetch wrapper & utilities
 *
 * Exposes a global `App` object used by every page script.
 * All fetch calls go through App.api.*  so headers and error
 * handling are consistent.
 */

const App = (function () {
    // Detect base path so the app works in any subfolder
    const BASE = (function () {
        const scripts = document.querySelectorAll('script[src]');
        for (const s of scripts) {
            const m = s.src.match(/(.*)\/public\/js\//);
            if (m) return m[1];
        }
        return '';   // fallback: project root = site root
    })();

    // ── Toast system ──────────────────────────────────────────
    function toast(msg, type = 'success', duration = 3200) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const el = document.createElement('div');
        el.className = `toast toast--${type}`;
        el.innerHTML = `
            <span class="toast__icon">${type === 'success' ? '✓' : '✕'}</span>
            <span>${msg}</span>`;
        container.appendChild(el);

        setTimeout(() => {
            el.classList.add('removing');
            el.addEventListener('animationend', () => el.remove());
        }, duration);
    }

    // ── Badge helper ──────────────────────────────────────────
    function badgeHtml(status) {
        const map = {
            'Available':           'badge--available',
            'In Use':              'badge--in-use',
            'Under Maintenance':   'badge--under-maint',
            'Retired':             'badge--retired',
        };
        return `<span class="badge ${map[status] || ''}">${status}</span>`;
    }

    // ── Core fetch helpers ────────────────────────────────────
    async function request(url, options = {}) {
        const headers = { 'Accept': 'application/json', ...(options.headers || {}) };
        if (options.body) {
            headers['Content-Type'] = 'application/json';
            if (typeof options.body === 'object') {
                options.body = JSON.stringify(options.body);
            }
        }
        options.headers = headers;
        options.credentials = 'same-origin';   // send session cookie

        const res = await fetch(url, options);
        let json = {};
        try { json = await res.json(); } catch (_) { /* empty body */ }
        return { ok: res.ok, status: res.status, data: json };
    }

    const api = {
        // Auth
        login:  (username, password)  => request(`${BASE}/api/auth.php?action=login`,  { method: 'POST', body: { username, password } }),
        logout: ()                    => request(`${BASE}/api/auth.php?action=logout`, { method: 'POST' }),
        me:     ()                    => request(`${BASE}/api/auth.php?action=me`),

        // Equipment CRUD
        listEquipment:   ()           => request(`${BASE}/api/equipment.php`),
        getEquipment:    (id)         => request(`${BASE}/api/equipment.php?id=${id}`),
        createEquipment: (payload)    => request(`${BASE}/api/equipment.php`,           { method: 'POST', body: payload }),
        updateEquipment: (id, payload)=> request(`${BASE}/api/equipment.php?id=${id}`,  { method: 'PUT',  body: payload }),
        deleteEquipment: (id)         => request(`${BASE}/api/equipment.php?id=${id}`,  { method: 'DELETE' }),
    };

    // ── Auth guard for protected pages ────────────────────────
    async function requireAuth() {
        const { ok, data } = await api.me();
        if (!ok || !data.authenticated) {
            window.location.href = `${BASE}/public/pages/login.html`;
            return null;
        }
        return data.user;
    }

    // ── Logout helper ─────────────────────────────────────────
    async function logout() {
        await api.logout();
        window.location.href = `${BASE}/public/pages/login.html`;
    }

    return { BASE, toast, badgeHtml, api, requireAuth, logout };
})();
