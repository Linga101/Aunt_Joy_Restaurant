<?php
/**
 * Delete Meal Controller
 * Handles permanent meal deletion including image removal
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

// Get meal ID
$data = json_decode(file_get_contents('php://input'), true);
$mealId = $data['meal_id'] ?? $_GET['meal_id'] ?? null;

if (!$mealId) {
    jsonResponse(false, null, 'Meal ID is required');
}

try {
    $db = getDB();
    
    // Check if meal exists and get its image_url
    $checkStmt = $db->prepare("SELECT meal_name, image_url FROM meals WHERE meal_id = :meal_id");
    $checkStmt->execute(['meal_id' => $mealId]);
    $meal = $checkStmt->fetch();
    
    if (!$meal) {
        jsonResponse(false, null, 'Meal not found');
    }
    
    // Check if meal is in any orders
    $orderCheckStmt = $db->prepare("
        SELECT COUNT(*) as order_count 
        FROM order_items 
        WHERE meal_id = :meal_id
    ");
    $orderCheckStmt->execute(['meal_id' => $mealId]);
    $orderCheck = $orderCheckStmt->fetch();
    
    if ($orderCheck['order_count'] > 0) {
        // Don't delete, just mark as unavailable
        $updateStmt = $db->prepare("
            UPDATE meals 
            SET is_available = 0 
            WHERE meal_id = :meal_id
        ");
        $updateStmt->execute(['meal_id' => $mealId]);
        
        jsonResponse(true, null, 'Meal marked as unavailable (has existing orders)');
    } else {
        // Safe to permanently delete
        
        // Delete image file from filesystem if it exists
        if (!empty($meal['image_url'])) {
            $imagePath = __DIR__ . '/../../' . $meal['image_url'];
            if (is_file($imagePath)) {
                @unlink($imagePath);
            }
        }
        
        // Delete from database
        $deleteStmt = $db->prepare("DELETE FROM meals WHERE meal_id = :meal_id");
        $deleteStmt->execute(['meal_id' => $mealId]);
        
        jsonResponse(true, null, 'Meal and associated image permanently deleted');
    }
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to delete meal: ' . $e->getMessage());
}
?>