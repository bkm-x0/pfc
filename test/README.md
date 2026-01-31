
# Matériel Informatique — Equipment Management System

A full-stack web application for managing computer equipment, built with
**HTML · CSS · Vanilla JS (Fetch)** on the frontend and **PHP · MySQL · PDO** on the backend.

---

## 📁 Project Structure

```
equipmentapp/
├── api/                        ← HTTP entry-points (URL-facing routers)
│   ├── auth.php                   POST login / logout, GET me
│   └── equipment.php              CRUD router (GET / POST / PUT / DELETE)
├── config/                     ← App-wide configuration
│   ├── db.php                     PDO singleton factory
│   └── auth.php                   Session bootstrap, guards, JSON helpers
├── src/                        ← Application logic (MVC)
│   ├── models/
│   │   ├── UserModel.php          User lookup + password verification
│   │   └── EquipmentModel.php     CRUD + validation for equipment
│   └── controllers/
│       ├── AuthController.php     Login / logout / session info
│       └── EquipmentController.php  index / show / store / update / destroy
├── public/                     ← Frontend assets (served by XAMPP)
│   ├── css/
│   │   └── style.css              Full application stylesheet
│   ├── js/
│   │   ├── api.js                 Shared Fetch wrapper, toast, badge helpers
│   │   ├── login.js               Login form logic
│   │   ├── dashboard.js           Stats + table + delete
│   │   └── add.js                 Create / Edit dual-mode form
│   └── pages/
│       ├── login.html             Sign-in page
│       ├── dashboard.html         Main dashboard
│       └── add.html               Add / Edit equipment form
├── schema.sql                  ← MySQL schema + sample data
├── setup_password.php          ← One-time admin password hasher (delete after use)
└── README.md                   ← This file
```

---

## 🚀 Setup (XAMPP on Windows)

1. **Install XAMPP** — make sure Apache + MySQL are running.

2. **Create the database**
   ```
   mysql -u root -p < schema.sql
   ```
   (or paste `schema.sql` into phpMyAdmin → SQL tab)

3. **Set the admin password hash**
   Open a browser and navigate to:
   ```
   http://localhost/equipmentapp/setup_password.php
   ```
   Confirm the green ✅ message, then **delete** `setup_password.php`.

4. **Open the app**
   ```
   http://localhost/equipmentapp/public/pages/login.html
   ```

5. **Log in** with `admin` / `admin123`.

---

## 🔄 Data-Flow Diagram

```
┌──────────────┐   Fetch (JSON)   ┌─────────────────┐
│  Browser     │ ────────────────▶│  api/*.php       │  ← URL Router
│  (HTML/CSS/  │                  │  (method routing)│
│   JS)        │◀────────────────│                  │
└──────────────┘   JSON response  └────────┬────────┘
                                           │ require
                                           ▼
                                  ┌─────────────────┐
                                  │  Controller      │  ← Business logic
                                  │  (Auth / Equip)  │     + validation
                                  └────────┬────────┘
                                           │ calls
                                           ▼
                                  ┌─────────────────┐
                                  │  Model           │  ← PDO queries
                                  │  (User / Equip)  │     (prepared stmts)
                                  └────────┬────────┘
                                           │ PDO
                                           ▼
                                  ┌─────────────────┐
                                  │  MySQL           │
                                  │  equipment_db    │
                                  └─────────────────┘
```

### Request lifecycle (example: creating equipment)

1. User fills the form in `add.html` and clicks **Add Equipment**.
2. `add.js` collects form values and calls `App.api.createEquipment(payload)`.
3. `api.js` issues `POST /api/equipment.php` with a JSON body.
4. `equipment.php` reads `REQUEST_METHOD` → dispatches to `EquipmentController::store()`.
5. The controller calls `requireAuth()` (session check), then `EquipmentModel::validate()`.
6. If valid, `EquipmentModel::create()` executes an `INSERT` via a prepared statement.
7. The controller returns `201 { message, data }` as JSON.
8. `add.js` shows a toast and redirects to the dashboard.

---

## 📡 API Reference

| Method   | URL                              | Auth | Description          |
|----------|----------------------------------|------|----------------------|
| POST     | `/api/auth.php?action=login`     | —    | Login                |
| POST     | `/api/auth.php?action=logout`    | ✅   | Logout               |
| GET      | `/api/auth.php?action=me`        | —    | Session info         |
| GET      | `/api/equipment.php`             | ✅   | List all equipment   |
| GET      | `/api/equipment.php?id={id}`     | ✅   | Get one item         |
| POST     | `/api/equipment.php`             | ✅   | Create equipment     |
| PUT      | `/api/equipment.php?id={id}`     | ✅   | Update equipment     |
| DELETE   | `/api/equipment.php?id={id}`     | ✅   | Delete equipment     |

---

## 🛡️ Security Measures

| Area                | Implementation                                                    |
|---------------------|-------------------------------------------------------------------|
| Passwords           | Stored with `password_hash()` (bcrypt, cost 10)                   |
| SQL Injection       | All queries use PDO prepared statements — zero string interpolation|
| XSS                 | Output escaped in JS (`createTextNode`); input sanitised server-side (`htmlspecialchars`) |
| Session Fixation    | `session_regenerate_id(true)` on every successful login           |
| Cookie Flags        | `HttpOnly`, `SameSite=Lax`, 1-hour lifetime                       |
| Input Validation    | Server-side: length, regex, enum membership checks in the Model   |
| Auth Guard          | Every protected API starts with `requireAuth()` — 401 if no session |
| Content-Type        | Write endpoints enforce `Content-Type: application/json`          |
| Cache Prevention    | API responses include `Cache-Control: no-store`                   |
| Generic Auth Errors | Login never reveals whether a username exists                     |

---

## 💡 Customisation Tips

- **Add a new category**: update the `ENUM` in `schema.sql`, the `CATEGORIES` const in
  `EquipmentModel.php`, and the `<option>` list in `add.html`.
- **Production deploy**: set `DB_PASS`, enable HTTPS, flip `'secure' => true` in the
  session cookie, and move `config/` outside the web root.
- **Pagination**: add `LIMIT / OFFSET` to `EquipmentModel::findAll()` and pass page params
  from the frontend.
