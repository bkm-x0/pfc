<?php
/**
 * src/models/UserModel.php
 *
 * Data-access layer for the `users` table.
 * All queries use prepared statements via PDO.
 */

require_once __DIR__ . '/../../config/db.php';

class UserModel {

    // ── READ ────────────────────────────────────────────────────

    /**
     * Find a user row by username.
     * Returns associative array or null.
     */
    public static function findByUsername(string $username): ?array {
        $stmt = getDB()->prepare(
            'SELECT id, username, password_hash, role, full_name, email, created_at
               FROM users
              WHERE username = ?
              LIMIT 1'
        );
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find a user by ID.
     * Returns associative array or null.
     */
    public static function findById(int $id): ?array {
        $stmt = getDB()->prepare(
            'SELECT id, username, password_hash, role, full_name, email, created_at
               FROM users
              WHERE id = ?
              LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all users (excluding password hashes).
     */
    public static function findAll(): array {
        $stmt = getDB()->prepare(
            'SELECT id, username, role, full_name, email, created_at
               FROM users
              ORDER BY created_at DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all client users (for assignment dropdown).
     */
    public static function findAllClients(): array {
        $stmt = getDB()->prepare(
            'SELECT id, username, full_name, email
               FROM users
              WHERE role = "client"
              ORDER BY username ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ── VALIDATE ────────────────────────────────────────────────

    /**
     * Validate user input for creation/update.
     * Returns ['data' => [...]] on success, or ['error' => string] on failure.
     */
    public static function validate(array $input, bool $isUpdate = false): array {
        $errors = [];

        // username ─────────────────────────────────────────
        $username = trim($input['username'] ?? '');
        if ($username === '') {
            $errors[] = 'username is required.';
        } elseif (strlen($username) > 64) {
            $errors[] = 'username must be ≤ 64 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'username may only contain letters, digits, and underscores.';
        }

        // password (required for creation, optional for update) ─
        $password = $input['password'] ?? '';
        if (!$isUpdate && $password === '') {
            $errors[] = 'password is required.';
        } elseif ($password !== '' && strlen($password) < 6) {
            $errors[] = 'password must be at least 6 characters.';
        }

        // role ─────────────────────────────────────────────
        $role = $input['role'] ?? 'client';
        if (!in_array($role, ['admin', 'client'], true)) {
            $errors[] = 'role must be either "admin" or "client".';
        }

        // full_name (optional) ─────────────────────────────
        $fullName = trim($input['full_name'] ?? '');
        if (strlen($fullName) > 150) {
            $errors[] = 'full_name must be ≤ 150 characters.';
        }

        // email (optional) ─────────────────────────────────
        $email = trim($input['email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email must be a valid email address.';
        } elseif (strlen($email) > 150) {
            $errors[] = 'email must be ≤ 150 characters.';
        }

        if (!empty($errors)) {
            return ['error' => implode(' ', $errors)];
        }

        $data = [
            'username'  => $username,
            'role'      => $role,
            'full_name' => htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'),
            'email'     => $email,
        ];

        // Only include password if provided
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        return ['data' => $data];
    }

    // ── CREATE ──────────────────────────────────────────────────

    /**
     * Create a new user.  Returns the new id on success.
     * Throws PDOException on duplicate username (unique constraint).
     */
    public static function create(array $fields): int {
        $stmt = getDB()->prepare(
            'INSERT INTO users (username, password_hash, role, full_name, email)
             VALUES (:username, :password_hash, :role, :full_name, :email)'
        );
        $stmt->execute($fields);
        return (int) getDB()->lastInsertId();
    }

    // ── UPDATE ──────────────────────────────────────────────────

    /**
     * Update an existing user.  Returns true if a row was affected.
     */
    public static function update(int $id, array $fields): bool {
        // Build dynamic SQL based on which fields are present
        $setParts = [];
        $params = [':id' => $id];

        if (isset($fields['username'])) {
            $setParts[] = 'username = :username';
            $params[':username'] = $fields['username'];
        }
        if (isset($fields['password_hash'])) {
            $setParts[] = 'password_hash = :password_hash';
            $params[':password_hash'] = $fields['password_hash'];
        }
        if (isset($fields['role'])) {
            $setParts[] = 'role = :role';
            $params[':role'] = $fields['role'];
        }
        if (isset($fields['full_name'])) {
            $setParts[] = 'full_name = :full_name';
            $params[':full_name'] = $fields['full_name'];
        }
        if (isset($fields['email'])) {
            $setParts[] = 'email = :email';
            $params[':email'] = $fields['email'];
        }

        if (empty($setParts)) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    // ── DELETE ──────────────────────────────────────────────────

    /**
     * Delete a user by ID.  Returns true if a row was removed.
     */
    public static function delete(int $id): bool {
        $stmt = getDB()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ── HELPERS ─────────────────────────────────────────────────

    /**
     * Verify a plain-text password against a stored hash.
     */
    public static function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    /**
     * Check if a username already exists (optionally excluding an id).
     */
    public static function usernameExists(string $username, ?int $excludeId = null): bool {
        $sql  = 'SELECT 1 FROM users WHERE username = ?';
        $args = [$username];

        if ($excludeId !== null) {
            $sql  .= ' AND id != ?';
            $args[] = $excludeId;
        }

        $stmt = getDB()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetch() !== false;
    }
}
