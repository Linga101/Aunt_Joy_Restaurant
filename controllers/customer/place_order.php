<?php
/**
 * Place Order Controller
 * Handles customer order placement
 */

require_once '../../config/db.php';
require_once '../../config/logger.php';

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
    
    // Validate inventory for all items before placing order
    $inventoryIssues = [];
    foreach ($data['items'] as $item) {
        $mealId = $item['meal_id'];
        $requestedQuantity = $item['quantity'];
        
        // Check meal availability and stock
        $inventoryStmt = $db->prepare("
            SELECT 
                m.meal_name,
                m.track_inventory,
                COALESCE(mi.current_stock, 0) AS current_stock,
                m.is_available
            FROM meals m
            LEFT JOIN meal_inventory mi ON m.meal_id = mi.meal_id
            WHERE m.meal_id = :meal_id
        ");
        
        $inventoryStmt->execute(['meal_id' => $mealId]);
        $mealInventory = $inventoryStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mealInventory) {
            $inventoryIssues[] = "Meal not found: {$item['meal_name']}";
        } elseif (!$mealInventory['is_available']) {
            $inventoryIssues[] = "{$mealInventory['meal_name']} is no longer available";
        } elseif ($mealInventory['track_inventory'] && $mealInventory['current_stock'] < $requestedQuantity) {
            $available = $mealInventory['current_stock'];
            $inventoryIssues[] = "Only {$available} {$mealInventory['meal_name']} available (you requested {$requestedQuantity})";
        }
    }
    
    if (!empty($inventoryIssues)) {
        $db->rollBack();
        jsonResponse(false, null, 'Cannot place order due to inventory issues: ' . implode('; ', $inventoryIssues));
    }
    
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
    
    // Update inventory (reserve stock for pending orders)
    $inventoryStmt = $db->prepare("
        UPDATE meal_inventory 
        SET current_stock = current_stock - :quantity,
            last_updated = NOW(),
            updated_by = :customer_id
        WHERE meal_id = :meal_id AND current_stock >= :quantity
    ");
    
    $transactionStmt = $db->prepare("
        INSERT INTO inventory_transactions (
            meal_id, transaction_type, quantity, quantity_before, quantity_after,
            reference_id, reference_type, notes, created_by, created_at
        ) VALUES (
            :meal_id, 'SALE', :quantity, :quantity_before, :quantity_after,
            :reference_id, 'order', :notes, :created_by, NOW()
        )
    ");
    
    foreach ($data['items'] as $item) {
        // Only update inventory for meals that track it
        $checkInventory = $db->prepare("SELECT track_inventory FROM meals WHERE meal_id = :meal_id");
        $checkInventory->execute(['meal_id' => $item['meal_id']]);
        $trackInventory = $checkInventory->fetchColumn();
        
        if ($trackInventory) {
            // Get current stock for transaction record
            $currentStockStmt = $db->prepare("SELECT current_stock FROM meal_inventory WHERE meal_id = :meal_id");
            $currentStockStmt->execute(['meal_id' => $item['meal_id']]);
            $quantityBefore = $currentStockStmt->fetchColumn() ?: 0;
            
            // Update inventory
            $result = $inventoryStmt->execute([
                'quantity' => $item['quantity'],
                'meal_id' => $item['meal_id'],
                'customer_id' => $customerId
            ]);
            
            $quantityAfter = $quantityBefore - $item['quantity'];
            
            // Record transaction
            $transactionStmt->execute([
                'meal_id' => $item['meal_id'],
                'quantity' => -$item['quantity'],
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference_id' => $orderId,
                'reference_type' => 'order',
                'notes' => "Reserved for order {$orderNumber}",
                'created_by' => $customerId
            ]);
        }
    }
    
    // Update meal availability based on new stock levels
    $updateAvailabilityStmt = $db->prepare("
        UPDATE meals m 
        SET is_available = CASE 
            WHEN m.track_inventory = 0 THEN m.is_available
            WHEN COALESCE(mi.current_stock, 0) > 0 THEN 1
            ELSE 0
        END
        FROM meal_inventory mi
        WHERE mi.meal_id = m.meal_id
        AND m.track_inventory = 1
    ");
    $updateAvailabilityStmt->execute();
    
    $db->commit();
    
    // Log successful order placement
    logBusiness("Order placed successfully", [
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'customer_id' => $customerId,
        'total_amount' => $totalAmount,
        'delivery_address' => $deliveryAddress
    ]);
    
    jsonResponse(true, [
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'total_amount' => formatCurrency($totalAmount)
    ], 'Order placed successfully');
    
} catch (PDOException $e) {
    $db->rollBack();
    
    // Log order placement failure
    logError("Order placement failed", [
        'customer_id' => $customerId,
        'subtotal' => $subtotal,
        'total_amount' => $totalAmount,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
    
    jsonResponse(false, null, 'Failed to place order: ' . $e->getMessage());
}
?>