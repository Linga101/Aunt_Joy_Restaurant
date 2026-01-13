-- ---------------------------------------------------------------------------
-- Aunt Joy's Restaurant - Inventory-Driven Availability Function
-- This function automatically updates meal availability based on stock levels
-- ---------------------------------------------------------------------------

USE aunt_joys_restaurant;

-- Create a stored procedure to update meal availability based on inventory
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS update_meal_availability_from_inventory()
BEGIN
    -- Update meals to unavailable if out of stock and inventory tracking is enabled
    UPDATE meals m 
    SET is_available = 0 
    WHERE EXISTS (
        SELECT 1 FROM meal_inventory mi 
        WHERE mi.meal_id = m.meal_id 
        AND mi.current_stock <= 0 
        AND m.track_inventory = 1
    );
    
    -- Update meals to available if they have stock and inventory tracking is enabled
    UPDATE meals m 
    SET is_available = 1 
    WHERE EXISTS (
        SELECT 1 FROM meal_inventory mi 
        WHERE mi.meal_id = m.meal_id 
        AND mi.current_stock > 0 
        AND m.track_inventory = 1
    );
    
    -- For meals that don't track inventory, keep them available if manually set
    UPDATE meals m 
    SET is_available = 1 
    WHERE m.track_inventory = 0 AND m.is_available = 1;
END //
DELIMITER ;

-- Create a trigger to automatically update availability when inventory changes
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_inventory_update
AFTER UPDATE ON meal_inventory
FOR EACH ROW
BEGIN
    -- Check if stock changed to 0 or from 0
    IF (OLD.current_stock > 0 AND NEW.current_stock <= 0) OR 
       (OLD.current_stock <= 0 AND NEW.current_stock > 0) THEN
        CALL update_meal_availability_from_inventory();
    END IF;
END //
DELIMITER ;

-- Create a trigger to update availability after inventory insertion
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_inventory_insert
AFTER INSERT ON meal_inventory
FOR EACH ROW
BEGIN
    CALL update_meal_availability_from_inventory();
END //
DELIMITER ;

-- Create a trigger to update availability after order status changes
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_order_status_change
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    -- If order status changed to 'Delivered', deduct inventory
    IF OLD.order_status != 'Delivered' AND NEW.order_status = 'Delivered' THEN
        -- Create inventory transactions for each item
        INSERT INTO inventory_transactions (meal_id, transaction_type, quantity, quantity_before, quantity_after, reference_id, reference_type, notes, created_by, created_at)
        SELECT 
            oi.meal_id,
            'SALE',
            -oi.quantity,
            COALESCE(mi.current_stock, 0) + oi.quantity,
            COALESCE(mi.current_stock, 0),
            NEW.order_id,
            'order',
            CONCAT('Order fulfilled: ', NEW.order_number),
            NEW.processed_by,
            NEW.updated_at
        FROM order_items oi
        LEFT JOIN meal_inventory mi ON oi.meal_id = mi.meal_id
        WHERE oi.order_id = NEW.order_id 
        AND oi.meal_id IS NOT NULL;
        
        -- Update inventory quantities
        UPDATE meal_inventory mi
        SET mi.current_stock = mi.current_stock - oi.quantity,
            mi.last_updated = NEW.updated_at,
            mi.updated_by = NEW.processed_by
        FROM order_items oi
        WHERE mi.meal_id = oi.meal_id 
        AND oi.order_id = NEW.order_id 
        AND oi.meal_id IS NOT NULL
        AND mi.meal_id IS NOT NULL;
        
        -- Update meal availability
        CALL update_meal_availability_from_inventory();
    END IF;
    
    -- If order status changed to 'Cancelled' and was previously in preparation, restock items
    IF OLD.order_status IN ('Pending', 'Preparing') AND NEW.order_status = 'Cancelled' THEN
        -- Create inventory transactions for returned items
        INSERT INTO inventory_transactions (meal_id, transaction_type, quantity, quantity_before, quantity_after, reference_id, reference_type, notes, created_by, created_at)
        SELECT 
            oi.meal_id,
            'RETURN',
            oi.quantity,
            COALESCE(mi.current_stock, 0) - oi.quantity,
            COALESCE(mi.current_stock, 0),
            NEW.order_id,
            'order',
            CONCAT('Order cancelled: ', NEW.order_number),
            NEW.processed_by,
            NEW.updated_at
        FROM order_items oi
        LEFT JOIN meal_inventory mi ON oi.meal_id = mi.meal_id
        WHERE oi.order_id = NEW.order_id 
        AND oi.meal_id IS NOT NULL;
        
        -- Restock inventory quantities
        UPDATE meal_inventory mi
        SET mi.current_stock = mi.current_stock + oi.quantity,
            mi.last_updated = NEW.updated_at,
            mi.updated_by = NEW.processed_by
        FROM order_items oi
        WHERE mi.meal_id = oi.meal_id 
        AND oi.order_id = NEW.order_id 
        AND oi.meal_id IS NOT NULL
        AND mi.meal_id IS NOT NULL;
        
        -- Update meal availability
        CALL update_meal_availability_from_inventory();
    END IF;
END //
DELIMITER ;

-- Create a function to check if an order can be fulfilled based on inventory
DELIMITER //
CREATE FUNCTION IF NOT EXISTS can_fulfill_order(order_id_param INT) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE can_fulfill BOOLEAN DEFAULT TRUE;
    
    -- Check each item in the order
    SELECT COUNT(*) INTO @out_of_stock_items
    FROM order_items oi
    INNER JOIN meals m ON oi.meal_id = m.meal_id
    LEFT JOIN meal_inventory mi ON oi.meal_id = mi.meal_id
    WHERE oi.order_id = order_id_param
    AND oi.meal_id IS NOT NULL
    AND m.track_inventory = 1
    AND COALESCE(mi.current_stock, 0) < oi.quantity;
    
    IF @out_of_stock_items > 0 THEN
        SET can_fulfill = FALSE;
    END IF;
    
    RETURN can_fulfill;
END //
DELIMITER ;

-- Create a procedure to check and update pending orders with insufficient stock
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS check_pending_orders_stock()
BEGIN
    -- Find pending orders that cannot be fulfilled due to insufficient stock
    UPDATE orders o
    SET order_status = 'Failed - Insufficient Stock',
        updated_at = NOW()
    WHERE o.order_status = 'Pending'
    AND NOT can_fulfill_order(o.order_id);
    
    -- Add status history for failed orders
    INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes, changed_at)
    SELECT o.order_id, 'Pending', 'Failed - Insufficient Stock', 1, 'Insufficient stock to fulfill order', NOW()
    FROM orders o
    WHERE o.order_status = 'Failed - Insufficient Stock'
    AND NOT EXISTS (
        SELECT 1 FROM order_status_history osh 
        WHERE osh.order_id = o.order_id 
        AND osh.new_status = 'Failed - Insufficient Stock'
    );
END //
DELIMITER ;

-- Run the initial availability update
CALL update_meal_availability_from_inventory();

-- Check for any pending orders that might have stock issues
CALL check_pending_orders_stock();