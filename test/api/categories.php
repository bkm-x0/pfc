<?php
/**
 * api/categories.php — Category CRUD endpoint router
 *
 * Routes:
 *   GET    /api/categories.php            → list all
 *   GET    /api/categories.php?id={id}    → show one
 *   POST   /api/categories.php            → create (admin only)
 *   PUT    /api/categories.php?id={id}    → update (admin only)
 *   DELETE /api/categories.php?id={id}    → delete (admin only)
 */

require_once __DIR__ . '/../src/controllers/CategoryController.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    match (true) {
        // ── List / Show ──
        $method === 'GET' && $id === null => CategoryController::index(),
        $method === 'GET' && $id !== null => CategoryController::show($id),

        // ── Create ──
        $method === 'POST' => CategoryController::store(),

        // ── Update ──
        $method === 'PUT' && $id !== null => CategoryController::update($id),

        // ── Delete ──
        $method === 'DELETE' && $id !== null => CategoryController::destroy($id),

        // ── Fallback ──
        default => jsonResponse(['error' => 'Route not found.'], 404),
    };
} catch (\Throwable $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
