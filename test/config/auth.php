<?php
/**
 * config/auth.php — Session management & response helpers
 *
 * Usage in any API file:
 *     require_once __DIR__ . '/../config/auth.php';
 *     requireAuth();          // 401 if not logged in
 *     requireAdmin();         // 403 if not admin
 *     jsonResponse($data);    // sends JSON + exits
 */

// ── Session bootstrap ────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600,           // 1 hour
        'path'     => '/',
        'secure'   => false,          // true in production (HTTPS)
        'httponly' => true,           // JS cannot read the cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Guard: require an active session ─────────────────────────
function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorised — please log in.'], 401);
    }
}

// ── Guard: require admin role ────────────────────────────────
function requireAdmin(): void {
    requireAuth();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        jsonResponse(['error' => 'Forbidden — admin access required.'], 403);
    }
}

// ── Guard: require client role ───────────────────────────────
function requireClient(): void {
    requireAuth();
    if (($_SESSION['role'] ?? '') !== 'client') {
        jsonResponse(['error' => 'Forbidden — client access required.'], 403);
    }
}

// ── Check if current user is admin ───────────────────────────
function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

// ── Check if current user is client ──────────────────────────
function isClient(): bool {
    return ($_SESSION['role'] ?? '') === 'client';
}

// ── Get current user ID ──────────────────────────────────────
function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

// ── Guard: require Content-Type: application/json ───────────
function requireJSON(): void {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($ct, 'application/json') === false) {
        jsonResponse(['error' => 'Content-Type must be application/json.'], 415);
    }
}

// ── Uniform JSON response ────────────────────────────────────
function jsonResponse(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    // Prevent caching of API responses
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Read raw JSON body into associative array ───────────────
function readJsonBody(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        jsonResponse(['error' => 'Malformed JSON body.'], 400);
    }
    return $decoded;
}
