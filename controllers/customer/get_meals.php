<?php
/**
 * Get Meals Controller
 * Fetches available meals with optional filtering
 */

require_once '../../config/db.php';

// Allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, null, 'Invalid request method');
}

try {
    $db = getDB();
    
    // Return categories list when requested (used by menu/admin pages)
    if (isset($_GET['categories'])) {
        $categoryStmt = $db->prepare("
            SELECT 
                category_id,
                category_name,
                description,
                display_order
            FROM categories
            WHERE is_active = 1
            ORDER BY display_order, category_name
        ");
        $categoryStmt->execute();
        $categories = $categoryStmt->fetchAll();
        jsonResponse(true, $categories, 'Categories retrieved successfully');
    }
    
    // Get query parameters
    $categoryId = $_GET['category_id'] ?? null;
    $search = $_GET['search'] ?? null;
    $featured = $_GET['featured'] ?? null;
    $includeAll = isset($_GET['include_all']) && $_GET['include_all'] === '1';
    
    // Only privileged roles can request all meals (including unavailable)
    if ($includeAll) {
        $allowedRoles = ['Administrator', 'Sales Personnel', 'Manager'];
        $userRole = getCurrentUserRole();
        if (!in_array($userRole, $allowedRoles, true)) {
            $includeAll = false;
        }
    }
    
    // Base query
    $query = "
        SELECT 
            m.meal_id,
            m.meal_name,
            m.meal_description,
            m.price,
            m.image_url,
            m.is_available,
            m.is_featured,
            m.preparation_time,
            c.category_id,
            c.category_name
        FROM meals m
        INNER JOIN categories c ON m.category_id = c.category_id
        WHERE c.is_active = 1
    ";
    
    if (!$includeAll) {
        $query .= " AND m.is_available = 1";
    }
    
    $params = [];
    
    // Add category filter
    if ($categoryId) {
        $query .= " AND m.category_id = :category_id";
        $params['category_id'] = $categoryId;
    }
    
    // Add search filter
    if ($search) {
        $query .= " AND (m.meal_name LIKE :search OR m.meal_description LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    // Add featured filter
    if ($featured) {
        $query .= " AND m.is_featured = 1";
    }
    
    // Order by category and name
    $query .= " ORDER BY c.display_order, m.meal_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $meals = $stmt->fetchAll();
    
    // Format prices
    foreach ($meals as &$meal) {
        $meal['price_formatted'] = formatCurrency($meal['price']);
    }
    
    jsonResponse(true, $meals, 'Meals retrieved successfully');
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to fetch meals: ' . $e->getMessage());
}
?>