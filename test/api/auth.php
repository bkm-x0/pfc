<?php
/**
 * api/auth.php — Auth endpoint router
 *
 * Routes:
 *   POST   /api/auth.php?action=login    → login
 *   POST   /api/auth.php?action=logout   → logout
 *   POST   /api/auth.php?action=register → register new user
 *   GET    /api/auth.php?action=me       → current user info
 */

require_once __DIR__ . '/../src/controllers/AuthController.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    match (true) {
        $method === 'POST' && $action === 'login'    => AuthController::login(),
        $method === 'POST' && $action === 'logout'   => AuthController::logout(),
        $method === 'POST' && $action === 'register' => AuthController::register(),
        $method === 'GET'  && $action === 'me'       => AuthController::me(),
        default => jsonResponse(['error' => 'Route not found.'], 404),
    };
} catch (\Throwable $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
