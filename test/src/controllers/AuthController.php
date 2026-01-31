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
     * POST /api/auth/register
     * Body: { "username": "...", "password": "...", "full_name": "...", "email": "..." }
     */
    public static function register(): void {
        requireJSON();

        $body = readJsonBody();
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';
        $fullName = trim($body['full_name'] ?? '');
        $email = trim($body['email'] ?? '');

        // Validate required fields
        if ($username === '' || $password === '') {
            jsonResponse(['error' => 'username and password are required.'], 400);
        }

        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            jsonResponse(['error' => 'Username can only contain letters, numbers, and underscores.'], 400);
        }

        // Validate username length
        if (strlen($username) < 3 || strlen($username) > 64) {
            jsonResponse(['error' => 'Username must be between 3 and 64 characters.'], 400);
        }

        // Validate password length
        if (strlen($password) < 6) {
            jsonResponse(['error' => 'Password must be at least 6 characters.'], 400);
        }

        // Validate email if provided
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Invalid email format.'], 400);
        }

        // Check if username already exists
        $existingUser = UserModel::findByUsername($username);
        if ($existingUser !== null) {
            jsonResponse(['error' => 'Username already exists.'], 409);
        }

        // Create user (default role is 'client')
        $userModel = new UserModel();
        $userId = $userModel->create([
            'username' => $username,
            'password' => $password,
            'role' => 'client',
            'full_name' => $fullName,
            'email' => $email
        ]);

        if ($userId) {
            jsonResponse([
                'success' => true,
                'message' => 'Registration successful. You can now log in.',
                'user_id' => $userId
            ], 201);
        } else {
            jsonResponse(['error' => 'Failed to create user account.'], 500);
        }
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
