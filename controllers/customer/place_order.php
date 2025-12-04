<?php
/**
 * Place Order Controller
 * Handles customer order placement
 */

require_once '../../config/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Invalid request method');
}

// Check authentication and authorization
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Unauthorized - Please log in');
}

if (!hasRole('Customer')) {
    jsonResponse(false, null, 'Access denied - Customer role required');
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['delivery_address']) || empty($data['contact_number']) || empty($data['items'])) {
    jsonResponse(false, null, 'Missing required fields: delivery_address, contact_number, items');
}

// Validate items array
if (!is_array($data['items']) || count($data['items']) === 0) {
    jsonResponse(false, null, 'Cart is empty');
}

$customerId = getCurrentUserId();
$deliveryAddress = sanitize($data['delivery_address']);
$contactNumber = sanitize($data['contact_number']);
$specialInstructions = sanitize($data['special_instructions'] ?? '');
$subtotal = floatval($data['subtotal'] ?? 0);
$deliveryFee = floatval($data['delivery_fee'] ?? 500);
$discountAmount = floatval($data['discount_amount'] ?? 0);
$totalAmount = floatval($data['total_amount']);

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Generate unique order number
    $orderNumber = generateOrderNumber();
    
    // Insert order
    $orderStmt = $db->prepare("
        INSERT INTO orders (
            order_number, customer_id, delivery_address, contact_number,
            special_instructions, subtotal, delivery_fee, discount_amount, total_amount
        ) VALUES (
            :order_number, :customer_id, :delivery_address, :contact_number,
            :special_instructions, :subtotal, :delivery_fee, :discount_amount, :total_amount
        )
    ");
    
    $orderStmt->execute([
        'order_number' => $orderNumber,
        'customer_id' => $customerId,
        'delivery_address' => $deliveryAddress,
        'contact_number' => $contactNumber,
        'special_instructions' => $specialInstructions,
        'subtotal' => $subtotal,
        'delivery_fee' => $deliveryFee,
        'discount_amount' => $discountAmount,
        'total_amount' => $totalAmount
    ]);
    
    $orderId = $db->lastInsertId();
    
    // Insert order items
    $itemStmt = $db->prepare("
        INSERT INTO order_items (
            order_id, meal_id, meal_name, quantity, unit_price, subtotal
        ) VALUES (
            :order_id, :meal_id, :meal_name, :quantity, :unit_price, :subtotal
        )
    ");
    
    foreach ($data['items'] as $item) {
        $itemStmt->execute([
            'order_id' => $orderId,
            'meal_id' => $item['meal_id'],
            'meal_name' => $item['meal_name'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['subtotal']
        ]);
    }
    
    // Log order status
    $historyStmt = $db->prepare("
        INSERT INTO order_status_history (order_id, new_status, changed_by, notes)
        VALUES (:order_id, 'Pending', :changed_by, 'Order placed by customer')
    ");
    $historyStmt->execute([
        'order_id' => $orderId,
        'changed_by' => $customerId
    ]);
    
    $db->commit();
    
    jsonResponse(true, [
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'total_amount' => formatCurrency($totalAmount)
    ], 'Order placed successfully');
    
} catch (PDOException $e) {
    $db->rollBack();
    jsonResponse(false, null, 'Failed to place order: ' . $e->getMessage());
}
?>