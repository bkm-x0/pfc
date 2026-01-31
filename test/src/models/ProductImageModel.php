<?php
/**
 * src/models/ProductImageModel.php
 *
 * Data-access layer for the `product_images` table.
 * Manages product photo uploads and associations.
 */

require_once __DIR__ . '/../../config/db.php';

class ProductImageModel {

    // ── READ ────────────────────────────────────────────────────

    /** Return all images for a specific product. */
    public static function findByProductId(int $productId): array {
        $stmt = getDB()->prepare(
            'SELECT id, product_id, image_path, is_primary, created_at
               FROM product_images
              WHERE product_id = ?
              ORDER BY is_primary DESC, created_at ASC'
        );
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    /** Return a single image by primary key, or null. */
    public static function findById(int $id): ?array {
        $stmt = getDB()->prepare(
            'SELECT id, product_id, image_path, is_primary, created_at
               FROM product_images
              WHERE id = ?
              LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Return the primary image for a product, or null. */
    public static function findPrimaryByProductId(int $productId): ?array {
        $stmt = getDB()->prepare(
            'SELECT id, product_id, image_path, is_primary, created_at
               FROM product_images
              WHERE product_id = ? AND is_primary = 1
              LIMIT 1'
        );
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    // ── CREATE ──────────────────────────────────────────────────

    /**
     * Insert a new product image.  Returns the new id on success.
     * If is_primary is true, unsets primary flag on other images for this product.
     */
    public static function create(int $productId, string $imagePath, bool $isPrimary = false): int {
        $db = getDB();
        
        // If this is primary, unset other primary images for this product
        if ($isPrimary) {
            $stmt = $db->prepare(
                'UPDATE product_images SET is_primary = 0 WHERE product_id = ?'
            );
            $stmt->execute([$productId]);
        }

        $stmt = $db->prepare(
            'INSERT INTO product_images (product_id, image_path, is_primary)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([$productId, $imagePath, $isPrimary ? 1 : 0]);
        return (int) $db->lastInsertId();
    }

    // ── UPDATE ──────────────────────────────────────────────────

    /**
     * Set an image as primary for its product.
     * Unsets primary flag on other images for the same product.
     */
    public static function setPrimary(int $imageId): bool {
        $image = self::findById($imageId);
        if ($image === null) {
            return false;
        }

        $db = getDB();
        
        // Unset other primary images for this product
        $stmt = $db->prepare(
            'UPDATE product_images SET is_primary = 0 WHERE product_id = ?'
        );
        $stmt->execute([$image['product_id']]);

        // Set this image as primary
        $stmt = $db->prepare(
            'UPDATE product_images SET is_primary = 1 WHERE id = ?'
        );
        $stmt->execute([$imageId]);
        return true;
    }

    // ── DELETE ──────────────────────────────────────────────────

    /**
     * Delete by primary key.  Returns the image path if successful, null otherwise.
     * Caller should delete the physical file after successful DB deletion.
     */
    public static function delete(int $id): ?string {
        $image = self::findById($id);
        if ($image === null) {
            return null;
        }

        $stmt = getDB()->prepare('DELETE FROM product_images WHERE id = ?');
        $stmt->execute([$id]);
        
        return $stmt->rowCount() > 0 ? $image['image_path'] : null;
    }

    /**
     * Delete all images for a product.  Returns array of image paths.
     * Caller should delete the physical files after successful DB deletion.
     */
    public static function deleteByProductId(int $productId): array {
        $images = self::findByProductId($productId);
        
        if (empty($images)) {
            return [];
        }

        $stmt = getDB()->prepare('DELETE FROM product_images WHERE product_id = ?');
        $stmt->execute([$productId]);

        return array_column($images, 'image_path');
    }

    // ── HELPERS ─────────────────────────────────────────────────

    /** Count images for a product. */
    public static function countByProductId(int $productId): int {
        $stmt = getDB()->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ?');
        $stmt->execute([$productId]);
        return (int) $stmt->fetchColumn();
    }

    /** Check if a product has any images. */
    public static function hasImages(int $productId): bool {
        return self::countByProductId($productId) > 0;
    }
}
