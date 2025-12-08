<?php
/**
 * Save Category Controller
 * Handles creating and updating meal categories
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

// Get input data
$data = json_decode(file_get_contents('php://input'), true) ?? [];

// Validate required fields
$categoryId = $data['category_id'] ?? null;
$categoryName = sanitize($data['category_name'] ?? '');
$description = sanitize($data['description'] ?? '');
$displayOrder = intval($data['display_order'] ?? 1);
$isActive = isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) : true;

if (empty($categoryName)) {
    jsonResponse(false, null, 'Category name is required');
}

if (strlen($categoryName) > 80) {
    jsonResponse(false, null, 'Category name must not exceed 80 characters');
}

try {
    $db = getDB();
    
    if ($categoryId) {
        // Update existing category
        $checkStmt = $db->prepare("SELECT category_id FROM categories WHERE category_id = :category_id");
        $checkStmt->execute(['category_id' => $categoryId]);
        
        if (!$checkStmt->fetch()) {
            jsonResponse(false, null, 'Category not found');
        }
        
        // Check for duplicate name (excluding current category)
        $dupCheckStmt = $db->prepare("
            SELECT category_id FROM categories 
            WHERE category_name = :category_name AND category_id != :category_id
        ");
        $dupCheckStmt->execute([
            'category_name' => $categoryName,
            'category_id' => $categoryId
        ]);
        
        if ($dupCheckStmt->fetch()) {
            jsonResponse(false, null, 'A category with this name already exists');
        }
        
        $updateStmt = $db->prepare("
            UPDATE categories 
            SET category_name = :category_name,
                description = :description,
                display_order = :display_order,
                is_active = :is_active
            WHERE category_id = :category_id
        ");
        
        $updateStmt->execute([
            'category_name' => $categoryName,
            'description' => $description,
            'display_order' => $displayOrder,
            'is_active' => $isActive ? 1 : 0,
            'category_id' => $categoryId
        ]);
        
        jsonResponse(true, ['category_id' => $categoryId], 'Category updated successfully');
    } else {
        // Create new category
        // Check for duplicate name
        $dupCheckStmt = $db->prepare("SELECT category_id FROM categories WHERE category_name = :category_name");
        $dupCheckStmt->execute(['category_name' => $categoryName]);
        
        if ($dupCheckStmt->fetch()) {
            jsonResponse(false, null, 'A category with this name already exists');
        }
        
        $insertStmt = $db->prepare("
            INSERT INTO categories (category_name, description, display_order, is_active)
            VALUES (:category_name, :description, :display_order, :is_active)
        ");
        
        $insertStmt->execute([
            'category_name' => $categoryName,
            'description' => $description,
            'display_order' => $displayOrder,
            'is_active' => $isActive ? 1 : 0
        ]);
        
        $newCategoryId = $db->lastInsertId();
        jsonResponse(true, ['category_id' => $newCategoryId], 'Category created successfully');
    }
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to save category: ' . $e->getMessage());
}
?>
