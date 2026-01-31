<?php
/**
 * setup_password.php
 *
 * Run ONCE after importing schema.sql to set the admin password hash.
 *
 * Usage (command line):
 *     php setup_password.php
 *
 *   — OR open in browser:
 *     http://localhost/equipmentapp/setup_password.php
 *
 * After success, DELETE this file from the server.
 */

require_once __DIR__ . '/config/db.php';

$plainPassword = 'admin123';
$hash          = password_hash($plainPassword, PASSWORD_BCRYPT);

try {
    $db   = getDB();
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);

    if ($stmt->rowCount() === 0) {
        // Row doesn't exist yet — insert it
        $stmt = $db->prepare(
            "INSERT INTO users (username, password_hash, role) VALUES ('admin', ?, 'admin')"
        );
        $stmt->execute([$hash]);
        echo "✅  Admin user CREATED with password: {$plainPassword}\n";
    } else {
        echo "✅  Admin password UPDATED successfully.\n";
    }

    echo "   Hash stored: {$hash}\n\n";
    echo "⚠️  DELETE this file (setup_password.php) now!\n";

} catch (\PDOException $e) {
    echo "❌  Database error: " . $e->getMessage() . "\n";
    echo "    Make sure you have imported schema.sql first.\n";
}
