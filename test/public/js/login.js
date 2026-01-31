/**
 * public/js/login.js
 *
 * Handles the login form submission.
 * On success → redirects to dashboard.
 * On failure → shows inline error + toast.
 */

(async function () {
    // If already authenticated, skip straight to dashboard
    const { data } = await App.api.me();
    if (data?.authenticated) {
        window.location.href = `${App.BASE}/public/pages/dashboard.html`;
        return;
    }

    const form     = document.getElementById('login-form');
    const errBox   = document.getElementById('login-error');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errBox.style.display = 'none';

        const username = usernameInput.value.trim();
        const password = passwordInput.value;

        if (!username || !password) {
            showError('Please fill in both fields.');
            return;
        }

        // Disable button during request
        const btn = form.querySelector('.btn--primary');
        btn.disabled = true;
        btn.textContent = 'Signing in…';

        try {
            const { ok, data } = await App.api.login(username, password);

            if (ok) {
                App.toast('Welcome back, ' + data.user.username + '!', 'success');
                // Small delay so the toast is visible before navigation
                setTimeout(() => {
                    window.location.href = `${App.BASE}/public/pages/dashboard.html`;
                }, 600);
            } else {
                showError(data.error || 'Login failed.');
                App.toast(data.error || 'Login failed.', 'error');
                btn.disabled = false;
                btn.textContent = 'Sign In';
                passwordInput.value = '';
                passwordInput.focus();
            }
        } catch (err) {
            showError('Network error — please try again.');
            App.toast('Network error.', 'error');
            btn.disabled = false;
            btn.textContent = 'Sign In';
        }
    });

    function showError(msg) {
        errBox.textContent = msg;
        errBox.style.display = 'block';
    }
})();
