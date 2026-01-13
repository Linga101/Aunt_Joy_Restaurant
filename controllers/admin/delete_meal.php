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
    
    // Temporarily disable foreign key checks to force deletion
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
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
    
    // Re-enable foreign key checks
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    jsonResponse(true, null, 'Meal and associated image permanently deleted');
    
} catch (PDOException $e) {
    // Make sure to re-enable foreign key checks even if there's an error
    try {
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Exception $ignore) {}
    
    jsonResponse(false, null, 'Failed to delete meal: ' . $e->getMessage());
}
?>