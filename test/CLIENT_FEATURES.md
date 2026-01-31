# Client Features Documentation

## Overview

This document describes the new client-facing features that allow clients to browse products, manage their profile, and add products to a shopping cart.

---

## 🎯 New Features

### 1. Product Browsing
- **Page**: [`products.html`](public/pages/products.html)
- **JavaScript**: [`products.js`](public/js/products.js)
- **Description**: Clients can browse all available products in the system

**Features:**
- ✅ View all available products in a grid layout
- ✅ Filter products by category
- ✅ Search products by name, brand, serial number, or notes
- ✅ View product images
- ✅ Add products to cart with one click
- ✅ Real-time cart count badge

**Access**: `http://localhost/equipmentapp/public/pages/products.html`

### 2. Shopping Cart
- **Page**: [`cart.html`](public/pages/cart.html)
- **JavaScript**: [`cart.js`](public/js/cart.js)
- **API**: [`api/cart.php`](api/cart.php)
- **Model**: [`CartModel.php`](src/models/CartModel.php)
- **Description**: Clients can manage their shopping cart

**Features:**
- ✅ View all items in cart
- ✅ Update item quantities
- ✅ Remove individual items
- ✅ Clear entire cart
- ✅ View cart summary with total items
- ✅ Product images and details in cart

**Access**: `http://localhost/equipmentapp/public/pages/cart.html`

### 3. Profile Management
- **Page**: [`profile.html`](public/pages/profile.html)
- **JavaScript**: [`profile.js`](public/js/profile.js)
- **API**: [`api/profile.php`](api/profile.php)
- **Description**: Clients can view and update their profile information

**Features:**
- ✅ View profile information
- ✅ Update full name
- ✅ Update email address
- ✅ Change password
- ✅ View account role and username

**Access**: `http://localhost/equipmentapp/public/pages/profile.html`

---

## 📊 Database Schema

### New Table: `cart`

```sql
CREATE TABLE cart (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    quantity    INT UNSIGNED NOT NULL DEFAULT 1,
    added_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB;
```

**Key Features:**
- Unique constraint prevents duplicate products per user
- Cascade deletion when user or product is deleted
- Quantity tracking for each item
- Timestamp for when item was added

---

## 🔌 API Endpoints

### Cart API (`/api/cart.php`)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/cart.php` | Get all cart items | Client |
| GET | `/api/cart.php?action=count` | Get cart item count | Client |
| POST | `/api/cart.php` | Add item to cart | Client |
| PUT | `/api/cart.php?id={id}` | Update item quantity | Client |
| DELETE | `/api/cart.php?id={id}` | Remove item from cart | Client |
| DELETE | `/api/cart.php?action=clear` | Clear entire cart | Client |

**Example: Add to Cart**
```javascript
const response = await fetch('/api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        product_id: 5,
        quantity: 1
    })
});
```

**Example: Update Quantity**
```javascript
const response = await fetch('/api/cart.php?id=3', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        quantity: 2
    })
});
```

### Profile API (`/api/profile.php`)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/profile.php` | Get current user profile | Any |
| PUT | `/api/profile.php` | Update profile info | Any |
| PUT | `/api/profile.php?action=password` | Change password | Any |

**Example: Update Profile**
```javascript
const response = await fetch('/api/profile.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        full_name: 'John Doe',
        email: 'john@example.com'
    })
});
```

**Example: Change Password**
```javascript
const response = await fetch('/api/profile.php?action=password', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        current_password: 'oldpass',
        new_password: 'newpass123'
    })
});
```

---

## 🎨 User Interface

### Products Page Layout

```
┌─────────────────────────────────────────────────────────┐
│  Header: Browse Products | Cart (badge) | Profile | ... │
├─────────────────────────────────────────────────────────┤
│  Filters: [Category Dropdown] [Search Input]            │
├─────────────────────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │ Product  │  │ Product  │  │ Product  │             │
│  │  Image   │  │  Image   │  │  Image   │             │
│  │  Name    │  │  Name    │  │  Name    │             │
│  │  Brand   │  │  Brand   │  │  Brand   │             │
│  │ [Add to  │  │ [Add to  │  │ [Add to  │             │
│  │  Cart]   │  │  Cart]   │  │  Cart]   │             │
│  └──────────┘  └──────────┘  └──────────┘             │
└─────────────────────────────────────────────────────────┘
```

### Cart Page Layout

```
┌─────────────────────────────────────────────────────────┐
│  Header: Shopping Cart | Browse | Profile | ...         │
├─────────────────────────────────────────────────────────┤
│  ┌───────────────────────────────────────────────────┐  │
│  │ [Image] Product Name                              │  │
│  │         Brand | Category | Serial                 │  │
│  │         Qty: [-] [2] [+]           [Remove]       │  │
│  └───────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────┐  │
│  │ [Image] Product Name                              │  │
│  │         Brand | Category | Serial                 │  │
│  │         Qty: [-] [1] [+]           [Remove]       │  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│  ┌─────────────────────────┐                            │
│  │ Cart Summary            │                            │
│  │ Total Items: 3          │                            │
│  │ [Proceed to Checkout]   │                            │
│  └─────────────────────────┘                            │
└─────────────────────────────────────────────────────────┘
```

### Profile Page Layout

```
┌─────────────────────────────────────────────────────────┐
│  Header: My Profile | Browse | Cart | ...               │
├─────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────┐    │
│  │ Profile Information                             │    │
│  │ Username: [client1] (readonly)                  │    │
│  │ Full Name: [John Doe]                           │    │
│  │ Email: [john@example.com]                       │    │
│  │ Role: [Client] (readonly)                       │    │
│  │ [Update Profile]                                │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
│  ┌─────────────────────────────────────────────────┐    │
│  │ Change Password                                 │    │
│  │ Current Password: [********]                    │    │
│  │ New Password: [********]                        │    │
│  │ Confirm Password: [********]                    │    │
│  │ [Change Password]                               │    │
│  └─────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────┘
```

---

## 🔒 Security Features

### Cart Security
- ✅ Client-only access (admins cannot access cart)
- ✅ User isolation (users can only see their own cart)
- ✅ Product validation (only available products can be added)
- ✅ Quantity validation (minimum 1)
- ✅ SQL injection prevention (prepared statements)

### Profile Security
- ✅ Password verification required for password changes
- ✅ Minimum password length (6 characters)
- ✅ Email validation
- ✅ Username cannot be changed (security)
- ✅ Role cannot be changed by user
- ✅ Bcrypt password hashing

### General Security
- ✅ Session-based authentication
- ✅ CSRF protection ready
- ✅ XSS prevention (output escaping)
- ✅ Input validation on all endpoints
- ✅ JSON content-type enforcement

---

## 🚀 Usage Guide

### For Clients

#### 1. Browse Products
1. Login as a client
2. Navigate to "Browse Products" from dashboard or header
3. Use category filter to narrow down products
4. Use search box to find specific products
5. Click "Add to Cart" on any product

#### 2. Manage Cart
1. Click "Cart" button in header (shows item count)
2. View all items in your cart
3. Adjust quantities using +/- buttons
4. Remove individual items with "Remove" button
5. Clear entire cart with "Clear Cart" button
6. Proceed to checkout (placeholder for now)

#### 3. Update Profile
1. Click "Profile" button in header
2. Update your full name and email
3. Click "Update Profile" to save changes
4. To change password:
   - Enter current password
   - Enter new password (min 6 characters)
   - Confirm new password
   - Click "Change Password"

---

## 📝 Code Structure

### Frontend Files

```
public/
├── pages/
│   ├── products.html      ← Product browsing page
│   ├── cart.html          ← Shopping cart page
│   └── profile.html       ← Profile management page
├── js/
│   ├── products.js        ← Product browsing logic
│   ├── cart.js            ← Cart management logic
│   ├── profile.js         ← Profile management logic
│   └── api.js             ← Shared API utilities (existing)
└── css/
    └── style.css          ← Updated with new styles
```

### Backend Files

```
api/
├── cart.php               ← Cart API endpoints
└── profile.php            ← Profile API endpoints

src/
└── models/
    └── CartModel.php      ← Cart data access layer
```

---

## 🧪 Testing Checklist

### Product Browsing
- [ ] Login as client
- [ ] Navigate to products page
- [ ] Verify all available products are displayed
- [ ] Test category filter
- [ ] Test search functionality
- [ ] Add product to cart
- [ ] Verify cart badge updates

### Shopping Cart
- [ ] View cart with items
- [ ] Increase item quantity
- [ ] Decrease item quantity
- [ ] Remove single item
- [ ] Clear entire cart
- [ ] Verify empty state displays correctly
- [ ] Try to add unavailable product (should fail)

### Profile Management
- [ ] View profile information
- [ ] Update full name
- [ ] Update email
- [ ] Verify validation (invalid email)
- [ ] Change password successfully
- [ ] Try wrong current password (should fail)
- [ ] Try short password (should fail)
- [ ] Verify password mismatch detection

### Security Testing
- [ ] Try to access cart as admin (should fail)
- [ ] Try to access another user's cart (should fail)
- [ ] Try to add product with invalid ID
- [ ] Try SQL injection in search
- [ ] Try XSS in profile fields
- [ ] Verify session timeout works

---

## 🔮 Future Enhancements

### Planned Features
- [ ] Checkout process with order creation
- [ ] Order history for clients
- [ ] Product reviews and ratings
- [ ] Wishlist functionality
- [ ] Email notifications for cart items
- [ ] Product availability notifications
- [ ] Advanced filtering (price range, brand, etc.)
- [ ] Product comparison feature
- [ ] Recently viewed products

### Technical Improvements
- [ ] Add pagination for products
- [ ] Implement lazy loading for images
- [ ] Add product quick view modal
- [ ] Implement real-time cart updates (WebSocket)
- [ ] Add cart persistence (save for later)
- [ ] Implement product recommendations
- [ ] Add analytics tracking

---

## 📚 API Response Examples

### Get Cart Items
```json
{
    "success": true,
    "items": [
        {
            "cart_id": 1,
            "quantity": 2,
            "added_at": "2024-01-31 10:30:00",
            "product_id": 5,
            "name": "HP LaserJet Pro M428fdw",
            "brand": "HP",
            "serial_number": "HP-LJ-M428-005",
            "status": "Available",
            "category_name": "Printer",
            "primary_image": "uploads/products/20240131_103000_abc123.jpg"
        }
    ],
    "count": 1
}
```

### Add to Cart
```json
{
    "success": true,
    "message": "Product added to cart",
    "cart_count": 3
}
```

### Get Profile
```json
{
    "success": true,
    "user": {
        "id": 2,
        "username": "client1",
        "role": "client",
        "full_name": "John Doe",
        "email": "john@example.com",
        "created_at": "2024-01-15 08:00:00"
    }
}
```

---

## 🐛 Troubleshooting

### Cart Issues

**Problem**: Items not appearing in cart
- Check if user is logged in as client
- Verify product status is "Available"
- Check browser console for errors
- Verify database cart table exists

**Problem**: Cart count not updating
- Clear browser cache
- Check session is active
- Verify API endpoint is accessible

### Profile Issues

**Problem**: Cannot update profile
- Verify email format is valid
- Check field length limits
- Ensure session is active
- Check for validation errors in response

**Problem**: Password change fails
- Verify current password is correct
- Ensure new password is at least 6 characters
- Check passwords match
- Look for error messages in response

---

## 📞 Support

For issues or questions:
1. Check browser console for JavaScript errors
2. Check PHP error logs for backend issues
3. Verify database schema is up to date
4. Ensure all files are in correct locations
5. Check file permissions on uploads directory

---

**Built with ❤️ for enhanced client experience**
