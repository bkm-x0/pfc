<?php
/**
 * src/models/CategoryModel.php
 *
 * Data-access layer for the `categories` table.
 * Handles CRUD operations for equipment categories.
 */

require_once __DIR__ . '/../../config/db.php';

class CategoryModel {

    // ── READ ────────────────────────────────────────────────────

    /** Return all categories, ordered by name. */
    public static function findAll(): array {
        $stmt = getDB()->prepare(
            'SELECT id, name, description, created_at, updated_at
               FROM categories
              ORDER BY name ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Return a single category by primary key, or null. */
    public static function findById(int $id): ?array {
        $stmt = getDB()->prepare(
            'SELECT id, name, description, created_at, updated_at
               FROM categories
              WHERE id = ?
              LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Return a single category by name, or null. */
    public static function findByName(string $name): ?array {
        $stmt = getDB()->prepare(
            'SELECT id, name, description, created_at, updated_at
               FROM categories
              WHERE name = ?
              LIMIT 1'
        );
        $stmt->execute([$name]);
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
        } elseif (strlen($name) > 100) {
            $errors[] = 'name must be ≤ 100 characters.';
        }

        // description (optional) ───────────────────────────
        $description = trim($input['description'] ?? '');
        if (strlen($description) > 1000) {
            $errors[] = 'description must be ≤ 1000 characters.';
        }

        if (!empty($errors)) {
            return ['error' => implode(' ', $errors)];
        }

        return ['data' => [
            'name'        => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
        ]];
    }

    // ── CREATE ──────────────────────────────────────────────────

    /**
     * Insert a new category.  Returns the new id on success.
     * Throws PDOException on duplicate name (unique constraint).
     */
    public static function create(array $fields): int {
        $stmt = getDB()->prepare(
            'INSERT INTO categories (name, description)
             VALUES (:name, :description)'
        );
        $stmt->execute($fields);
        return (int) getDB()->lastInsertId();
    }

    // ── UPDATE ──────────────────────────────────────────────────

    /**
     * Update an existing category.  Returns true if a row was affected.
     */
    public static function update(int $id, array $fields): bool {
        $stmt = getDB()->prepare(
            'UPDATE categories
                SET name        = :name,
                    description = :description
              WHERE id          = :id'
        );
        $fields[':id'] = $id;
        $stmt->execute($fields);
        return $stmt->rowCount() > 0;
    }

    // ── DELETE ──────────────────────────────────────────────────

    /**
     * Delete by primary key.  Returns true if a row was removed.
     * Note: Will fail if products reference this category (FK constraint).
     */
    public static function delete(int $id): bool {
        $stmt = getDB()->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ── HELPERS ─────────────────────────────────────────────────

    /** Check if a category name already exists (optionally excluding an id). */
    public static function nameExists(string $name, ?int $excludeId = null): bool {
        $sql  = 'SELECT 1 FROM categories WHERE name = ?';
        $args = [$name];

        if ($excludeId !== null) {
            $sql  .= ' AND id != ?';
            $args[] = $excludeId;
        }

        $stmt = getDB()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetch() !== false;
    }

    /** Count products in a category. */
    public static function countProducts(int $categoryId): int {
        $stmt = getDB()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $stmt->execute([$categoryId]);
        return (int) $stmt->fetchColumn();
    }
}
