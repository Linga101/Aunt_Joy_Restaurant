<?php
/**
 * Get Customer Orders Controller
 * Fetches orders for logged-in customer
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

if (!hasRole('Customer')) {
    jsonResponse(false, null, 'Access denied - Customer role required');
}

try {
    $db = getDB();
    $customerId = getCurrentUserId();
    $orderId = $_GET['order_id'] ?? null;
    
    if ($orderId) {
        // Get single order with items
        $orderStmt = $db->prepare("
            SELECT 
                o.*,
                u.full_name as customer_name,
                u.email as customer_email
            FROM orders o
            INNER JOIN users u ON o.customer_id = u.user_id
            WHERE o.order_id = :order_id AND o.customer_id = :customer_id
        ");
        
        $orderStmt->execute([
            'order_id' => $orderId,
            'customer_id' => $customerId
        ]);
        
        $order = $orderStmt->fetch();
        
        if (!$order) {
            jsonResponse(false, null, 'Order not found');
        }
        
        // Get order items
        $itemsStmt = $db->prepare("
            SELECT * FROM order_items WHERE order_id = :order_id
        ");
        $itemsStmt->execute(['order_id' => $orderId]);
        $order['items'] = $itemsStmt->fetchAll();
        
        // Format amounts
        $order['subtotal_formatted'] = formatCurrency($order['subtotal']);
        $order['delivery_fee_formatted'] = formatCurrency($order['delivery_fee']);
        $order['discount_amount_formatted'] = formatCurrency($order['discount_amount']);
        $order['total_amount_formatted'] = formatCurrency($order['total_amount']);
        
        jsonResponse(true, $order, 'Order details retrieved');
        
    } else {
        // Get all customer orders
        $stmt = $db->prepare("
            SELECT 
                order_id,
                order_number,
                order_date,
                order_status,
                total_amount,
                delivery_address
            FROM orders
            WHERE customer_id = :customer_id
            ORDER BY order_date DESC
        ");
        
        $stmt->execute(['customer_id' => $customerId]);
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