<?php
/**
 * src/models/EquipmentModel.php
 *
 * Data-access layer for the `products` table (equipment).
 * Every public method maps to one SQL operation.
 * Input validation is enforced before writes.
 */

require_once __DIR__ . '/../../config/db.php';

class EquipmentModel {

    // ── Allowed enum values (single source of truth) ──────────
    public const STATUSES = [
        'Available', 'In Use', 'Under Maintenance', 'Retired'
    ];

    // ── READ ────────────────────────────────────────────────────

    /** Return every row with category and user info, newest first. */
    public static function findAll(): array {
        $stmt = getDB()->prepare(
            'SELECT p.id, p.name, p.category_id, c.name as category_name,
                    p.brand, p.serial_number, p.status, p.purchase_date,
                    p.assigned_to, u.username as assigned_to_username,
                    u.full_name as assigned_to_name,
                    p.notes, p.created_at, p.updated_at
               FROM products p
               LEFT JOIN categories c ON p.category_id = c.id
               LEFT JOIN users u ON p.assigned_to = u.id
              ORDER BY p.created_at DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Return products assigned to a specific user. */
    public static function findByAssignedTo(int $userId): array {
        $stmt = getDB()->prepare(
            'SELECT p.id, p.name, p.category_id, c.name as category_name,
                    p.brand, p.serial_number, p.status, p.purchase_date,
                    p.assigned_to, p.notes, p.created_at, p.updated_at
               FROM products p
               LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.assigned_to = ?
              ORDER BY p.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Return products by category. */
    public static function findByCategoryId(int $categoryId): array {
        $stmt = getDB()->prepare(
            'SELECT p.id, p.name, p.category_id, c.name as category_name,
                    p.brand, p.serial_number, p.status, p.purchase_date,
                    p.assigned_to, u.username as assigned_to_username,
                    p.notes, p.created_at, p.updated_at
               FROM products p
               LEFT JOIN categories c ON p.category_id = c.id
               LEFT JOIN users u ON p.assigned_to = u.id
              WHERE p.category_id = ?
              ORDER BY p.created_at DESC'
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    /** Return a single row by primary key, or null. */
    public static function findById(int $id): ?array {
        $stmt = getDB()->prepare(
            'SELECT p.id, p.name, p.category_id, c.name as category_name,
                    p.brand, p.serial_number, p.status, p.purchase_date,
                    p.assigned_to, u.username as assigned_to_username,
                    u.full_name as assigned_to_name,
                    p.notes, p.created_at, p.updated_at
               FROM products p
               LEFT JOIN categories c ON p.category_id = c.id
               LEFT JOIN users u ON p.assigned_to = u.id
              WHERE p.id = ?
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

        // category_id ──────────────────────────────────────
        $categoryId = $input['category_id'] ?? null;
        if ($categoryId === null || $categoryId === '') {
            $errors[] = 'category_id is required.';
        } elseif (!is_numeric($categoryId) || (int)$categoryId <= 0) {
            $errors[] = 'category_id must be a positive integer.';
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

        // assigned_to (optional) ───────────────────────────
        $assignedTo = $input['assigned_to'] ?? null;
        if ($assignedTo !== null && $assignedTo !== '') {
            if (!is_numeric($assignedTo) || (int)$assignedTo <= 0) {
                $errors[] = 'assigned_to must be a positive integer or null.';
            }
        } else {
            $assignedTo = null;
        }

        // notes (optional) ─────────────────────────────────
        $notes = trim($input['notes'] ?? '');
        if (strlen($notes) > 5000) {
            $errors[] = 'notes must be ≤ 5000 characters.';
        }

        if (!empty($errors)) {
            return ['error' => implode(' ', $errors)];
        }

        return ['data' => [
            'name'          => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'category_id'   => (int)$categoryId,
            'brand'         => htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'),
            'serial_number' => $serial,
            'status'        => $status,
            'purchase_date' => $date,
            'assigned_to'   => $assignedTo,
            'notes'         => htmlspecialchars($notes, ENT_QUOTES, 'UTF-8'),
        ]];
    }

    // ── CREATE ──────────────────────────────────────────────────

    /**
     * Insert a new row.  Returns the new id on success.
     * Throws PDOException on duplicate serial_number (unique constraint).
     */
    public static function create(array $fields): int {
        $stmt = getDB()->prepare(
            'INSERT INTO products (name, category_id, brand, serial_number, status, purchase_date, assigned_to, notes)
             VALUES (:name, :category_id, :brand, :serial_number, :status, :purchase_date, :assigned_to, :notes)'
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
            'UPDATE products
                SET name            = :name,
                    category_id     = :category_id,
                    brand           = :brand,
                    serial_number   = :serial_number,
                    status          = :status,
                    purchase_date   = :purchase_date,
                    assigned_to     = :assigned_to,
                    notes           = :notes
              WHERE id              = :id'
        );
        $fields[':id'] = $id;
        $stmt->execute($fields);
        return $stmt->rowCount() > 0;
    }

    // ── DELETE ──────────────────────────────────────────────────

    /** Delete by primary key.  Returns true if a row was removed. */
    public static function delete(int $id): bool {
        $stmt = getDB()->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ── HELPERS ─────────────────────────────────────────────────

    /** Check if a serial number already exists (optionally excluding an id). */
    public static function serialExists(string $serial, ?int $excludeId = null): bool {
        $sql  = 'SELECT 1 FROM products WHERE serial_number = ?';
        $args = [$serial];

        if ($excludeId !== null) {
            $sql  .= ' AND id != ?';
            $args[] = $excludeId;
        }

        $stmt = getDB()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetch() !== false;
    }

    /** Get statistics for dashboard. */
    public static function getStatistics(): array {
        $db = getDB();
        
        // Total products
        $stmt = $db->query('SELECT COUNT(*) FROM products');
        $total = (int) $stmt->fetchColumn();

        // By status
        $stmt = $db->query(
            'SELECT status, COUNT(*) as count
               FROM products
              GROUP BY status'
        );
        $byStatus = [];
        while ($row = $stmt->fetch()) {
            $byStatus[$row['status']] = (int)$row['count'];
        }

        // By category
        $stmt = $db->query(
            'SELECT c.name, COUNT(p.id) as count
               FROM categories c
               LEFT JOIN products p ON c.id = p.category_id
              GROUP BY c.id, c.name
              ORDER BY count DESC'
        );
        $byCategory = $stmt->fetchAll();

        return [
            'total'       => $total,
            'by_status'   => $byStatus,
            'by_category' => $byCategory,
        ];
    }
}
