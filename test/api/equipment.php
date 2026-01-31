<?php
/**
 * api/equipment.php — Equipment CRUD endpoint router
 *
 * Routes:
 *   GET    /api/equipment.php            → list all
 *   GET    /api/equipment.php?id={id}    → show one
 *   POST   /api/equipment.php            → create
 *   PUT    /api/equipment.php?id={id}    → update
 *   DELETE /api/equipment.php?id={id}    → delete
 */

require_once __DIR__ . '/../src/controllers/EquipmentController.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    match (true) {
        // ── List / Show ──
        $method === 'GET' && $id === null => EquipmentController::index(),
        $method === 'GET' && $id !== null => EquipmentController::show($id),

        // ── Create ──
        $method === 'POST' => EquipmentController::store(),

        // ── Update ──
        $method === 'PUT' && $id !== null => EquipmentController::update($id),

        // ── Delete ──
        $method === 'DELETE' && $id !== null => EquipmentController::destroy($id),

        // ── Fallback ──
        default => jsonResponse(['error' => 'Route not found.'], 404),
    };
} catch (\Throwable $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
