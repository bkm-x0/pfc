-- ============================================================
-- MATÉRIEL INFORMATIQUE — Database Schema (Enhanced)
-- Target: MySQL 5.7+ / MariaDB 10.3+
-- Import: mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS equipment_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE equipment_db;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(64)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,                        -- password_hash(PASSWORD_BCRYPT)
    role          ENUM('admin','client') NOT NULL DEFAULT 'client',
    full_name     VARCHAR(150) NULL,
    email         VARCHAR(150) NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- Default admin  — password: admin123
-- Generated with: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO users (username, password_hash, role, full_name, email)
VALUES (
    'admin',
    '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012',
    'admin',
    'System Administrator',
    'admin@example.com'
);

-- Sample client user — password: client123
INSERT INTO users (username, password_hash, role, full_name, email)
VALUES (
    'client1',
    '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012',
    'client',
    'John Doe',
    'john.doe@example.com'
);

-- NOTE: On first deployment run setup_password.php to generate proper hashes

-- ------------------------------------------------------------
-- Table: categories
-- ------------------------------------------------------------
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                          ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB;

-- Sample categories
INSERT INTO categories (name, description) VALUES
('Desktop', 'Desktop computers and workstations'),
('Laptop', 'Portable computers and notebooks'),
('Monitor', 'Display screens and monitors'),
('Printer', 'Printing devices and scanners'),
('Peripheral', 'Keyboards, mice, and other accessories'),
('Server', 'Server hardware and rack equipment'),
('Network', 'Routers, switches, and network equipment'),
('Other', 'Miscellaneous computer equipment');

-- ------------------------------------------------------------
-- Table: products (equipment)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS products;
CREATE TABLE products (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150)  NOT NULL,
    category_id   INT UNSIGNED  NOT NULL,
    brand         VARCHAR(80)   NOT NULL,
    serial_number VARCHAR(100)  NOT NULL UNIQUE,
    status        ENUM(
                      'Available',
                      'In Use',
                      'Under Maintenance',
                      'Retired'
                  ) NOT NULL DEFAULT 'Available',
    purchase_date DATE          NOT NULL,
    assigned_to   INT UNSIGNED  NULL,                          -- FK to users (client)
    notes         TEXT          NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category      (category_id),
    INDEX idx_status        (status),
    INDEX idx_serial        (serial_number),
    INDEX idx_purchase_date (purchase_date),
    INDEX idx_assigned_to   (assigned_to)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: product_images
-- ------------------------------------------------------------
DROP TABLE IF EXISTS product_images;
CREATE TABLE product_images (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    image_path  VARCHAR(255) NOT NULL,
    is_primary  BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_is_primary (is_primary)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: issues (for client issue reporting)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS issues;
CREATE TABLE issues (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status      ENUM('Open', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'Open',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                          ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Sample data
-- ------------------------------------------------------------
INSERT INTO products (name, category_id, brand, serial_number, status, purchase_date, assigned_to) VALUES
('ThinkPad X1 Carbon Gen 11',  2, 'Lenovo',  'LN-X1C-20HQ-001', 'In Use',              '2023-03-15', 2),
('Dell UltraSharp U2722D',     3, 'Dell',    'DL-U27-K82P1-02', 'Available',           '2023-01-08', NULL),
('PowerEdge R740',             6, 'Dell',    'DL-R740-SVR-003', 'In Use',              '2022-11-20', NULL),
('MacBook Pro 16" M2 Pro',     2, 'Apple',   'AP-MBP16-M2P-04', 'Available',           '2023-06-01', NULL),
('HP LaserJet Pro M428fdw',    4, 'HP',      'HP-LJ-M428-005',  'Under Maintenance',   '2022-07-14', NULL),
('Cisco UCS C220 M5',          6, 'Cisco',   'CS-UCS-C220-006', 'In Use',              '2022-04-03', NULL),
('Logitech MX Master 3s',      5, 'Logitech','LG-MX3S-007',     'Available',           '2023-09-22', NULL),
('Dell OptiPlex 7090',         1, 'Dell',    'DL-OPX-7090-008', 'In Use',              '2021-12-10', 2),
('Netgear GS724T Switch',      7, 'Netgear', 'NG-GS724T-009',   'Available',           '2023-02-17', NULL),
('Samsung 49" Ultrawide',      3, 'Samsung', 'SG-U49-CRG9-010', 'Retired',             '2020-05-30', NULL);
