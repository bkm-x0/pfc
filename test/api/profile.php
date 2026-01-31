<?php
/**
 * Profile API — User Profile Management
 * 
 * Endpoints:
 * - GET  /api/profile.php       → Get current user profile
 * - PUT  /api/profile.php       → Update profile
 * - PUT  /api/profile.php?action=password → Change password
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../src/models/UserModel.php';

// Require authentication
requireAuth();

$userModel = new UserModel();
$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($userModel, $userId);
            break;
        
        case 'PUT':
            handlePut($userModel, $userId);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * GET — Get current user profile
 */
function handleGet(UserModel $userModel, int $userId): void
{
    $user = $userModel->findById($userId);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        return;
    }

    // Remove sensitive data
    unset($user['password_hash']);

    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
}

/**
 * PUT — Update profile or change password
 */
function handlePut(UserModel $userModel, int $userId): void
{
    $action = $_GET['action'] ?? null;

    // Enforce JSON content type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Content-Type must be application/json']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'password') {
        handlePasswordChange($userModel, $userId, $data);
    } else {
        handleProfileUpdate($userModel, $userId, $data);
    }
}

/**
 * Update user profile information
 */
function handleProfileUpdate(UserModel $userModel, int $userId, array $data): void
{
    $updateData = [];

    // Only allow updating specific fields
    if (isset($data['full_name'])) {
        $updateData['full_name'] = trim($data['full_name']);
        
        if (strlen($updateData['full_name']) > 150) {
            http_response_code(400);
            echo json_encode(['error' => 'Full name must be 150 characters or less']);
            return;
        }
    }

    if (isset($data['email'])) {
        $updateData['email'] = trim($data['email']);
        
        if (!empty($updateData['email']) && !filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        if (strlen($updateData['email']) > 150) {
            http_response_code(400);
            echo json_encode(['error' => 'Email must be 150 characters or less']);
            return;
        }
    }

    if (empty($updateData)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid fields to update']);
        return;
    }

    $success = $userModel->update($userId, $updateData);

    if ($success) {
        $user = $userModel->findById($userId);
        unset($user['password_hash']);

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile']);
    }
}

/**
 * Change user password
 */
function handlePasswordChange(UserModel $userModel, int $userId, array $data): void
{
    if (!isset($data['current_password']) || !isset($data['new_password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'current_password and new_password are required']);
        return;
    }

    $currentPassword = $data['current_password'];
    $newPassword = $data['new_password'];

    // Validate new password
    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'New password must be at least 6 characters']);
        return;
    }

    // Get current user
    $user = $userModel->findById($userId);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        return;
    }

    // Verify current password
    if (!password_verify($currentPassword, $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Current password is incorrect']);
        return;
    }

    // Update password
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $success = $userModel->update($userId, ['password_hash' => $newPasswordHash]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to change password']);
    }
}
