<?php
/**
 * Get Categories Controller
 * Fetches all meal categories
 */

require_once '../../config/db.php';

// Set JSON header immediately
header('Content-Type: application/json');

// Debug: Log that the file was accessed
error_log('get_categories.php accessed at ' . date('Y-m-d H:i:s'));

// Only allow GET or POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    jsonResponse(false, null, 'Invalid request method');
}

// Check authentication and authorization
if (!isLoggedIn()) {
    error_log('User is not logged in');
    jsonResponse(false, null, 'Unauthorized - Please log in');
}

error_log('User logged in. Role: ' . getCurrentUserRole());

if (!hasRole('Administrator')) {
    error_log('User does not have Administrator role. Role: ' . getCurrentUserRole());
    jsonResponse(false, null, 'Access denied - Administrator role required');
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            category_id,
            category_name,
            description,
            display_order,
            is_active,
            created_at
        FROM categories
        ORDER BY display_order ASC, category_name ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    error_log('Categories retrieved: ' . count($categories) . ' found');
    
    jsonResponse(true, $categories, 'Categories retrieved successfully');
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    jsonResponse(false, null, 'Failed to retrieve categories: ' . $e->getMessage());
}
?>
