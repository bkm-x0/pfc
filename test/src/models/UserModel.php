<?php
/**
 * src/models/UserModel.php
 *
 * Thin data-access layer for the `users` table.
 * All queries use prepared statements via PDO.
 */

require_once __DIR__ . '/../../config/db.php';

class UserModel {

    /**
     * Find a user row by username.
     * Returns associative array or null.
     */
    public static function findByUsername(string $username): ?array {
        $stmt = getDB()->prepare(
            'SELECT id, username, password_hash, role
               FROM users
              WHERE username = ?
              LIMIT 1'
        );
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;   // false → null
    }

    /**
     * Verify a plain-text password against a stored hash.
     */
    public static function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }
}
