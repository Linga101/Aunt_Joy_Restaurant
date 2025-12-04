<?php
/**
 * Get Orders Controller (Sales)
 * Fetches all orders for sales personnel
 */

require_once '../../config/db.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, null, 'Invalid request method');
}

// Check authentication and authorization
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Unauthorized - Please log in');
}

if (!hasRole('Sales Personnel', 'Administrator')) {
    jsonResponse(false, null, 'Access denied - Sales Personnel role required');
}

try {
    $db = getDB();
    
    $orderId = $_GET['order_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $limit = $_GET['limit'] ?? null;
    
    if ($orderId) {
        // Get single order with full details
        $orderStmt = $db->prepare("
            SELECT 
                o.*,
                u.full_name as customer_name,
                u.email as customer_email,
                u.phone_number as customer_phone
            FROM orders o
            INNER JOIN users u ON o.customer_id = u.user_id
            WHERE o.order_id = :order_id
        ");
        
        $orderStmt->execute(['order_id' => $orderId]);
        $order = $orderStmt->fetch();
        
        if (!$order) {
            jsonResponse(false, null, 'Order not found');
        }
        
        // Get order items
        $itemsStmt = $db->prepare("
            SELECT 
                oi.*,
                m.image_url
            FROM order_items oi
            LEFT JOIN meals m ON oi.meal_id = m.meal_id
            WHERE oi.order_id = :order_id
        ");
        $itemsStmt->execute(['order_id' => $orderId]);
        $order['items'] = $itemsStmt->fetchAll();
        
        // Get status history
        $historyStmt = $db->prepare("
            SELECT 
                h.*,
                u.full_name as changed_by_name
            FROM order_status_history h
            INNER JOIN users u ON h.changed_by = u.user_id
            WHERE h.order_id = :order_id
            ORDER BY h.changed_at ASC
        ");
        $historyStmt->execute(['order_id' => $orderId]);
        $order['status_history'] = $historyStmt->fetchAll();
        
        // Format amounts
        $order['subtotal_formatted'] = formatCurrency($order['subtotal']);
        $order['delivery_fee_formatted'] = formatCurrency($order['delivery_fee']);
        $order['discount_amount_formatted'] = formatCurrency($order['discount_amount']);
        $order['total_amount_formatted'] = formatCurrency($order['total_amount']);
        
        jsonResponse(true, $order, 'Order details retrieved');
        
    } else {
        // Get all orders with optional status filter
        $query = "
            SELECT 
                o.order_id,
                o.order_number,
                o.customer_id,
                o.delivery_address,
                o.contact_number,
                o.order_status,
                o.total_amount,
                o.order_date,
                o.special_instructions,
                u.full_name as customer_name,
                u.email as customer_email,
                COUNT(oi.order_item_id) as item_count
            FROM orders o
            INNER JOIN users u ON o.customer_id = u.user_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
        ";
        
        $params = [];
        
        if ($status) {
            $query .= " WHERE o.order_status = :status";
            $params['status'] = $status;
        }
        
        $query .= " GROUP BY o.order_id ORDER BY o.order_date DESC";
        
        if ($limit) {
            $query .= " LIMIT :limit";
            $params['limit'] = intval($limit);
        }
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            if ($key === 'limit') {
                $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":$key", $value);
            }
        }
        
        $stmt->execute();
        $orders = $stmt->fetchAll();
        
        // Format amounts
        foreach ($orders as &$order) {
            $order['total_amount_formatted'] = formatCurrency($order['total_amount']);
        }
        
        jsonResponse(true, $orders, 'Orders retrieved successfully');
    }
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to fetch orders: ' . $e->getMessage());
}
?>