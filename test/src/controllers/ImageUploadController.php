<?php
/**
 * src/controllers/ImageUploadController.php
 *
 * Handles secure file uploads for product images.
 * Validates file type, size, and prevents executable uploads.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../models/ProductImageModel.php';
require_once __DIR__ . '/../models/EquipmentModel.php';

class ImageUploadController {

    // Upload configuration
    private const UPLOAD_DIR = __DIR__ . '/../../uploads/products/';
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    // ── POST /api/images/upload ─────────────────────────────────
    /**
     * Upload one or more images for a product (admin only).
     * Expects multipart/form-data with:
     *   - product_id: int
     *   - images[]: file(s)
     *   - is_primary: bool (optional, for single image)
     */
    public static function upload(): void {
        requireAdmin();

        // Validate product_id
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        if ($productId === null || $productId <= 0) {
            jsonResponse(['error' => 'product_id is required and must be a positive integer.'], 400);
        }

        // Check if product exists
        $product = EquipmentModel::findById($productId);
        if ($product === null) {
            jsonResponse(['error' => 'Product not found.'], 404);
        }

        // Check if files were uploaded
        if (empty($_FILES['images'])) {
            jsonResponse(['error' => 'No images uploaded.'], 400);
        }

        // Ensure upload directory exists
        self::ensureUploadDirectory();

        $uploadedImages = [];
        $errors = [];

        // Handle multiple file upload
        $files = self::normalizeFilesArray($_FILES['images']);
        $isPrimary = isset($_POST['is_primary']) && $_POST['is_primary'] === 'true';

        foreach ($files as $index => $file) {
            $result = self::processUpload($file, $productId, $isPrimary && $index === 0);
            
            if (isset($result['error'])) {
                $errors[] = $result['error'];
            } else {
                $uploadedImages[] = $result;
            }
        }

        if (!empty($errors) && empty($uploadedImages)) {
            jsonResponse(['error' => 'All uploads failed: ' . implode('; ', $errors)], 400);
        }

        $response = [
            'message' => count($uploadedImages) . ' image(s) uploaded successfully.',
            'data' => $uploadedImages
        ];

        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }

        jsonResponse($response, 201);
    }

    // ── DELETE /api/images/{id} ─────────────────────────────────
    /** Delete a product image (admin only). */
    public static function delete(int $id): void {
        requireAdmin();

        $imagePath = ProductImageModel::delete($id);
        
        if ($imagePath === null) {
            jsonResponse(['error' => 'Image not found.'], 404);
        }

        // Delete physical file
        $fullPath = self::UPLOAD_DIR . basename($imagePath);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        jsonResponse(['message' => 'Image deleted successfully.']);
    }

    // ── PUT /api/images/{id}/primary ────────────────────────────
    /** Set an image as primary (admin only). */
    public static function setPrimary(int $id): void {
        requireAdmin();

        $ok = ProductImageModel::setPrimary($id);
        
        if (!$ok) {
            jsonResponse(['error' => 'Image not found.'], 404);
        }

        jsonResponse(['message' => 'Image set as primary.']);
    }

    // ── GET /api/images/product/{productId} ─────────────────────
    /** Get all images for a product. */
    public static function getByProduct(int $productId): void {
        requireAuth();

        $images = ProductImageModel::findByProductId($productId);
        jsonResponse(['data' => $images, 'count' => count($images)]);
    }

    // ── PRIVATE HELPERS ─────────────────────────────────────────

    /**
     * Process a single file upload.
     * Returns ['id' => ..., 'path' => ...] on success, or ['error' => ...] on failure.
     */
    private static function processUpload(array $file, int $productId, bool $isPrimary): array {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Upload error: ' . self::getUploadErrorMessage($file['error'])];
        }

        // Validate file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['error' => 'File too large. Maximum size: ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return ['error' => 'Invalid file type. Allowed: JPG, PNG, WebP'];
        }

        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return ['error' => 'Invalid file extension. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS)];
        }

        // Generate unique filename
        $filename = self::generateUniqueFilename($extension);
        $destination = self::UPLOAD_DIR . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['error' => 'Failed to save uploaded file.'];
        }

        // Save to database
        try {
            $imageId = ProductImageModel::create($productId, 'uploads/products/' . $filename, $isPrimary);
            
            return [
                'id' => $imageId,
                'path' => 'uploads/products/' . $filename,
                'is_primary' => $isPrimary
            ];
        } catch (\PDOException $e) {
            // Clean up file if database insert fails
            if (file_exists($destination)) {
                unlink($destination);
            }
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Normalize $_FILES array to handle both single and multiple uploads.
     */
    private static function normalizeFilesArray(array $files): array {
        $normalized = [];

        if (is_array($files['name'])) {
            // Multiple files
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $normalized[] = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];
            }
        } else {
            // Single file
            $normalized[] = $files;
        }

        return $normalized;
    }

    /**
     * Generate a unique filename with timestamp and random string.
     */
    private static function generateUniqueFilename(string $extension): string {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    /**
     * Ensure upload directory exists with proper permissions.
     */
    private static function ensureUploadDirectory(): void {
        if (!is_dir(self::UPLOAD_DIR)) {
            if (!mkdir(self::UPLOAD_DIR, 0755, true)) {
                jsonResponse(['error' => 'Failed to create upload directory.'], 500);
            }
        }

        // Create .htaccess to prevent PHP execution in upload directory
        $htaccessPath = self::UPLOAD_DIR . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Prevent PHP execution\n";
            $htaccessContent .= "php_flag engine off\n";
            $htaccessContent .= "<FilesMatch \"\\.php$\">\n";
            $htaccessContent .= "    Deny from all\n";
            $htaccessContent .= "</FilesMatch>\n";
            file_put_contents($htaccessPath, $htaccessContent);
        }
    }

    /**
     * Get human-readable upload error message.
     */
    private static function getUploadErrorMessage(int $errorCode): string {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'Upload stopped by extension',
            default               => 'Unknown upload error',
        };
    }
}
