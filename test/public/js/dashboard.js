/**
 * public/js/dashboard.js
 *
 * Fetches equipment list, renders stat cards + data table.
 * Inline delete with confirmation; edit opens the form page.
 */

(async function () {
    // ── Auth guard ──────────────────────────────────────────
    const user = await App.requireAuth();
    if (!user) return;

    // Populate topbar username
    const topUser = document.getElementById('topbar-username');
    if (topUser) topUser.textContent = user.username;

    // Logout button
    document.getElementById('btn-logout')?.addEventListener('click', () => App.logout());

    // ── Fetch & render ──────────────────────────────────────
    const tableBody = document.getElementById('equipment-tbody');
    const statsEl   = {
        total:       document.getElementById('stat-total'),
        available:   document.getElementById('stat-available'),
        inUse:       document.getElementById('stat-inuse'),
        maintenance: document.getElementById('stat-maintenance'),
        retired:     document.getElementById('stat-retired'),
    };

    await loadData();

    async function loadData() {
        tableBody.innerHTML = '<tr><td colspan="8"><div class="spinner"></div></td></tr>';

        try {
            const { ok, data } = await App.api.listEquipment();
            if (!ok) throw new Error(data.error || 'Failed to load');

            const items = data.data;
            renderStats(items);
            renderTable(items);
        } catch (err) {
            App.toast(err.message, 'error');
            tableBody.innerHTML = `
                <tr><td colspan="8">
                    <div class="empty-state">
                        <div class="empty-state__icon">⚠️</div>
                        <p>${err.message}</p>
                    </div>
                </td></tr>`;
        }
    }

    // ── Stats ─────────────────────────────────────────────
    function renderStats(items) {
        const counts = { Available: 0, 'In Use': 0, 'Under Maintenance': 0, Retired: 0 };
        items.forEach(i => { counts[i.status] = (counts[i.status] || 0) + 1; });

        statsEl.total.textContent       = items.length;
        statsEl.available.textContent   = counts['Available'];
        statsEl.inUse.textContent       = counts['In Use'];
        statsEl.maintenance.textContent = counts['Under Maintenance'];
        statsEl.retired.textContent     = counts['Retired'];
    }

    // ── Table ─────────────────────────────────────────────
    function renderTable(items) {
        if (items.length === 0) {
            tableBody.innerHTML = `
                <tr><td colspan="8">
                    <div class="empty-state">
                        <div class="empty-state__icon">📦</div>
                        <p>No equipment registered yet. <a href="${App.BASE}/public/pages/add.html">Add one now →</a></p>
                    </div>
                </td></tr>`;
            return;
        }

        tableBody.innerHTML = items.map(item => `
            <tr>
                <td><strong>${escHtml(item.name)}</strong></td>
                <td>${escHtml(item.category)}</td>
                <td>${escHtml(item.brand)}</td>
                <td><code style="font-size:.78rem;color:var(--text-muted)">${escHtml(item.serial_number)}</code></td>
                <td>${App.badgeHtml(item.status)}</td>
                <td>${escHtml(item.purchase_date)}</td>
                <td class="actions">
                    <button class="btn btn--ghost btn--sm" onclick="goEdit(${item.id})">✏️ Edit</button>
                    <button class="btn btn--danger btn--sm" onclick="confirmDelete(${item.id}, '${escHtml(item.name)}')">🗑️</button>
                </td>
            </tr>
        `).join('');
    }

    // ── Edit navigation ───────────────────────────────────
    window.goEdit = function (id) {
        window.location.href = `${App.BASE}/public/pages/add.html?id=${id}`;
    };

    // ── Delete with confirmation ──────────────────────────
    window.confirmDelete = async function (id, name) {
        if (!confirm(`Delete "${name}"?\n\nThis action cannot be undone.`)) return;

        const { ok, data } = await App.api.deleteEquipment(id);
        if (ok) {
            App.toast('Equipment deleted.', 'success');
            await loadData();   // refresh
        } else {
            App.toast(data.error || 'Delete failed.', 'error');
        }
    };

    // ── XSS-safe escaping ─────────────────────────────────
    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str ?? '')));
        return d.innerHTML;
    }
})();
