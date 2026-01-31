<?php
/**
 * api/images.php — Product image upload endpoint router
 *
 * Routes:
 *   POST   /api/images.php?action=upload              → upload images (admin only)
 *   DELETE /api/images.php?id={id}                    → delete image (admin only)
 *   PUT    /api/images.php?id={id}&action=primary     → set as primary (admin only)
 *   GET    /api/images.php?product_id={id}            → get images for product
 */

require_once __DIR__ . '/../src/controllers/ImageUploadController.php';

$method    = $_SERVER['REQUEST_METHOD'];
$id        = isset($_GET['id']) ? (int) $_GET['id'] : null;
$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : null;
$action    = $_GET['action'] ?? '';

try {
    match (true) {
        // ── Upload ──
        $method === 'POST' && $action === 'upload' => ImageUploadController::upload(),

        // ── Delete ──
        $method === 'DELETE' && $id !== null => ImageUploadController::delete($id),

        // ── Set Primary ──
        $method === 'PUT' && $id !== null && $action === 'primary' => ImageUploadController::setPrimary($id),

        // ── Get by Product ──
        $method === 'GET' && $productId !== null => ImageUploadController::getByProduct($productId),

        // ── Fallback ──
        default => jsonResponse(['error' => 'Route not found.'], 404),
    };
} catch (\Throwable $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
