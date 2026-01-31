<?php
/**
 * Cart API — Shopping Cart Management
 * 
 * Endpoints:
 * - GET    /api/cart.php                → Get cart items
 * - GET    /api/cart.php?action=count   → Get cart count
 * - POST   /api/cart.php                → Add item to cart
 * - PUT    /api/cart.php?id={id}        → Update quantity
 * - DELETE /api/cart.php?id={id}        → Remove item
 * - DELETE /api/cart.php?action=clear   → Clear cart
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../src/models/CartModel.php';

// Require authentication (clients only)
requireAuth();

// Only clients can use cart
if (!isClient()) {
    http_response_code(403);
    echo json_encode(['error' => 'Only clients can access the shopping cart']);
    exit;
}

$cartModel = new CartModel();
$userId = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($cartModel, $userId);
            break;
        
        case 'POST':
            handlePost($cartModel, $userId);
            break;
        
        case 'PUT':
            handlePut($cartModel, $userId);
            break;
        
        case 'DELETE':
            handleDelete($cartModel, $userId);
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
 * GET — Get cart items or count
 */
function handleGet(CartModel $cartModel, int $userId): void
{
    $action = $_GET['action'] ?? null;

    if ($action === 'count') {
        // Get cart count
        $count = $cartModel->getCartCount($userId);
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
    } else {
        // Get all cart items
        $items = $cartModel->getCartItems($userId);
        echo json_encode([
            'success' => true,
            'items' => $items,
            'count' => count($items)
        ]);
    }
}

/**
 * POST — Add item to cart
 */
function handlePost(CartModel $cartModel, int $userId): void
{
    // Enforce JSON content type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Content-Type must be application/json']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['product_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'product_id is required']);
        return;
    }

    $productId = (int)$data['product_id'];
    $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;

    if ($quantity < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Quantity must be at least 1']);
        return;
    }

    $success = $cartModel->addToCart($userId, $productId, $quantity);

    if ($success) {
        $count = $cartModel->getCartCount($userId);
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart',
            'cart_count' => $count
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to add product to cart. Product may not be available.']);
    }
}

/**
 * PUT — Update cart item quantity
 */
function handlePut(CartModel $cartModel, int $userId): void
{
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Cart item ID is required']);
        return;
    }

    $cartId = (int)$_GET['id'];

    // Enforce JSON content type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Content-Type must be application/json']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['quantity'])) {
        http_response_code(400);
        echo json_encode(['error' => 'quantity is required']);
        return;
    }

    $quantity = (int)$data['quantity'];

    if ($quantity < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Quantity must be at least 1']);
        return;
    }

    $success = $cartModel->updateQuantity($cartId, $userId, $quantity);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated'
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to update cart item']);
    }
}

/**
 * DELETE — Remove item or clear cart
 */
function handleDelete(CartModel $cartModel, int $userId): void
{
    $action = $_GET['action'] ?? null;

    if ($action === 'clear') {
        // Clear entire cart
        $success = $cartModel->clearCart($userId);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Cart cleared'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to clear cart']);
        }
    } else {
        // Remove single item
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Cart item ID is required']);
            return;
        }

        $cartId = (int)$_GET['id'];
        $success = $cartModel->removeFromCart($cartId, $userId);

        if ($success) {
            $count = $cartModel->getCartCount($userId);
            echo json_encode([
                'success' => true,
                'message' => 'Item removed from cart',
                'cart_count' => $count
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to remove item from cart']);
        }
    }
}
