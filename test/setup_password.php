<?php
/**
 * setup_password.php — One-time password hash generator
 *
 * Run this file ONCE after database setup to generate proper password hashes.
 * Then DELETE this file for security.
 *
 * Usage: http://localhost/equipmentapp/setup_password.php
 */

require_once __DIR__ . '/config/db.php';

// Passwords to hash
$adminPassword  = 'admin123';
$clientPassword = 'client123';

try {
    $db = getDB();
    
    // Generate hashes
    $adminHash  = password_hash($adminPassword, PASSWORD_BCRYPT);
    $clientHash = password_hash($clientPassword, PASSWORD_BCRYPT);
    
    // Update admin user
    $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
    $stmt->execute([$adminHash, 'admin']);
    $adminUpdated = $stmt->rowCount();
    
    // Update client user
    $stmt->execute([$clientHash, 'client1']);
    $clientUpdated = $stmt->rowCount();
    
    // Display results
    echo '<!DOCTYPE html>';
    echo '<html><head><meta charset="UTF-8"><title>Password Setup</title>';
    echo '<style>body{font-family:sans-serif;max-width:600px;margin:50px auto;padding:20px;}';
    echo '.success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}';
    echo 'code{background:#f4f4f4;padding:2px 6px;border-radius:3px;}</style></head><body>';
    
    echo '<h1>🔐 Password Setup</h1>';
    
    if ($adminUpdated > 0) {
        echo '<p class="success">✅ Admin password updated successfully</p>';
        echo '<p>Username: <code>admin</code><br>Password: <code>' . htmlspecialchars($adminPassword) . '</code></p>';
    } else {
        echo '<p class="error">❌ Failed to update admin password (user may not exist)</p>';
    }
    
    if ($clientUpdated > 0) {
        echo '<p class="success">✅ Client password updated successfully</p>';
        echo '<p>Username: <code>client1</code><br>Password: <code>' . htmlspecialchars($clientPassword) . '</code></p>';
    } else {
        echo '<p class="error">❌ Failed to update client password (user may not exist)</p>';
    }
    
    echo '<hr>';
    echo '<p><strong>⚠️ IMPORTANT:</strong> Delete this file immediately for security!</p>';
    echo '<p>You can now login at: <a href="public/pages/login.html">public/pages/login.html</a></p>';
    
    echo '</body></html>';
    
} catch (PDOException $e) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body>';
    echo '<h1>❌ Database Error</h1>';
    echo '<p>Could not connect to database. Make sure MySQL is running and the database exists.</p>';
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Run <code>schema.sql</code> first to create the database and tables.</p>';
    echo '</body></html>';
}
