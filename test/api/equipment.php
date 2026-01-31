<?php
/**
 * api/equipment.php — Equipment CRUD endpoint router
 *
 * Routes:
 *   GET    /api/equipment.php                      → list all (admin) or assigned (client)
 *   GET    /api/equipment.php?id={id}              → show one
 *   GET    /api/equipment.php?action=statistics    → get statistics (admin only)
 *   GET    /api/equipment.php?category_id={id}     → get by category
 *   POST   /api/equipment.php                      → create (admin only)
 *   PUT    /api/equipment.php?id={id}              → update (admin only)
 *   DELETE /api/equipment.php?id={id}              → delete (admin only)
 */

require_once __DIR__ . '/../src/controllers/EquipmentController.php';

$method     = $_SERVER['REQUEST_METHOD'];
$id         = isset($_GET['id']) ? (int) $_GET['id'] : null;
$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$action     = $_GET['action'] ?? '';

try {
    match (true) {
        // ── Statistics ──
        $method === 'GET' && $action === 'statistics' => EquipmentController::statistics(),

        // ── By Category ──
        $method === 'GET' && $categoryId !== null => EquipmentController::byCategory($categoryId),

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
