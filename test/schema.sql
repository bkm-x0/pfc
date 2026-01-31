-- ============================================================
-- MATÉRIEL INFORMATIQUE — Database Schema
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
    role          ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- Default admin  — password: admin123
-- Generated with: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO users (username, password_hash, role)
VALUES (
    'admin',
    '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012',
    'admin'
);

-- NOTE: On first deployment run:
--   php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
-- then UPDATE users SET password_hash='<output>' WHERE username='admin';

-- ------------------------------------------------------------
-- Table: equipment
-- ------------------------------------------------------------
DROP TABLE IF EXISTS equipment;
CREATE TABLE equipment (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150)  NOT NULL,
    category      ENUM(
                      'Desktop',
                      'Laptop',
                      'Monitor',
                      'Printer',
                      'Peripheral',
                      'Server',
                      'Network',
                      'Other'
                  ) NOT NULL,
    brand         VARCHAR(80)   NOT NULL,
    serial_number VARCHAR(100)  NOT NULL UNIQUE,
    status        ENUM(
                      'Available',
                      'In Use',
                      'Under Maintenance',
                      'Retired'
                  ) NOT NULL DEFAULT 'Available',
    purchase_date DATE          NOT NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category      (category),
    INDEX idx_status        (status),
    INDEX idx_serial        (serial_number),
    INDEX idx_purchase_date (purchase_date)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Sample data
-- ------------------------------------------------------------
INSERT INTO equipment (name, category, brand, serial_number, status, purchase_date) VALUES
('ThinkPad X1 Carbon Gen 11',  'Laptop',     'Lenovo',  'LN-X1C-20HQ-001', 'In Use',              '2023-03-15'),
('Dell UltraSharp U2722D',     'Monitor',    'Dell',    'DL-U27-K82P1-02', 'Available',           '2023-01-08'),
('PowerEdge R740',             'Server',     'Dell',    'DL-R740-SVR-003', 'In Use',              '2022-11-20'),
('MacBook Pro 16" M2 Pro',     'Laptop',     'Apple',   'AP-MBP16-M2P-04', 'Available',           '2023-06-01'),
('HP LaserJet Pro M428fdw',    'Printer',    'HP',      'HP-LJ-M428-005',  'Under Maintenance',   '2022-07-14'),
('Cisco UCS C220 M5',          'Server',     'Cisco',   'CS-UCS-C220-006', 'In Use',              '2022-04-03'),
('Logitech MX Master 3s',      'Peripheral', 'Logitech','LG-MX3S-007',     'Available',           '2023-09-22'),
('Dell OptiPlex 7090',         'Desktop',    'Dell',    'DL-OPX-7090-008', 'In Use',              '2021-12-10'),
('Netgear GS724T Switch',      'Network',    'Netgear', 'NG-GS724T-009',   'Available',           '2023-02-17'),
('Samsung 49" Ultrawide',      'Monitor',    'Samsung', 'SG-U49-CRG9-010', 'Retired',             '2020-05-30');
