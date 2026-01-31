<?php
/**
 * api/users.php — User management endpoint router
 *
 * Routes:
 *   GET    /api/users.php                 → list all users (admin only)
 *   GET    /api/users.php?clients=1       → list all clients (admin only)
 *   GET    /api/users.php?id={id}         → show one user (admin only)
 *   POST   /api/users.php                 → create user (admin only)
 *   PUT    /api/users.php?id={id}         → update user (admin only)
 *   DELETE /api/users.php?id={id}         → delete user (admin only)
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../src/models/UserModel.php';

$method  = $_SERVER['REQUEST_METHOD'];
$id      = isset($_GET['id']) ? (int) $_GET['id'] : null;
$clients = isset($_GET['clients']) && $_GET['clients'] === '1';

try {
    match (true) {
        // ── List all users ──
        $method === 'GET' && $id === null && !$clients => listUsers(),

        // ── List clients only ──
        $method === 'GET' && $id === null && $clients => listClients(),

        // ── Show one user ──
        $method === 'GET' && $id !== null => showUser($id),

        // ── Create user ──
        $method === 'POST' => createUser(),

        // ── Update user ──
        $method === 'PUT' && $id !== null => updateUser($id),

        // ── Delete user ──
        $method === 'DELETE' && $id !== null => deleteUser($id),

        // ── Fallback ──
        default => jsonResponse(['error' => 'Route not found.'], 404),
    };
} catch (\Throwable $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}

// ── Controller Functions ────────────────────────────────────────

function listUsers(): void {
    requireAdmin();
    $users = UserModel::findAll();
    jsonResponse(['data' => $users, 'count' => count($users)]);
}

function listClients(): void {
    requireAdmin();
    $clients = UserModel::findAllClients();
    jsonResponse(['data' => $clients, 'count' => count($clients)]);
}

function showUser(int $id): void {
    requireAdmin();
    $user = UserModel::findById($id);
    if ($user === null) {
        jsonResponse(['error' => 'User not found.'], 404);
    }
    // Remove password hash from response
    unset($user['password_hash']);
    jsonResponse(['data' => $user]);
}

function createUser(): void {
    requireAdmin();
    requireJSON();

    $body      = readJsonBody();
    $validated = UserModel::validate($body, false);

    if (isset($validated['error'])) {
        jsonResponse(['error' => $validated['error']], 422);
    }

    $fields = $validated['data'];

    // Check if username already exists
    if (UserModel::usernameExists($fields['username'])) {
        jsonResponse(['error' => 'Username already exists.'], 409);
    }

    try {
        $newId = UserModel::create($fields);
        $user  = UserModel::findById($newId);
        unset($user['password_hash']);
        jsonResponse(['message' => 'User created.', 'data' => $user], 201);
    } catch (\PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

function updateUser(int $id): void {
    requireAdmin();
    requireJSON();

    $existing = UserModel::findById($id);
    if ($existing === null) {
        jsonResponse(['error' => 'User not found.'], 404);
    }

    $body      = readJsonBody();
    $validated = UserModel::validate($body, true);

    if (isset($validated['error'])) {
        jsonResponse(['error' => $validated['error']], 422);
    }

    $fields = $validated['data'];

    // Check if username already exists (excluding current user)
    if (isset($fields['username']) && UserModel::usernameExists($fields['username'], $id)) {
        jsonResponse(['error' => 'Username already exists on another user.'], 409);
    }

    try {
        $ok = UserModel::update($id, $fields);
        if (!$ok) {
            jsonResponse(['error' => 'Update failed — no rows affected.'], 500);
        }
        $user = UserModel::findById($id);
        unset($user['password_hash']);
        jsonResponse(['message' => 'User updated.', 'data' => $user]);
    } catch (\PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

function deleteUser(int $id): void {
    requireAdmin();

    $existing = UserModel::findById($id);
    if ($existing === null) {
        jsonResponse(['error' => 'User not found.'], 404);
    }

    // Prevent deleting yourself
    if ($id === getCurrentUserId()) {
        jsonResponse(['error' => 'Cannot delete your own account.'], 409);
    }

    try {
        $ok = UserModel::delete($id);
        if (!$ok) {
            jsonResponse(['error' => 'Delete failed.'], 500);
        }
        jsonResponse(['message' => 'User deleted.']);
    } catch (\PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}
