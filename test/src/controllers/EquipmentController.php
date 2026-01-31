<?php
/**
 * src/controllers/EquipmentController.php
 *
 * REST-style controller for equipment CRUD.
 * All methods enforce authentication first.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../models/EquipmentModel.php';

class EquipmentController {

    // ── GET /api/equipment ──────────────────────────────────────
    /** List all equipment. */
    public static function index(): void {
        requireAuth();

        $items = EquipmentModel::findAll();
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

        jsonResponse(['data' => $item]);
    }

    // ── POST /api/equipment ─────────────────────────────────────
    /** Create a new equipment item. */
    public static function store(): void {
        requireAuth();
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
            jsonResponse(['message' => 'Equipment created.', 'data' => $item], 201);
        } catch (\PDOException $e) {
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    // ── PUT /api/equipment/{id} ─────────────────────────────────
    /** Update an existing equipment item. */
    public static function update(int $id): void {
        requireAuth();
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
            jsonResponse(['message' => 'Equipment updated.', 'data' => $item]);
        } catch (\PDOException $e) {
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    // ── DELETE /api/equipment/{id} ──────────────────────────────
    /** Delete an equipment item. */
    public static function destroy(int $id): void {
        requireAuth();

        $existing = EquipmentModel::findById($id);
        if ($existing === null) {
            jsonResponse(['error' => 'Equipment not found.'], 404);
        }

        $ok = EquipmentModel::delete($id);
        if (!$ok) {
            jsonResponse(['error' => 'Delete failed.'], 500);
        }

        jsonResponse(['message' => 'Equipment deleted.']);
    }
}
