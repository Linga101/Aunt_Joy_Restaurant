<?php
/**
 * Save Meal Controller
 * Handles creating and updating meals
 */

require_once '../../config/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Invalid request method');
}

// Check authentication and authorization
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Unauthorized - Please log in');
}

if (!hasRole('Administrator')) {
    jsonResponse(false, null, 'Access denied - Administrator role required');
}

// Get input data (JSON or form-data)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    // Handle both POST data and $_FILES
    $data = $_POST;
}

// Validate required fields
$required = ['category_id', 'meal_name', 'meal_description', 'price'];
foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        jsonResponse(false, null, ucfirst(str_replace('_', ' ', $field)) . ' is required');
    }
}

$mealId = $data['meal_id'] ?? null;
$categoryId = intval($data['category_id']);
$mealName = sanitize($data['meal_name']);
$mealDescription = sanitize($data['meal_description']);
$price = floatval($data['price']);
$imageUrl = sanitize($data['existing_image'] ?? '');
$isAvailable = isset($data['is_available']) ? filter_var($data['is_available'], FILTER_VALIDATE_BOOLEAN) : true;
$isFeatured = isset($data['is_featured']) ? filter_var($data['is_featured'], FILTER_VALIDATE_BOOLEAN) : false;
$preparationTime = intval($data['preparation_time'] ?? 20);
$userId = getCurrentUserId();

$uploadDir = __DIR__ . '/../../assets/images/uploads/meals/';
if (!empty($_FILES['image_file']['name'])) {
    $file = $_FILES['image_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, null, 'Failed to upload meal image.');
    }

    $allowedTypes = [
        'image/jpeg' => '.jpg',
        'image/jpg' => '.jpg',
        'image/png' => '.png'
    ];

    $mimeType = mime_content_type($file['tmp_name']);
    if (!isset($allowedTypes[$mimeType])) {
        jsonResponse(false, null, 'Invalid image format. Upload JPG or PNG.');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        jsonResponse(false, null, 'Image is too large. Maximum size is 2MB.');
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newFileName = uniqid('meal_', true) . $allowedTypes[$mimeType];
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        jsonResponse(false, null, 'Unable to save meal image.');
    }

    // Delete previous upload if replacing
    if (!empty($imageUrl) && strpos($imageUrl, 'assets/images/uploads/meals/') === 0) {
        $oldPath = __DIR__ . '/../../' . $imageUrl;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    $imageUrl = 'assets/images/uploads/meals/' . $newFileName;
}

if (empty($imageUrl)) {
    jsonResponse(false, null, 'Meal image is required.');
}

try {
    $db = getDB();
    
    if ($mealId) {
        // Update existing meal
        $stmt = $db->prepare("
            UPDATE meals SET
                category_id = :category_id,
                meal_name = :meal_name,
                meal_description = :meal_description,
                price = :price,
                image_url = :image_url,
                is_available = :is_available,
                is_featured = :is_featured,
                preparation_time = :preparation_time
            WHERE meal_id = :meal_id
        ");
        
        $stmt->execute([
            'meal_id' => $mealId,
            'category_id' => $categoryId,
            'meal_name' => $mealName,
            'meal_description' => $mealDescription,
            'price' => $price,
            'image_url' => $imageUrl,
            'is_available' => $isAvailable,
            'is_featured' => $isFeatured,
            'preparation_time' => $preparationTime
        ]);
        
        jsonResponse(true, ['meal_id' => $mealId], 'Meal updated successfully');
        
    } else {
        // Create new meal
        $stmt = $db->prepare("
            INSERT INTO meals (
                category_id, meal_name, meal_description, price,
                image_url, is_available, is_featured, preparation_time, created_by
            ) VALUES (
                :category_id, :meal_name, :meal_description, :price,
                :image_url, :is_available, :is_featured, :preparation_time, :created_by
            )
        ");
        
        $stmt->execute([
            'category_id' => $categoryId,
            'meal_name' => $mealName,
            'meal_description' => $mealDescription,
            'price' => $price,
            'image_url' => $imageUrl,
            'is_available' => $isAvailable,
            'is_featured' => $isFeatured,
            'preparation_time' => $preparationTime,
            'created_by' => $userId
        ]);
        
        $newMealId = $db->lastInsertId();
        
        jsonResponse(true, ['meal_id' => $newMealId], 'Meal created successfully');
    }
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to save meal: ' . $e->getMessage());
}
?>