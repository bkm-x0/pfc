/**
 * Profile Page — User Profile Management
 * Handles profile viewing, editing, and password changes
 */

let currentUser = null;

// Initialize page
document.addEventListener('DOMContentLoaded', async () => {
    await checkAuth();
    await loadProfile();
    await updateCartBadge();
    setupEventListeners();
});

/**
 * Check authentication
 */
async function checkAuth() {
    try {
        const response = await apiRequest('/api/auth.php?action=me');
        
        if (!response.authenticated) {
            window.location.href = 'login.html';
            return;
        }

        document.getElementById('userInfo').textContent = 
            `Welcome, ${response.user.full_name || response.user.username}`;
    } catch (error) {
        console.error('Auth check failed:', error);
        window.location.href = 'login.html';
    }
}

/**
 * Load user profile
 */
async function loadProfile() {
    try {
        const response = await apiRequest('/api/profile.php');
        currentUser = response.user;

        // Populate form fields
        document.getElementById('username').value = currentUser.username;
        document.getElementById('fullName').value = currentUser.full_name || '';
        document.getElementById('email').value = currentUser.email || '';
        document.getElementById('role').value = currentUser.role.charAt(0).toUpperCase() + currentUser.role.slice(1);
    } catch (error) {
        console.error('Failed to load profile:', error);
        showToast('Failed to load profile', 'error');
    }
}

/**
 * Update cart badge count (for clients only)
 */
async function updateCartBadge() {
    try {
        const response = await apiRequest('/api/auth.php?action=me');
        
        if (response.user.role === 'client') {
            const cartResponse = await apiRequest('/api/cart.php?action=count');
            document.getElementById('cartBadge').textContent = cartResponse.count;
        }
    } catch (error) {
        console.error('Failed to update cart badge:', error);
    }
}

/**
 * Handle profile form submission
 */
async function handleProfileSubmit(event) {
    event.preventDefault();

    const fullName = document.getElementById('fullName').value.trim();
    const email = document.getElementById('email').value.trim();

    try {
        const response = await apiRequest('/api/profile.php', {
            method: 'PUT',
            body: JSON.stringify({
                full_name: fullName,
                email: email
            })
        });

        if (response.success) {
            showToast('Profile updated successfully!', 'success');
            currentUser = response.user;
            
            // Update header
            document.getElementById('userInfo').textContent = 
                `Welcome, ${currentUser.full_name || currentUser.username}`;
        }
    } catch (error) {
        console.error('Failed to update profile:', error);
        showToast(error.message || 'Failed to update profile', 'error');
    }
}

/**
 * Handle password form submission
 */
async function handlePasswordSubmit(event) {
    event.preventDefault();

    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // Validate passwords match
    if (newPassword !== confirmPassword) {
        showToast('New passwords do not match', 'error');
        return;
    }

    // Validate password length
    if (newPassword.length < 6) {
        showToast('Password must be at least 6 characters', 'error');
        return;
    }

    try {
        const response = await apiRequest('/api/profile.php?action=password', {
            method: 'PUT',
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        });

        if (response.success) {
            showToast('Password changed successfully!', 'success');
            
            // Clear form
            document.getElementById('passwordForm').reset();
        }
    } catch (error) {
        console.error('Failed to change password:', error);
        showToast(error.message || 'Failed to change password', 'error');
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Profile form
    document.getElementById('profileForm').addEventListener('submit', handleProfileSubmit);

    // Password form
    document.getElementById('passwordForm').addEventListener('submit', handlePasswordSubmit);

    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', async () => {
        try {
            await apiRequest('/api/auth.php?action=logout', { method: 'POST' });
            window.location.href = 'login.html';
        } catch (error) {
            console.error('Logout failed:', error);
        }
    });
}
