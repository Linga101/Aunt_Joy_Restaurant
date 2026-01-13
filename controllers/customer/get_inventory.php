<?php
/**
 * Get meal inventory information for cart validation
 */

require_once '../../config/db.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, null, 'Invalid request method');
}

// Get meal ID
$mealId = filter_input(INPUT_GET, 'meal_id', FILTER_VALIDATE_INT);
if (!$mealId) {
    jsonResponse(false, null, 'Meal ID is required');
}

try {
    $db = getDB();
    
    // Get meal inventory information
    $stmt = $db->prepare("
        SELECT 
            m.meal_id,
            m.meal_name,
            m.track_inventory,
            m.min_stock_level,
            m.max_stock_level,
            m.is_available,
            COALESCE(mi.current_stock, 0) AS current_stock,
            CASE 
                WHEN m.track_inventory = 0 THEN m.max_stock_level
                WHEN COALESCE(mi.current_stock, 0) = 0 THEN 0
                WHEN COALESCE(mi.current_stock, 0) <= m.min_stock_level THEN COALESCE(mi.current_stock, 0)
                ELSE m.max_stock_level
            END AS max_quantity,
            CASE 
                WHEN m.track_inventory = 0 THEN 1
                WHEN COALESCE(mi.current_stock, 0) > 0 THEN 1
                ELSE 0
            END AS in_stock,
            CASE 
                WHEN COALESCE(mi.current_stock, 0) = 0 THEN 'Out of Stock'
                WHEN COALESCE(mi.current_stock, 0) <= m.min_stock_level THEN 'Low Stock'
                ELSE 'In Stock'
            END AS stock_status
        FROM meals m
        LEFT JOIN meal_inventory mi ON m.meal_id = mi.meal_id
        WHERE m.meal_id = :meal_id
        AND m.is_available = 1
    ");
    
    $stmt->execute(['meal_id' => $mealId]);
    $meal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$meal) {
        jsonResponse(false, null, 'Meal not found or unavailable');
    }
    
    // Prepare response data
    $inventoryData = [
        'meal_id' => $meal['meal_id'],
        'meal_name' => $meal['meal_name'],
        'track_inventory' => (bool)$meal['track_inventory'],
        'current_stock' => (int)$meal['current_stock'],
        'min_stock_level' => (int)$meal['min_stock_level'],
        'max_stock_level' => (int)$meal['max_stock_level'],
        'max_quantity' => (int)$meal['max_quantity'],
        'in_stock' => (bool)$meal['in_stock'],
        'stock_status' => $meal['stock_status'],
        'available' => (bool)$meal['in_stock']
    ];
    
    jsonResponse(true, $inventoryData, 'Inventory data retrieved');
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Error retrieving inventory: ' . $e->getMessage());
}
?>