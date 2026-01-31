/**
 * public/js/add.js
 *
 * Dual-mode form page:
 *   • No ?id  → "Add Equipment" (POST)
 *   • ?id=N   → "Edit Equipment" (GET to pre-fill, then PUT)
 *
 * Handles server validation errors inline.
 */

(async function () {
    // ── Auth guard ──────────────────────────────────────────
    const user = await App.requireAuth();
    if (!user) return;

    document.getElementById('topbar-username')?.setAttribute('textContent', user.username);
    document.getElementById('btn-logout')?.addEventListener('click', () => App.logout());

    // ── DOM refs ────────────────────────────────────────────
    const pageTitle   = document.getElementById('page-title');
    const pageSubtitle = document.getElementById('page-subtitle');
    const form        = document.getElementById('equipment-form');
    const errBox      = document.getElementById('form-error');
    const submitBtn   = document.getElementById('btn-submit');

    const fields = ['name','category','brand','serial_number','status','purchase_date'];
    const getField = (name) => document.getElementById('field-' + name);

    // ── Detect mode ─────────────────────────────────────────
    const params = new URLSearchParams(window.location.search);
    const editId = params.get('id') ? parseInt(params.get('id'), 10) : null;
    let   isEdit = false;

    if (editId) {
        isEdit = true;
        pageTitle.textContent     = 'Edit Equipment';
        pageSubtitle.textContent  = 'Update the details below.';
        submitBtn.textContent     = '💾 Save Changes';

        // Pre-fill form
        try {
            const { ok, data } = await App.api.getEquipment(editId);
            if (!ok) throw new Error(data.error || 'Not found');

            const item = data.data;
            fields.forEach(f => {
                const el = getField(f);
                if (el) el.value = item[f] ?? '';
            });
        } catch (err) {
            App.toast(err.message, 'error');
            showError('Could not load equipment data.');
        }
    }

    // ── Form submit ─────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errBox.style.display = 'none';

        // Gather values
        const payload = {};
        fields.forEach(f => {
            const el = getField(f);
            if (el) payload[f] = el.value.trim();
        });

        // Client-side presence check
        const missing = fields.filter(f => !payload[f]);
        if (missing.length) {
            showError('Please fill in: ' + missing.join(', ').replace(/_/g, ' '));
            return;
        }

        submitBtn.disabled    = true;
        submitBtn.textContent = 'Saving…';

        try {
            let res;
            if (isEdit) {
                res = await App.api.updateEquipment(editId, payload);
            } else {
                res = await App.api.createEquipment(payload);
            }

            if (res.ok) {
                App.toast(isEdit ? 'Equipment updated!' : 'Equipment added!', 'success');
                setTimeout(() => {
                    window.location.href = `${App.BASE}/public/pages/dashboard.html`;
                }, 700);
            } else {
                showError(res.data.error || 'Request failed.');
                App.toast(res.data.error || 'Request failed.', 'error');
                resetBtn();
            }
        } catch (err) {
            showError('Network error — please try again.');
            App.toast('Network error.', 'error');
            resetBtn();
        }
    });

    function resetBtn() {
        submitBtn.disabled    = false;
        submitBtn.textContent = isEdit ? '💾 Save Changes' : '➕ Add Equipment';
    }

    function showError(msg) {
        errBox.textContent    = msg;
        errBox.style.display  = 'block';
    }
})();
