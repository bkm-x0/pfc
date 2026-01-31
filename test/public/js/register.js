/**
 * Register Page — User Registration
 * Handles new user registration
 */

document.addEventListener('DOMContentLoaded', () => {
    setupEventListeners();
});

/**
 * Setup event listeners
 */
function setupEventListeners() {
    const registerForm = document.getElementById('registerForm');
    registerForm.addEventListener('submit', handleRegister);
}

/**
 * Handle registration form submission
 */
async function handleRegister(event) {
    event.preventDefault();

    const username = document.getElementById('username').value.trim();
    const fullName = document.getElementById('fullName').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // Validate passwords match
    if (password !== confirmPassword) {
        showToast('Passwords do not match', 'error');
        return;
    }

    // Validate username format
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        showToast('Username can only contain letters, numbers, and underscores', 'error');
        return;
    }

    // Validate password length
    if (password.length < 6) {
        showToast('Password must be at least 6 characters', 'error');
        return;
    }

    // Validate email format
    if (!isValidEmail(email)) {
        showToast('Please enter a valid email address', 'error');
        return;
    }

    try {
        const response = await apiRequest('/api/auth.php?action=register', {
            method: 'POST',
            body: JSON.stringify({
                username: username,
                full_name: fullName,
                email: email,
                password: password
            })
        });

        if (response.success) {
            showToast('Registration successful! Redirecting to login...', 'success');
            
            // Redirect to login page after 2 seconds
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        }
    } catch (error) {
        console.error('Registration failed:', error);
        showToast(error.message || 'Registration failed. Please try again.', 'error');
    }
}

/**
 * Validate email format
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
