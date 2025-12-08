<?php
/**
 * Get Categories Controller
 * Fetches all meal categories
 */

require_once '../../config/db.php';

// Only allow GET or POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    jsonResponse(false, null, 'Invalid request method');
}

// Check authentication and authorization
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Unauthorized - Please log in');
}

if (!hasRole('Administrator')) {
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
    
    jsonResponse(true, $categories, 'Categories retrieved successfully');
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to retrieve categories: ' . $e->getMessage());
}
?>
