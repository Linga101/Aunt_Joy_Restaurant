<?php
/**
 * Delete Category Controller
 * Handles deletion of meal categories
 */

require_once '../../config/db.php';

// Only allow DELETE or POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
    jsonResponse(false, null, 'Invalid request method');
}

// Check authentication and authorization
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Unauthorized - Please log in');
}

if (!hasRole('Administrator')) {
    jsonResponse(false, null, 'Access denied - Administrator role required');
}

// Get category ID
$data = json_decode(file_get_contents('php://input'), true);
$categoryId = $data['category_id'] ?? $_GET['category_id'] ?? null;

if (!$categoryId) {
    jsonResponse(false, null, 'Category ID is required');
}

try {
    $db = getDB();
    
    // Check if category exists
    $checkStmt = $db->prepare("SELECT category_name FROM categories WHERE category_id = :category_id");
    $checkStmt->execute(['category_id' => $categoryId]);
    $category = $checkStmt->fetch();
    
    if (!$category) {
        jsonResponse(false, null, 'Category not found');
    }
    
    // Check if category has associated meals
    $mealCheckStmt = $db->prepare("
        SELECT COUNT(*) as meal_count 
        FROM meals 
        WHERE category_id = :category_id
    ");
    $mealCheckStmt->execute(['category_id' => $categoryId]);
    $mealCheck = $mealCheckStmt->fetch();
    
    if ($mealCheck['meal_count'] > 0) {
        jsonResponse(false, null, 'Cannot delete category with associated meals. Remove all meals from this category first.');
    }
    
    // Safe to delete
    $deleteStmt = $db->prepare("DELETE FROM categories WHERE category_id = :category_id");
    $deleteStmt->execute(['category_id' => $categoryId]);
    
    jsonResponse(true, null, 'Category deleted successfully');
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to delete category: ' . $e->getMessage());
}
?>
