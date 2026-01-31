# Quick Setup Guide — Client Features

## 🚀 Installation Steps

### 1. Update Database Schema

Run the updated schema to add the cart table:

```bash
mysql -u root -p equipment_db < schema.sql
```

Or manually execute in phpMyAdmin:

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

### 2. Verify File Structure

Ensure all new files are in place:

```
test/
├── api/
│   ├── cart.php          ← NEW
│   └── profile.php       ← NEW
├── src/
│   └── models/
│       └── CartModel.php ← NEW
├── public/
│   ├── pages/
│   │   ├── products.html ← NEW
│   │   ├── cart.html     ← NEW
│   │   └── profile.html  ← NEW
│   ├── js/
│   │   ├── products.js   ← NEW
│   │   ├── cart.js       ← NEW
│   │   └── profile.js    ← NEW
│   └── css/
│       └── style.css     ← UPDATED
├── schema.sql            ← UPDATED
├── README.md             ← UPDATED
├── CLIENT_FEATURES.md    ← NEW
└── SETUP_CLIENT_FEATURES.md ← This file
```

### 3. Test the Features

#### A. Login as Client
```
URL: http://localhost/equipmentapp/public/pages/login.html
Username: client1
Password: client123
```

#### B. Browse Products
```
URL: http://localhost/equipmentapp/public/pages/products.html
```
- View all available products
- Filter by category
- Search products
- Add items to cart

#### C. View Cart
```
URL: http://localhost/equipmentapp/public/pages/cart.html
```
- View cart items
- Update quantities
- Remove items
- Clear cart

#### D. Manage Profile
```
URL: http://localhost/equipmentapp/public/pages/profile.html
```
- Update full name and email
- Change password

---

## 🔧 Configuration

### No Additional Configuration Required!

The new features integrate seamlessly with the existing system:
- ✅ Uses existing authentication system
- ✅ Uses existing database connection
- ✅ Uses existing session management
- ✅ Uses existing security measures

---

## 📋 Feature Summary

### What's New

1. **Product Browsing** (`products.html`)
   - Grid layout with product cards
   - Category filtering
   - Search functionality
   - Add to cart buttons
   - Real-time cart badge

2. **Shopping Cart** (`cart.html`)
   - View all cart items
   - Quantity management
   - Item removal
   - Cart summary
   - Clear cart option

3. **Profile Management** (`profile.html`)
   - View profile information
   - Update personal details
   - Change password
   - Email validation

### API Endpoints

- `GET /api/cart.php` - Get cart items
- `POST /api/cart.php` - Add to cart
- `PUT /api/cart.php?id={id}` - Update quantity
- `DELETE /api/cart.php?id={id}` - Remove item
- `GET /api/profile.php` - Get profile
- `PUT /api/profile.php` - Update profile
- `PUT /api/profile.php?action=password` - Change password

---

## 🎯 Quick Test Checklist

- [ ] Database cart table created
- [ ] Login as client works
- [ ] Products page loads and displays products
- [ ] Category filter works
- [ ] Search functionality works
- [ ] Add to cart works
- [ ] Cart badge updates
- [ ] Cart page displays items
- [ ] Quantity update works
- [ ] Remove item works
- [ ] Clear cart works
- [ ] Profile page loads
- [ ] Profile update works
- [ ] Password change works

---

## 🐛 Common Issues

### Issue: Cart table doesn't exist
**Solution**: Run the updated `schema.sql` file

### Issue: Products not showing
**Solution**: 
- Ensure products exist in database
- Check product status is "Available"
- Verify user is logged in as client

### Issue: Cart not updating
**Solution**:
- Clear browser cache
- Check browser console for errors
- Verify session is active

### Issue: Profile update fails
**Solution**:
- Check email format is valid
- Ensure field lengths are within limits
- Verify current password is correct (for password change)

---

## 📚 Documentation

For detailed information, see:
- [`CLIENT_FEATURES.md`](CLIENT_FEATURES.md) - Complete feature documentation
- [`README.md`](README.md) - Main project documentation
- [`IMPLEMENTATION_SUMMARY.md`](IMPLEMENTATION_SUMMARY.md) - Technical implementation details

---

## 🎉 You're All Set!

The client features are now ready to use. Clients can:
1. Browse all available products
2. Add products to their shopping cart
3. Manage their cart items
4. Update their profile information
5. Change their password

**Next Steps:**
- Test all features thoroughly
- Customize styling if needed
- Add additional features as required
- Deploy to production when ready

---

**Happy Shopping! 🛒**
