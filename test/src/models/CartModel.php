<?php
/**
 * CartModel — Shopping Cart Data Access Layer
 * 
 * Handles all database operations for the shopping cart:
 * - Add products to cart
 * - Update quantities
 * - Remove items
 * - Get cart items with product details
 * - Clear cart
 */

require_once __DIR__ . '/../../config/db.php';

class CartModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDbConnection();
    }

    /**
     * Add a product to the cart or update quantity if already exists
     * 
     * @param int $userId User ID
     * @param int $productId Product ID
     * @param int $quantity Quantity to add (default 1)
     * @return bool Success status
     */
    public function addToCart(int $userId, int $productId, int $quantity = 1): bool
    {
        try {
            // Check if product exists and is available
            $stmt = $this->pdo->prepare("
                SELECT id, status FROM products WHERE id = ?
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception("Product not found");
            }

            if ($product['status'] !== 'Available') {
                throw new Exception("Product is not available");
            }

            // Check if item already in cart
            $stmt = $this->pdo->prepare("
                SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?
            ");
            $stmt->execute([$userId, $productId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update quantity
                $newQuantity = $existing['quantity'] + $quantity;
                $stmt = $this->pdo->prepare("
                    UPDATE cart SET quantity = ? WHERE id = ?
                ");
                return $stmt->execute([$newQuantity, $existing['id']]);
            } else {
                // Insert new item
                $stmt = $this->pdo->prepare("
                    INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)
                ");
                return $stmt->execute([$userId, $productId, $quantity]);
            }
        } catch (Exception $e) {
            error_log("CartModel::addToCart error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update quantity of a cart item
     * 
     * @param int $cartId Cart item ID
     * @param int $userId User ID (for security)
     * @param int $quantity New quantity
     * @return bool Success status
     */
    public function updateQuantity(int $cartId, int $userId, int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$quantity, $cartId, $userId]);
        } catch (PDOException $e) {
            error_log("CartModel::updateQuantity error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove an item from the cart
     * 
     * @param int $cartId Cart item ID
     * @param int $userId User ID (for security)
     * @return bool Success status
     */
    public function removeFromCart(int $cartId, int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM cart WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$cartId, $userId]);
        } catch (PDOException $e) {
            error_log("CartModel::removeFromCart error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all cart items for a user with product details
     * 
     * @param int $userId User ID
     * @return array Cart items with product information
     */
    public function getCartItems(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.id as cart_id,
                    c.quantity,
                    c.added_at,
                    p.id as product_id,
                    p.name,
                    p.brand,
                    p.serial_number,
                    p.status,
                    cat.name as category_name,
                    (SELECT image_path FROM product_images 
                     WHERE product_id = p.id AND is_primary = 1 
                     LIMIT 1) as primary_image
                FROM cart c
                INNER JOIN products p ON c.product_id = p.id
                INNER JOIN categories cat ON p.category_id = cat.id
                WHERE c.user_id = ?
                ORDER BY c.added_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("CartModel::getCartItems error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get cart item count for a user
     * 
     * @param int $userId User ID
     * @return int Number of items in cart
     */
    public function getCartCount(int $userId): int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT SUM(quantity) as total FROM cart WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("CartModel::getCartCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear all items from user's cart
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function clearCart(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("CartModel::clearCart error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a product is in user's cart
     * 
     * @param int $userId User ID
     * @param int $productId Product ID
     * @return bool True if product is in cart
     */
    public function isInCart(int $userId, int $productId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM cart WHERE user_id = ? AND product_id = ?
            ");
            $stmt->execute([$userId, $productId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("CartModel::isInCart error: " . $e->getMessage());
            return false;
        }
    }
}
