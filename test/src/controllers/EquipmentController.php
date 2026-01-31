<?php
/**
 * src/controllers/EquipmentController.php
 *
 * REST-style controller for equipment (products) CRUD.
 * All methods enforce authentication first.
 * Write operations require admin role.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../models/EquipmentModel.php';
require_once __DIR__ . '/../models/ProductImageModel.php';

class EquipmentController {

    // ── GET /api/equipment ──────────────────────────────────────
    /** List all equipment (admin) or assigned equipment (client). */
    public static function index(): void {
        requireAuth();

        if (isAdmin()) {
            // Admin sees all products
            $items = EquipmentModel::findAll();
        } else {
            // Client sees only assigned products
            $userId = getCurrentUserId();
            $items = EquipmentModel::findByAssignedTo($userId);
        }

        // Attach images to each product
        foreach ($items as &$item) {
            $item['images'] = ProductImageModel::findByProductId($item['id']);
        }

        jsonResponse(['data' => $items, 'count' => count($items)]);
    }

    // ── GET /api/equipment/{id} ─────────────────────────────────
    /** Show one equipment item. */
    public static function show(int $id): void {
        requireAuth();

        $item = EquipmentModel::findById($id);
        if ($item === null) {
            jsonResponse(['error' => 'Equipment not found.'], 404);
        }

        // If client, verify they have access to this product
        if (isClient()) {
            $userId = getCurrentUserId();
            if ($item['assigned_to'] !== $userId) {
                jsonResponse(['error' => 'Forbidden — you do not have access to this product.'], 403);
            }
        }

        // Attach images
        $item['images'] = ProductImageModel::findByProductId($id);

        jsonResponse(['data' => $item]);
    }

    // ── POST /api/equipment ─────────────────────────────────────
    /** Create a new equipment item (admin only). */
    public static function store(): void {
        requireAdmin();
        requireJSON();

        $body      = readJsonBody();
        $validated = EquipmentModel::validate($body);

        if (isset($validated['error'])) {
            jsonResponse(['error' => $validated['error']], 422);
        }

        $fields = $validated['data'];

        // Duplicate serial check before hitting the DB constraint
        if (EquipmentModel::serialExists($fields['serial_number'])) {
            jsonResponse(['error' => 'serial_number already exists.'], 409);
        }

        try {
            $newId = EquipmentModel::create($fields);
            $item  = EquipmentModel::findById($newId);
            $item['images'] = [];
            jsonResponse(['message' => 'Equipment created.', 'data' => $item], 201);
        } catch (\PDOException $e) {
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    // ── PUT /api/equipment/{id} ─────────────────────────────────
    /** Update an existing equipment item (admin only). */
    public static function update(int $id): void {
        requireAdmin();
        requireJSON();

        $existing = EquipmentModel::findById($id);
        if ($existing === null) {
            jsonResponse(['error' => 'Equipment not found.'], 404);
        }

        $body      = readJsonBody();
        $validated = EquipmentModel::validate($body);

        if (isset($validated['error'])) {
            jsonResponse(['error' => $validated['error']], 422);
        }

        $fields = $validated['data'];

        // Duplicate serial check, excluding current row
        if (EquipmentModel::serialExists($fields['serial_number'], $id)) {
            jsonResponse(['error' => 'serial_number already exists on another item.'], 409);
        }

        try {
            $ok = EquipmentModel::update($id, $fields);
            if (!$ok) {
                jsonResponse(['error' => 'Update failed — no rows affected.'], 500);
            }
            $item = EquipmentModel::findById($id);
            $item['images'] = ProductImageModel::findByProductId($id);
            jsonResponse(['message' => 'Equipment updated.', 'data' => $item]);
        } catch (\PDOException $e) {
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    // ── DELETE /api/equipment/{id} ──────────────────────────────
    /** Delete an equipment item (admin only). */
    public static function destroy(int $id): void {
        requireAdmin();

        $existing = EquipmentModel::findById($id);
        if ($existing === null) {
            jsonResponse(['error' => 'Equipment not found.'], 404);
        }

        // Delete associated images first
        $imagePaths = ProductImageModel::deleteByProductId($id);
        
        // Delete physical image files
        foreach ($imagePaths as $path) {
            $fullPath = __DIR__ . '/../../' . $path;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        // Delete product
        $ok = EquipmentModel::delete($id);
        if (!$ok) {
            jsonResponse(['error' => 'Delete failed.'], 500);
        }

        jsonResponse(['message' => 'Equipment deleted.']);
    }

    // ── GET /api/equipment/statistics ───────────────────────────
    /** Get dashboard statistics (admin only). */
    public static function statistics(): void {
        requireAdmin();

        $stats = EquipmentModel::getStatistics();
        jsonResponse(['data' => $stats]);
    }

    // ── GET /api/equipment/category/{categoryId} ────────────────
    /** Get equipment by category. */
    public static function byCategory(int $categoryId): void {
        requireAuth();

        $items = EquipmentModel::findByCategoryId($categoryId);

        // If client, filter to only assigned products
        if (isClient()) {
            $userId = getCurrentUserId();
            $items = array_filter($items, fn($item) => $item['assigned_to'] === $userId);
            $items = array_values($items); // Re-index array
        }

        // Attach images
        foreach ($items as &$item) {
            $item['images'] = ProductImageModel::findByProductId($item['id']);
        }

        jsonResponse(['data' => $items, 'count' => count($items)]);
    }
}
