<?php
/**
 * src/models/EquipmentModel.php
 *
 * Data-access layer for the `equipment` table.
 * Every public method maps to one SQL operation.
 * Input validation is enforced before writes.
 */

require_once __DIR__ . '/../../config/db.php';

class EquipmentModel {

    // ── Allowed enum values (single source of truth) ──────────
    public const CATEGORIES = [
        'Desktop', 'Laptop', 'Monitor', 'Printer',
        'Peripheral', 'Server', 'Network', 'Other'
    ];

    public const STATUSES = [
        'Available', 'In Use', 'Under Maintenance', 'Retired'
    ];

    // ── READ ────────────────────────────────────────────────────

    /** Return every row, newest first. */
    public static function findAll(): array {
        $stmt = getDB()->prepare(
            'SELECT id, name, category, brand, serial_number, status,
                    purchase_date, created_at, updated_at
               FROM equipment
              ORDER BY created_at DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Return a single row by primary key, or null. */
    public static function findById(int $id): ?array {
        $stmt = getDB()->prepare(
            'SELECT id, name, category, brand, serial_number, status,
                    purchase_date, created_at, updated_at
               FROM equipment
              WHERE id = ?
              LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ── VALIDATE ────────────────────────────────────────────────

    /**
     * Validate & sanitise an input array.
     * Returns ['data' => [...]] on success, or ['error' => string] on failure.
     */
    public static function validate(array $input): array {
        $errors = [];

        // name ─────────────────────────────────────────────
        $name = trim($input['name'] ?? '');
        if ($name === '') {
            $errors[] = 'name is required.';
        } elseif (strlen($name) > 150) {
            $errors[] = 'name must be ≤ 150 characters.';
        }

        // category ─────────────────────────────────────────
        $category = trim($input['category'] ?? '');
        if (!in_array($category, self::CATEGORIES, true)) {
            $errors[] = 'category must be one of: ' . implode(', ', self::CATEGORIES);
        }

        // brand ────────────────────────────────────────────
        $brand = trim($input['brand'] ?? '');
        if ($brand === '') {
            $errors[] = 'brand is required.';
        } elseif (strlen($brand) > 80) {
            $errors[] = 'brand must be ≤ 80 characters.';
        }

        // serial_number ────────────────────────────────────
        $serial = trim($input['serial_number'] ?? '');
        if ($serial === '') {
            $errors[] = 'serial_number is required.';
        } elseif (strlen($serial) > 100) {
            $errors[] = 'serial_number must be ≤ 100 characters.';
        } elseif (!preg_match('/^[A-Za-z0-9\-_]+$/', $serial)) {
            $errors[] = 'serial_number may only contain letters, digits, hyphens, underscores.';
        }

        // status ───────────────────────────────────────────
        $status = trim($input['status'] ?? 'Available');
        if (!in_array($status, self::STATUSES, true)) {
            $errors[] = 'status must be one of: ' . implode(', ', self::STATUSES);
        }

        // purchase_date ────────────────────────────────────
        $date = trim($input['purchase_date'] ?? '');
        if ($date === '') {
            $errors[] = 'purchase_date is required (YYYY-MM-DD).';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                  || date('Y-m-d', strtotime($date)) !== $date) {
            $errors[] = 'purchase_date must be a valid date in YYYY-MM-DD format.';
        }

        if (!empty($errors)) {
            return ['error' => implode(' ', $errors)];
        }

        return ['data' => [
            'name'          => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'category'      => $category,
            'brand'         => htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'),
            'serial_number' => $serial,
            'status'        => $status,
            'purchase_date' => $date,
        ]];
    }

    // ── CREATE ──────────────────────────────────────────────────

    /**
     * Insert a new row.  Returns the new id on success.
     * Throws PDOException on duplicate serial_number (unique constraint).
     */
    public static function create(array $fields): int {
        $stmt = getDB()->prepare(
            'INSERT INTO equipment (name, category, brand, serial_number, status, purchase_date)
             VALUES (:name, :category, :brand, :serial_number, :status, :purchase_date)'
        );
        $stmt->execute($fields);
        return (int) getDB()->lastInsertId();
    }

    // ── UPDATE ──────────────────────────────────────────────────

    /**
     * Update an existing row.  Returns true if a row was affected.
     */
    public static function update(int $id, array $fields): bool {
        $stmt = getDB()->prepare(
            'UPDATE equipment
               SET name            = :name,
                   category        = :category,
                   brand           = :brand,
                   serial_number   = :serial_number,
                   status          = :status,
                   purchase_date   = :purchase_date
             WHERE id              = :id'
        );
        $fields[':id'] = $id;          // bind the WHERE clause
        $stmt->execute($fields);
        return $stmt->rowCount() > 0;
    }

    // ── DELETE ──────────────────────────────────────────────────

    /** Delete by primary key.  Returns true if a row was removed. */
    public static function delete(int $id): bool {
        $stmt = getDB()->prepare('DELETE FROM equipment WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ── HELPERS ─────────────────────────────────────────────────

    /** Check if a serial number already exists (optionally excluding an id). */
    public static function serialExists(string $serial, ?int $excludeId = null): bool {
        $sql  = 'SELECT 1 FROM equipment WHERE serial_number = ?';
        $args = [$serial];

        if ($excludeId !== null) {
            $sql  .= ' AND id != ?';
            $args[] = $excludeId;
        }

        $stmt = getDB()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetch() !== false;
    }
}
