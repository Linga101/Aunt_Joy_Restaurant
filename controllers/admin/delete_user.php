<?php
/**
 * Delete User Controller
 * Handles user deletion
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

// Get user ID
$data = json_decode(file_get_contents('php://input'), true);
$deleteUserId = $data['user_id'] ?? $_GET['user_id'] ?? null;

if (!$deleteUserId) {
    jsonResponse(false, null, 'User ID is required');
}

// Don't allow deleting yourself
$currentUserId = getCurrentUserId();
if ($deleteUserId == $currentUserId) {
    jsonResponse(false, null, 'You cannot delete your own account');
}

try {
    $db = getDB();
    
    // Check if user exists
    $checkStmt = $db->prepare("
        SELECT u.username, r.role_name 
        FROM users u
        INNER JOIN roles r ON u.role_id = r.role_id
        WHERE u.user_id = :user_id
    ");
    $checkStmt->execute(['user_id' => $deleteUserId]);
    $user = $checkStmt->fetch();
    
    if (!$user) {
        jsonResponse(false, null, 'User not found');
    }
    
    // Temporarily disable foreign key checks to force deletion
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Delete the user
    $deleteStmt = $db->prepare("DELETE FROM users WHERE user_id = :user_id");
    $deleteStmt->execute(['user_id' => $deleteUserId]);
    
    // Re-enable foreign key checks
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    jsonResponse(true, null, 'User deleted successfully');
    
} catch (PDOException $e) {
    // Make sure to re-enable foreign key checks even if there's an error
    try {
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Exception $ignore) {}
    
    jsonResponse(false, null, 'Failed to delete user: ' . $e->getMessage());
}
?>