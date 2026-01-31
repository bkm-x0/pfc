/**
 * Products Page — Client Product Browsing
 * Handles product listing, filtering, and adding to cart
 */

let allProducts = [];
let categories = [];
let cartCount = 0;

// Initialize page
document.addEventListener('DOMContentLoaded', async () => {
    await checkAuth();
    await loadCategories();
    await loadProducts();
    await updateCartBadge();
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

        // Only clients can browse products
        if (response.user.role !== 'client') {
            showToast('Only clients can browse products', 'error');
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
 * Load categories for filter
 */
async function loadCategories() {
    try {
        const response = await apiRequest('/api/categories.php');
        categories = response;

        const categoryFilter = document.getElementById('categoryFilter');
        categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;
            categoryFilter.appendChild(option);
        });
    } catch (error) {
        console.error('Failed to load categories:', error);
    }
}

/**
 * Load all available products
 */
async function loadProducts() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const productsGrid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');

    loadingIndicator.style.display = 'block';
    productsGrid.innerHTML = '';
    emptyState.style.display = 'none';

    try {
        // Load all products (not just assigned ones)
        const response = await apiRequest('/api/equipment.php');
        allProducts = response.filter(p => p.status === 'Available');

        loadingIndicator.style.display = 'none';

        if (allProducts.length === 0) {
            emptyState.style.display = 'block';
        } else {
            displayProducts(allProducts);
        }
    } catch (error) {
        console.error('Failed to load products:', error);
        loadingIndicator.style.display = 'none';
        showToast('Failed to load products', 'error');
    }
}

/**
 * Display products in grid
 */
function displayProducts(products) {
    const productsGrid = document.getElementById('productsGrid');
    productsGrid.innerHTML = '';

    if (products.length === 0) {
        document.getElementById('emptyState').style.display = 'block';
        return;
    }

    document.getElementById('emptyState').style.display = 'none';

    products.forEach(product => {
        const productCard = createProductCard(product);
        productsGrid.appendChild(productCard);
    });
}

/**
 * Create product card element
 */
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';

    const imageUrl = product.primary_image 
        ? `../../${product.primary_image}` 
        : 'https://via.placeholder.com/300x200?text=No+Image';

    card.innerHTML = `
        <div class="product-image">
            <img src="${imageUrl}" alt="${escapeHtml(product.name)}" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
            <span class="product-status status-${product.status.toLowerCase().replace(' ', '-')}">${escapeHtml(product.status)}</span>
        </div>
        <div class="product-info">
            <h3>${escapeHtml(product.name)}</h3>
            <p class="product-brand">${escapeHtml(product.brand)}</p>
            <p class="product-category">📁 ${escapeHtml(product.category_name)}</p>
            <p class="product-serial">SN: ${escapeHtml(product.serial_number)}</p>
            ${product.notes ? `<p class="product-notes">${escapeHtml(product.notes)}</p>` : ''}
        </div>
        <div class="product-actions">
            <button class="btn btn-primary add-to-cart-btn" data-product-id="${product.id}">
                🛒 Add to Cart
            </button>
        </div>
    `;

    // Add to cart button event
    const addToCartBtn = card.querySelector('.add-to-cart-btn');
    addToCartBtn.addEventListener('click', () => addToCart(product.id));

    return card;
}

/**
 * Add product to cart
 */
async function addToCart(productId) {
    try {
        const response = await apiRequest('/api/cart.php', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity: 1 })
        });

        if (response.success) {
            showToast('Product added to cart!', 'success');
            await updateCartBadge();
        }
    } catch (error) {
        console.error('Failed to add to cart:', error);
        showToast(error.message || 'Failed to add to cart', 'error');
    }
}

/**
 * Update cart badge count
 */
async function updateCartBadge() {
    try {
        const response = await apiRequest('/api/cart.php?action=count');
        cartCount = response.count;
        document.getElementById('cartBadge').textContent = cartCount;
    } catch (error) {
        console.error('Failed to update cart badge:', error);
    }
}

/**
 * Filter products by category
 */
function filterProducts() {
    const categoryId = document.getElementById('categoryFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();

    let filtered = allProducts;

    // Filter by category
    if (categoryId) {
        filtered = filtered.filter(p => p.category_id == categoryId);
    }

    // Filter by search term
    if (searchTerm) {
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(searchTerm) ||
            p.brand.toLowerCase().includes(searchTerm) ||
            p.serial_number.toLowerCase().includes(searchTerm) ||
            (p.notes && p.notes.toLowerCase().includes(searchTerm))
        );
    }

    displayProducts(filtered);
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

    // Category filter
    document.getElementById('categoryFilter').addEventListener('change', filterProducts);

    // Search input
    document.getElementById('searchInput').addEventListener('input', filterProducts);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
