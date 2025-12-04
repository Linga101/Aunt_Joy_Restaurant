<?php
/**
 * Update Order Status Controller
 * Handles order status updates by sales personnel
 */

require_once '../../config/db.php';

// Only allow POST or PUT requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    jsonResponse(false, null, 'Invalid request method');
}

// Check authentication and authorization
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Unauthorized - Please log in');
}

if (!hasRole('Sales Personnel', 'Administrator')) {
    jsonResponse(false, null, 'Access denied - Sales Personnel role required');
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['order_id']) || empty($data['new_status'])) {
    jsonResponse(false, null, 'Order ID and new status are required');
}

$orderId = intval($data['order_id']);
$newStatus = sanitize($data['new_status']);
$notes = sanitize($data['notes'] ?? '');
$userId = getCurrentUserId();

// Valid status transitions
$validStatuses = ['Pending', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'];

if (!in_array($newStatus, $validStatuses)) {
    jsonResponse(false, null, 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses));
}

try {
    $db = getDB();
    
    // Get current order status
    $orderStmt = $db->prepare("
        SELECT order_status, customer_id 
        FROM orders 
        WHERE order_id = :order_id
    ");
    $orderStmt->execute(['order_id' => $orderId]);
    $order = $orderStmt->fetch();
    
    if (!$order) {
        jsonResponse(false, null, 'Order not found');
    }
    
    $oldStatus = $order['order_status'];
    
    // Check if status is already set
    if ($oldStatus === $newStatus) {
        jsonResponse(false, null, 'Order is already in ' . $newStatus . ' status');
    }
    
    // Validate status transition logic
    $allowedTransitions = [
        'Pending' => ['Preparing', 'Cancelled'],
        'Preparing' => ['Out for Delivery', 'Cancelled'],
        'Out for Delivery' => ['Delivered', 'Cancelled'],
        'Delivered' => [],
        'Cancelled' => []
    ];
    
    if (!in_array($newStatus, $allowedTransitions[$oldStatus])) {
        jsonResponse(false, null, "Cannot change status from $oldStatus to $newStatus");
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Update order status
    $updateStmt = $db->prepare("
        UPDATE orders 
        SET order_status = :new_status,
            delivered_at = CASE WHEN :status_check = 'Delivered' THEN CURRENT_TIMESTAMP ELSE delivered_at END,
            processed_by = :processed_by,
            updated_at = CURRENT_TIMESTAMP
        WHERE order_id = :order_id
    ");
    
    $updateResult = $updateStmt->execute([
        'order_id' => $orderId,
        'new_status' => $newStatus,
        'status_check' => $newStatus,
        'processed_by' => $userId
    ]);
    
    if (!$updateResult || $updateStmt->rowCount() === 0) {
        throw new Exception('Failed to update order status');
    }
    
    // Log status change in history
    $historyStmt = $db->prepare("
        INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes)
        VALUES (:order_id, :old_status, :new_status, :changed_by, :notes)
    ");
    
    $historyResult = $historyStmt->execute([
        'order_id' => $orderId,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'changed_by' => $userId,
        'notes' => !empty($notes) ? $notes : "Status updated to $newStatus"
    ]);
    
    if (!$historyResult) {
        throw new Exception('Failed to log status change');
    }
    
    // Commit transaction
    $db->commit();
    
    jsonResponse(true, [
        'order_id' => $orderId,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'timestamp' => date('Y-m-d H:i:s')
    ], 'Order status updated successfully');
    
} catch (Exception $e) {
    // Rollback transaction if active
    try {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
    } catch (Exception $rollbackError) {
        // Silent catch for rollback errors
    }
    
    jsonResponse(false, null, 'Failed to update order status: ' . $e->getMessage());
}
?>