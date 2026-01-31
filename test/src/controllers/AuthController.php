<?php
/**
 * src/controllers/AuthController.php
 *
 * Handles login & logout.
 * Called directly by api/auth.php after method routing.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController {

    /**
     * POST /api/auth/login
     * Body: { "username": "...", "password": "..." }
     */
    public static function login(): void {
        requireJSON();

        $body     = readJsonBody();
        $username = trim($body['username'] ?? '');
        $password = $body['password']      ?? '';

        // ── basic presence check ──
        if ($username === '' || $password === '') {
            jsonResponse(['error' => 'username and password are required.'], 400);
        }

        // ── lookup ──
        $user = UserModel::findByUsername($username);
        if ($user === null) {
            // Generic message — never reveal whether the username exists
            jsonResponse(['error' => 'Invalid username or password.'], 401);
        }

        // ── verify hash ──
        if (!UserModel::verifyPassword($password, $user['password_hash'])) {
            jsonResponse(['error' => 'Invalid username or password.'], 401);
        }

        // ── success → write session ──
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        session_regenerate_id(true);   // prevent session fixation

        jsonResponse([
            'message'  => 'Login successful.',
            'user'     => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public static function logout(): void {
        requireAuth();

        // Wipe session data & destroy cookie
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();

        jsonResponse(['message' => 'Logged out successfully.']);
    }

    /**
     * GET /api/auth/me
     * Returns current session info (used by frontend on page load).
     */
    public static function me(): void {
        if (empty($_SESSION['user_id'])) {
            jsonResponse(['authenticated' => false], 200);
        }

        jsonResponse([
            'authenticated' => true,
            'user'          => [
                'id'       => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role'     => $_SESSION['role'],
            ],
        ]);
    }
}
