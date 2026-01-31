<?php
/**
 * config/db.php — PDO Database Connection Factory
 *
 * Edit DB_HOST / DB_NAME / DB_USER / DB_PASS for your environment.
 * Target: XAMPP on Windows (localhost, root, no password).
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'equipment_db');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: empty
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO instance.
 * Throws PDOException on connection failure (caught at API layer).
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST
             . ";dbname=" . DB_NAME
             . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // native prepared statements
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
