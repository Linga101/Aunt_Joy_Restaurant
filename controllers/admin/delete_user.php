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
    
    // Check if user has orders
    $orderCheckStmt = $db->prepare("
        SELECT COUNT(*) as order_count 
        FROM orders 
        WHERE customer_id = :user_id
    ");
    $orderCheckStmt->execute(['user_id' => $deleteUserId]);
    $orderCheck = $orderCheckStmt->fetch();
    
    if ($orderCheck['order_count'] > 0) {
        // Don't delete, just deactivate
        $updateStmt = $db->prepare("
            UPDATE users 
            SET is_active = 0 
            WHERE user_id = :user_id
        ");
        $updateStmt->execute(['user_id' => $deleteUserId]);
        
        jsonResponse(true, null, 'User deactivated (has existing orders)');
    } else {
        // Safe to delete
        $deleteStmt = $db->prepare("DELETE FROM users WHERE user_id = :user_id");
        $deleteStmt->execute(['user_id' => $deleteUserId]);
        
        jsonResponse(true, null, 'User deleted successfully');
    }
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to delete user: ' . $e->getMessage());
}
?>