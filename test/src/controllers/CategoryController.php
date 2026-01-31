<?php
/**
 * src/controllers/CategoryController.php
 *
 * REST-style controller for category CRUD.
 * All write methods require admin authentication.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../models/CategoryModel.php';

class CategoryController {

    // ── GET /api/categories ─────────────────────────────────────
    /** List all categories. */
    public static function index(): void {
        requireAuth();

        $items = CategoryModel::findAll();
        jsonResponse(['data' => $items, 'count' => count($items)]);
    }

    // ── GET /api/categories/{id} ────────────────────────────────
    /** Show one category. */
    public static function show(int $id): void {
        requireAuth();

        $item = CategoryModel::findById($id);
        if ($item === null) {
            jsonResponse(['error' => 'Category not found.'], 404);
        }

        // Include product count
        $item['product_count'] = CategoryModel::countProducts($id);

        jsonResponse(['data' => $item]);
    }

    // ── POST /api/categories ────────────────────────────────────
    /** Create a new category (admin only). */
    public static function store(): void {
        requireAdmin();
        requireJSON();

        $body      = readJsonBody();
        $validated = CategoryModel::validate($body);

        if (isset($validated['error'])) {
            jsonResponse(['error' => $validated['error']], 422);
        }

        $fields = $validated['data'];

        // Duplicate name check
        if (CategoryModel::nameExists($fields['name'])) {
            jsonResponse(['error' => 'Category name already exists.'], 409);
        }

        try {
            $newId = CategoryModel::create($fields);
            $item  = CategoryModel::findById($newId);
            jsonResponse(['message' => 'Category created.', 'data' => $item], 201);
        } catch (\PDOException $e) {
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    // ── PUT /api/categories/{id} ────────────────────────────────
    /** Update an existing category (admin only). */
    public static function update(int $id): void {
        requireAdmin();
        requireJSON();

        $existing = CategoryModel::findById($id);
        if ($existing === null) {
            jsonResponse(['error' => 'Category not found.'], 404);
        }

        $body      = readJsonBody();
        $validated = CategoryModel::validate($body);

        if (isset($validated['error'])) {
            jsonResponse(['error' => $validated['error']], 422);
        }

        $fields = $validated['data'];

        // Duplicate name check, excluding current row
        if (CategoryModel::nameExists($fields['name'], $id)) {
            jsonResponse(['error' => 'Category name already exists on another category.'], 409);
        }

        try {
            $ok = CategoryModel::update($id, $fields);
            if (!$ok) {
                jsonResponse(['error' => 'Update failed — no rows affected.'], 500);
            }
            $item = CategoryModel::findById($id);
            jsonResponse(['message' => 'Category updated.', 'data' => $item]);
        } catch (\PDOException $e) {
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    // ── DELETE /api/categories/{id} ─────────────────────────────
    /** Delete a category (admin only). */
    public static function destroy(int $id): void {
        requireAdmin();

        $existing = CategoryModel::findById($id);
        if ($existing === null) {
            jsonResponse(['error' => 'Category not found.'], 404);
        }

        // Check if category has products
        $productCount = CategoryModel::countProducts($id);
        if ($productCount > 0) {
            jsonResponse([
                'error' => "Cannot delete category with {$productCount} product(s). Reassign or delete products first."
            ], 409);
        }

        try {
            $ok = CategoryModel::delete($id);
            if (!$ok) {
                jsonResponse(['error' => 'Delete failed.'], 500);
            }

            jsonResponse(['message' => 'Category deleted.']);
        } catch (\PDOException $e) {
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
}
