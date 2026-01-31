/**
 * Cart Page — Shopping Cart Management
 * Handles cart display, quantity updates, and item removal
 */

let cartItems = [];

// Initialize page
document.addEventListener('DOMContentLoaded', async () => {
    await checkAuth();
    await loadCart();
    setupEventListeners();
});

/**
 * Check authentication and user role
 */
async function checkAuth() {
    try {
        const response = await apiRequest('/api/auth.php?action=me');
        
        if (!response.authenticated) {
            window.location.href = 'login.html';
            return;
        }

        // Only clients can access cart
        if (response.user.role !== 'client') {
            showToast('Only clients can access the cart', 'error');
            window.location.href = 'dashboard.html';
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
 * Load cart items
 */
async function loadCart() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const cartItemsContainer = document.getElementById('cartItems');
    const emptyState = document.getElementById('emptyState');
    const cartSummary = document.getElementById('cartSummary');
    const clearCartBtn = document.getElementById('clearCartBtn');

    loadingIndicator.style.display = 'block';
    cartItemsContainer.innerHTML = '';
    emptyState.style.display = 'none';
    cartSummary.style.display = 'none';
    clearCartBtn.style.display = 'none';

    try {
        const response = await apiRequest('/api/cart.php');
        cartItems = response.items;

        loadingIndicator.style.display = 'none';

        if (cartItems.length === 0) {
            emptyState.style.display = 'block';
        } else {
            displayCartItems();
            updateCartSummary();
            cartSummary.style.display = 'block';
            clearCartBtn.style.display = 'inline-block';
        }
    } catch (error) {
        console.error('Failed to load cart:', error);
        loadingIndicator.style.display = 'none';
        showToast('Failed to load cart', 'error');
    }
}

/**
 * Display cart items
 */
function displayCartItems() {
    const cartItemsContainer = document.getElementById('cartItems');
    cartItemsContainer.innerHTML = '';

    cartItems.forEach(item => {
        const cartItem = createCartItemElement(item);
        cartItemsContainer.appendChild(cartItem);
    });
}

/**
 * Create cart item element
 */
function createCartItemElement(item) {
    const itemDiv = document.createElement('div');
    itemDiv.className = 'cart-item';

    const imageUrl = item.primary_image 
        ? `../../${item.primary_image}` 
        : 'https://via.placeholder.com/150x100?text=No+Image';

    itemDiv.innerHTML = `
        <div class="cart-item-image">
            <img src="${imageUrl}" alt="${escapeHtml(item.name)}" onerror="this.src='https://via.placeholder.com/150x100?text=No+Image'">
        </div>
        <div class="cart-item-details">
            <h3>${escapeHtml(item.name)}</h3>
            <p class="item-brand">${escapeHtml(item.brand)}</p>
            <p class="item-category">📁 ${escapeHtml(item.category_name)}</p>
            <p class="item-serial">SN: ${escapeHtml(item.serial_number)}</p>
            <p class="item-status">Status: <span class="badge badge-${item.status.toLowerCase().replace(' ', '-')}">${escapeHtml(item.status)}</span></p>
        </div>
        <div class="cart-item-quantity">
            <label>Quantity:</label>
            <div class="quantity-controls">
                <button class="btn btn-sm quantity-btn" data-action="decrease" data-cart-id="${item.cart_id}" data-quantity="${item.quantity}">-</button>
                <input type="number" class="quantity-input" value="${item.quantity}" min="1" data-cart-id="${item.cart_id}" readonly>
                <button class="btn btn-sm quantity-btn" data-action="increase" data-cart-id="${item.cart_id}" data-quantity="${item.quantity}">+</button>
            </div>
        </div>
        <div class="cart-item-actions">
            <button class="btn btn-danger remove-btn" data-cart-id="${item.cart_id}">
                🗑️ Remove
            </button>
        </div>
    `;

    // Quantity controls
    const decreaseBtn = itemDiv.querySelector('[data-action="decrease"]');
    const increaseBtn = itemDiv.querySelector('[data-action="increase"]');
    
    decreaseBtn.addEventListener('click', () => updateQuantity(item.cart_id, item.quantity - 1));
    increaseBtn.addEventListener('click', () => updateQuantity(item.cart_id, item.quantity + 1));

    // Remove button
    const removeBtn = itemDiv.querySelector('.remove-btn');
    removeBtn.addEventListener('click', () => removeItem(item.cart_id));

    return itemDiv;
}

/**
 * Update item quantity
 */
async function updateQuantity(cartId, newQuantity) {
    if (newQuantity < 1) {
        return;
    }

    try {
        await apiRequest(`/api/cart.php?id=${cartId}`, {
            method: 'PUT',
            body: JSON.stringify({ quantity: newQuantity })
        });

        showToast('Quantity updated', 'success');
        await loadCart();
    } catch (error) {
        console.error('Failed to update quantity:', error);
        showToast('Failed to update quantity', 'error');
    }
}

/**
 * Remove item from cart
 */
async function removeItem(cartId) {
    if (!confirm('Remove this item from cart?')) {
        return;
    }

    try {
        await apiRequest(`/api/cart.php?id=${cartId}`, {
            method: 'DELETE'
        });

        showToast('Item removed from cart', 'success');
        await loadCart();
    } catch (error) {
        console.error('Failed to remove item:', error);
        showToast('Failed to remove item', 'error');
    }
}

/**
 * Clear entire cart
 */
async function clearCart() {
    if (!confirm('Are you sure you want to clear your entire cart?')) {
        return;
    }

    try {
        await apiRequest('/api/cart.php?action=clear', {
            method: 'DELETE'
        });

        showToast('Cart cleared', 'success');
        await loadCart();
    } catch (error) {
        console.error('Failed to clear cart:', error);
        showToast('Failed to clear cart', 'error');
    }
}

/**
 * Update cart summary
 */
function updateCartSummary() {
    const totalItems = cartItems.reduce((sum, item) => sum + parseInt(item.quantity), 0);
    document.getElementById('totalItems').textContent = totalItems;
}

/**
 * Checkout (placeholder)
 */
function checkout() {
    showToast('Checkout functionality coming soon!', 'info');
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', async () => {
        try {
            await apiRequest('/api/auth.php?action=logout', { method: 'POST' });
            window.location.href = 'login.html';
        } catch (error) {
            console.error('Logout failed:', error);
        }
    });

    // Clear cart button
    document.getElementById('clearCartBtn').addEventListener('click', clearCart);

    // Checkout button
    document.getElementById('checkoutBtn').addEventListener('click', checkout);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
