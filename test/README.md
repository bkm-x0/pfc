
# Matériel Informatique — Equipment Management System

A full-stack web application for managing computer equipment with **role-based access control**, **category management**, **product image uploads**, and **client assignment**.

Built with **HTML · CSS · Vanilla JS (Fetch API)** on the frontend and **PHP · MySQL · PDO** on the backend.

---

## 🎯 Features

### Admin Features
- ✅ Secure login with session management
- ✅ Full CRUD operations for **categories**
- ✅ Full CRUD operations for **products** (equipment)
- ✅ **Multiple image upload** per product (JPG, PNG, WebP)
- ✅ Set primary product image
- ✅ Assign products to clients
- ✅ User management (create/edit/delete clients)
- ✅ Dashboard with statistics
- ✅ View all products with images

### Client Features
- ✅ Secure login
- ✅ View **assigned products only**
- ✅ **Browse all available products** (NEW)
- ✅ **Shopping cart functionality** (NEW)
- ✅ **Profile management** (NEW)
- ✅ Filter products by category
- ✅ Search products by name, brand, serial
- ✅ View product images
- ✅ Add products to cart
- ✅ Update cart quantities
- ✅ Update profile information
- ✅ Change password
- ✅ Report issues (database ready)

---

## 📁 Project Structure

```
test/
├── api/                        ← HTTP entry-points (URL-facing routers)
│   ├── auth.php                   POST login / logout, GET me
│   ├── equipment.php              Equipment CRUD + statistics
│   ├── categories.php             Category CRUD (admin only)
│   ├── images.php                 Image upload/delete/set primary
│   └── users.php                  User management (admin only)
├── config/                     ← App-wide configuration
│   ├── db.php                     PDO singleton factory
│   └── auth.php                   Session bootstrap, guards, JSON helpers
├── src/                        ← Application logic (MVC)
│   ├── models/
│   │   ├── UserModel.php          User CRUD + authentication
│   │   ├── CategoryModel.php      Category CRUD
│   │   ├── EquipmentModel.php     Product CRUD + statistics
│   │   └── ProductImageModel.php  Image management
│   └── controllers/
│       ├── AuthController.php     Login / logout / session info
│       ├── CategoryController.php Category CRUD (admin only)
│       ├── EquipmentController.php Product CRUD with role-based access
│       └── ImageUploadController.php Secure file upload handling
├── public/                     ← Frontend assets (served by XAMPP)
│   ├── css/
│   │   └── style.css              Full application stylesheet
│   ├── js/
│   │   ├── api.js                 Shared Fetch wrapper, helpers
│   │   ├── login.js               Login form logic
│   │   ├── dashboard.js           Dashboard logic (role-aware)
│   │   └── add.js                 Product form with image upload
│   └── pages/
│       ├── login.html             Sign-in page
│       ├── dashboard.html         Main dashboard (admin/client)
│       └── add.html               Add / Edit product form
├── uploads/                    ← Uploaded product images
│   └── products/
│       ├── .htaccess              Prevent PHP execution
│       └── .gitkeep               Keep directory in git
├── schema.sql                  ← MySQL schema + sample data
├── setup_password.php          ← One-time password hasher (delete after use)
└── README.md                   ← This file
```

---

## 🚀 Setup (XAMPP on Windows)

### 1. Install XAMPP
Make sure Apache + MySQL are running.

### 2. Place Project Files
Copy the `test/` folder to your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\equipmentapp\
```

### 3. Create the Database
Open phpMyAdmin or MySQL CLI and run:
```bash
mysql -u root -p < schema.sql
```
Or paste the contents of [`schema.sql`](schema.sql) into phpMyAdmin → SQL tab.

This creates:
- Database: `equipment_db`
- Tables: `users`, `categories`, `products`, `product_images`, `issues`
- Sample data with admin and client users

### 4. Set Admin Password
Navigate to:
```
http://localhost/equipmentapp/setup_password.php
```
This will generate a proper bcrypt hash for the admin password. **Delete this file after use.**

### 5. Configure Upload Directory
Ensure the `uploads/products/` directory has write permissions:
```bash
chmod 755 uploads/products/
```
On Windows, this is usually automatic.

### 6. Open the Application
```
http://localhost/equipmentapp/public/pages/login.html
```

### 7. Login Credentials
**Admin:**
- Username: `admin`
- Password: `admin123`

**Client:**
- Username: `client1`
- Password: `client123`

---

## 🗄️ Database Schema

### Tables

#### `users`
| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| username | VARCHAR(64) | Unique username |
| password_hash | VARCHAR(255) | Bcrypt hash |
| role | ENUM('admin','client') | User role |
| full_name | VARCHAR(150) | Full name (optional) |
| email | VARCHAR(150) | Email (optional) |
| created_at | TIMESTAMP | Creation timestamp |

#### `categories`
| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| name | VARCHAR(100) | Unique category name |
| description | TEXT | Category description |
| created_at | TIMESTAMP | Creation timestamp |

#### `products`
| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| name | VARCHAR(150) | Product name |
| category_id | INT UNSIGNED | FK to categories |
| brand | VARCHAR(80) | Brand name |
| serial_number | VARCHAR(100) | Unique serial |
| status | ENUM | Available, In Use, Under Maintenance, Retired |
| purchase_date | DATE | Purchase date |
| assigned_to | INT UNSIGNED | FK to users (client) |
| notes | TEXT | Additional notes |
| created_at | TIMESTAMP | Creation timestamp |

#### `product_images`
| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| product_id | INT UNSIGNED | FK to products |
| image_path | VARCHAR(255) | Relative path to image |
| is_primary | BOOLEAN | Primary image flag |
| created_at | TIMESTAMP | Upload timestamp |

#### `issues`
| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| product_id | INT UNSIGNED | FK to products |
| user_id | INT UNSIGNED | FK to users |
| title | VARCHAR(200) | Issue title |
| description | TEXT | Issue description |
| status | ENUM | Open, In Progress, Resolved, Closed |
| created_at | TIMESTAMP | Creation timestamp |

---

## 📡 API Reference

### Authentication

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| POST | `/api/auth.php?action=login` | — | Login |
| POST | `/api/auth.php?action=logout` | ✅ | Logout |
| GET | `/api/auth.php?action=me` | — | Session info |

### Equipment (Products)

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/equipment.php` | ✅ | List all (admin) or assigned (client) |
| GET | `/api/equipment.php?id={id}` | ✅ | Get one product |
| GET | `/api/equipment.php?action=statistics` | 🔒 Admin | Dashboard statistics |
| GET | `/api/equipment.php?category_id={id}` | ✅ | Filter by category |
| POST | `/api/equipment.php` | 🔒 Admin | Create product |
| PUT | `/api/equipment.php?id={id}` | 🔒 Admin | Update product |
| DELETE | `/api/equipment.php?id={id}` | 🔒 Admin | Delete product |

### Categories

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/categories.php` | ✅ | List all categories |
| GET | `/api/categories.php?id={id}` | ✅ | Get one category |
| POST | `/api/categories.php` | 🔒 Admin | Create category |
| PUT | `/api/categories.php?id={id}` | 🔒 Admin | Update category |
| DELETE | `/api/categories.php?id={id}` | 🔒 Admin | Delete category |

### Images

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| POST | `/api/images.php?action=upload` | 🔒 Admin | Upload images (multipart/form-data) |
| GET | `/api/images.php?product_id={id}` | ✅ | Get product images |
| PUT | `/api/images.php?id={id}&action=primary` | 🔒 Admin | Set as primary |
| DELETE | `/api/images.php?id={id}` | 🔒 Admin | Delete image |

### Cart (NEW)

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/cart.php` | 👤 Client | Get cart items |
| GET | `/api/cart.php?action=count` | 👤 Client | Get cart count |
| POST | `/api/cart.php` | 👤 Client | Add item to cart |
| PUT | `/api/cart.php?id={id}` | 👤 Client | Update quantity |
| DELETE | `/api/cart.php?id={id}` | 👤 Client | Remove item |
| DELETE | `/api/cart.php?action=clear` | 👤 Client | Clear cart |

### Profile (NEW)

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/profile.php` | ✅ | Get current user profile |
| PUT | `/api/profile.php` | ✅ | Update profile info |
| PUT | `/api/profile.php?action=password` | ✅ | Change password |

### Users

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/users.php` | 🔒 Admin | List all users |
| GET | `/api/users.php?clients=1` | 🔒 Admin | List clients only |
| GET | `/api/users.php?id={id}` | 🔒 Admin | Get one user |
| POST | `/api/users.php` | 🔒 Admin | Create user |
| PUT | `/api/users.php?id={id}` | 🔒 Admin | Update user |
| DELETE | `/api/users.php?id={id}` | 🔒 Admin | Delete user |

---

## 🔄 Data Flow

### Image Upload Flow

```
┌──────────────┐   FormData      ┌─────────────────┐
│  Browser     │ ───────────────▶│  api/images.php  │
│  (add.html)  │                 │  (multipart)     │
└──────────────┘                 └────────┬────────┘
                                          │ require
                                          ▼
                                 ┌─────────────────┐
                                 │ ImageUpload     │  ← Validate file
                                 │ Controller      │     type, size
                                 └────────┬────────┘     rename, move
                                          │ calls
                                          ▼
                                 ┌─────────────────┐
                                 │ ProductImage    │  ← Save path
                                 │ Model           │     to database
                                 └────────┬────────┘
                                          │ PDO
                                          ▼
                                 ┌─────────────────┐
                                 │  MySQL           │
                                 │  product_images  │
                                 └─────────────────┘
```

### Role-Based Access Flow

```
┌──────────────┐   Fetch         ┌─────────────────┐
│  Browser     │ ───────────────▶│  api/*.php       │
│              │                 │                  │
└──────────────┘                 └────────┬────────┘
                                          │ require
                                          ▼
                                 ┌─────────────────┐
                                 │ config/auth.php  │  ← requireAuth()
                                 │                  │    requireAdmin()
                                 └────────┬────────┘    requireClient()
                                          │ checks
                                          ▼
                                 ┌─────────────────┐
                                 │ $_SESSION        │  ← user_id
                                 │                  │    role
                                 └─────────────────┘
```

---

## 🛡️ Security Measures

| Area | Implementation |
|------|----------------|
| **Passwords** | Stored with `password_hash()` (bcrypt, cost 10) |
| **SQL Injection** | All queries use PDO prepared statements |
| **XSS** | Output escaped with `htmlspecialchars()` |
| **Session Fixation** | `session_regenerate_id(true)` on login |
| **Cookie Flags** | `HttpOnly`, `SameSite=Lax`, 1-hour lifetime |
| **File Upload** | Type validation (MIME + extension), size limit (5MB) |
| **Executable Prevention** | `.htaccess` blocks PHP execution in uploads/ |
| **File Naming** | Unique names with timestamp + random bytes |
| **Role-Based Access** | Middleware guards: `requireAdmin()`, `requireClient()` |
| **Authorization** | Clients can only view assigned products |
| **Input Validation** | Server-side validation in all models |
| **Content-Type** | Write endpoints enforce `application/json` |
| **Cache Prevention** | API responses include `Cache-Control: no-store` |

---

## 🖼️ Image Upload Implementation

### Frontend (JavaScript + FormData)

```javascript
// Example: Upload images for a product
const formData = new FormData();
formData.append('product_id', productId);
formData.append('is_primary', 'true');

// Add multiple files
for (let file of fileInput.files) {
    formData.append('images[]', file);
}

const response = await fetch('/api/images.php?action=upload', {
    method: 'POST',
    body: formData  // Don't set Content-Type, browser handles it
});
```

### Backend (PHP)

```php
// ImageUploadController::upload()
// 1. Validate product_id
// 2. Check file upload errors
// 3. Validate MIME type (finfo_file)
// 4. Validate file extension
// 5. Check file size (max 5MB)
// 6. Generate unique filename (timestamp + random)
// 7. Move to uploads/products/
// 8. Save path to database
// 9. Return JSON response
```

### Security Features

1. **File Type Validation**: Checks both MIME type and extension
2. **Size Limit**: Maximum 5MB per file
3. **Unique Naming**: `20240131_143022_a1b2c3d4e5f6g7h8.jpg`
4. **Executable Prevention**: `.htaccess` blocks PHP execution
5. **Database Cascade**: Images deleted when product is deleted

---

## 🎨 Frontend Architecture

### Role-Aware Dashboard

The dashboard automatically adapts based on user role:

**Admin View:**
- Statistics cards (total products, by status, by category)
- Full product table with edit/delete actions
- Category management section
- User management section
- Image upload interface

**Client View:**
- Assigned products only
- Category filter
- Product images gallery
- Issue reporting (future feature)

### JavaScript Modules

- **[`api.js`](public/js/api.js)**: Shared Fetch wrapper, toast notifications, badge helpers
- **[`login.js`](public/js/login.js)**: Login form handling
- **[`dashboard.js`](public/js/dashboard.js)**: Role-aware dashboard logic
- **[`add.js`](public/js/add.js)**: Product form with image upload (FormData)

---

## 💡 Customization Tips

### Add a New Category

1. **Database**: Categories are now in a separate table, add via admin UI
2. **Frontend**: Categories load dynamically from API

### Add a New Product Status

1. Update `ENUM` in [`schema.sql`](schema.sql:60-65) (products table)
2. Update `STATUSES` const in [`EquipmentModel.php`](src/models/EquipmentModel.php:16-18)
3. Update status `<select>` in [`add.html`](public/pages/add.html)

### Change Upload Limits

1. **PHP**: Edit `MAX_FILE_SIZE` in [`ImageUploadController.php`](src/controllers/ImageUploadController.php:18)
2. **Server**: Update `upload_max_filesize` and `post_max_size` in `php.ini`

### Production Deployment

1. Set strong database password in [`db.php`](config/db.php:12)
2. Enable HTTPS and set `'secure' => true` in [`auth.php`](config/auth.php:16)
3. Move `config/` outside web root
4. Set proper file permissions (755 for directories, 644 for files)
5. Enable error logging, disable display_errors
6. Use environment variables for sensitive config

---

## 🧪 Testing the Application

### 1. Test Admin Login
```
URL: http://localhost/equipmentapp/public/pages/login.html
Username: admin
Password: admin123
```

### 2. Test Category Management
- Navigate to Categories section
- Create a new category
- Edit existing category
- Try to delete a category with products (should fail)

### 3. Test Product Creation with Images
- Click "Add Equipment"
- Fill in product details
- Select category from dropdown
- Upload multiple images (JPG, PNG, WebP)
- Set one as primary
- Save and verify images appear

### 4. Test Client Assignment
- Edit a product
- Assign to "client1" user
- Logout and login as client1
- Verify only assigned products are visible

### 5. Test Client Login
```
Username: client1
Password: client123
```
- Should see only assigned products
- Cannot access admin features
- Can filter by category

### 6. Test Image Management
- Upload multiple images for a product
- Set different image as primary
- Delete an image
- Verify physical file is removed

---

## 🐛 Troubleshooting

### Images Not Uploading

1. Check `uploads/products/` directory exists and is writable
2. Verify `upload_max_filesize` in `php.ini` (default 2MB may be too small)
3. Check `post_max_size` in `php.ini` (must be larger than `upload_max_filesize`)
4. Look for errors in browser console and PHP error log

### Database Connection Failed

1. Verify MySQL is running in XAMPP
2. Check credentials in [`db.php`](config/db.php:9-12)
3. Ensure database `equipment_db` exists
4. Run [`schema.sql`](schema.sql) to create tables

### Session Not Persisting

1. Check `session.save_path` in `php.ini`
2. Ensure session directory is writable
3. Clear browser cookies
4. Check for JavaScript errors preventing session cookie

### 403 Forbidden Errors

1. Verify user role in database
2. Check `$_SESSION['role']` is set correctly
3. Ensure `requireAdmin()` or `requireClient()` is appropriate for the endpoint

---

## 📚 Code Examples

### Create a Product with Images (JavaScript)

```javascript
// 1. Create product
const productData = {
    name: 'Dell Latitude 5420',
    category_id: 2,  // Laptop
    brand: 'Dell',
    serial_number: 'DL-LAT-5420-001',
    status: 'Available',
    purchase_date: '2024-01-15',
    assigned_to: null,
    notes: 'New laptop for development team'
};

const createResponse = await fetch('/api/equipment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(productData)
});

const { data: product } = await createResponse.json();

// 2. Upload images
const formData = new FormData();
formData.append('product_id', product.id);
formData.append('is_primary', 'true');
formData.append('images[]', imageFile1);
formData.append('images[]', imageFile2);

await fetch('/api/images.php?action=upload', {
    method: 'POST',
    body: formData
});
```

### Query Products by Category (PHP)

```php
// Get all laptops
$laptops = EquipmentModel::findByCategoryId(2);

// Each product includes category name and assigned user info
foreach ($laptops as $laptop) {
    echo $laptop['name'] . ' - ' . $laptop['category_name'];
    if ($laptop['assigned_to']) {
        echo ' (Assigned to: ' . $laptop['assigned_to_username'] . ')';
    }
}
```

---

## 🚀 Future Enhancements

- [ ] Issue reporting system (database ready)
- [ ] Email notifications for assignments
- [ ] Product maintenance history
- [ ] Advanced search and filtering
- [ ] Export to PDF/Excel
- [ ] Product QR code generation
- [ ] Mobile-responsive design improvements
- [ ] Dark mode theme
- [ ] Audit log for all changes
- [ ] Bulk product import (CSV)

---

## 📄 License

This project is provided as-is for educational and commercial use.

---

## 👨‍💻 Author

Built with ❤️ as a demonstration of modern PHP + MySQL + Vanilla JS architecture.

**Key Technologies:**
- PHP 8.0+ (MVC pattern)
- MySQL 5.7+ / MariaDB 10.3+
- Vanilla JavaScript (ES6+)
- Fetch API + FormData
- PDO with prepared statements
- Bcrypt password hashing
- Role-based access control (RBAC)
- Secure file upload handling

---

## 📞 Support

For issues or questions:
1. Check the [Troubleshooting](#-troubleshooting) section
2. Review the [API Reference](#-api-reference)
3. Examine browser console and PHP error logs
4. Verify database schema matches [`schema.sql`](schema.sql)

---

**Happy Equipment Managing! 🖥️📦**
