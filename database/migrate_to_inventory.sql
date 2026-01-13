-- ---------------------------------------------------------------------------
-- Aunt Joy's Restaurant - Database Migration Script
-- Updates basic schema to include inventory management
-- ---------------------------------------------------------------------------

USE aunt_joys_restaurant;

-- Add inventory tracking columns to meals table if they don't exist
ALTER TABLE meals 
ADD COLUMN IF NOT EXISTS track_inventory TINYINT(1) NOT NULL DEFAULT 1,
ADD COLUMN IF NOT EXISTS min_stock_level INT UNSIGNED NOT NULL DEFAULT 5,
ADD COLUMN IF NOT EXISTS max_stock_level INT UNSIGNED NOT NULL DEFAULT 100;

-- Create meal_inventory table if it doesn't exist
CREATE TABLE IF NOT EXISTS meal_inventory (
    inventory_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    meal_id INT UNSIGNED NOT NULL,
    current_stock INT UNSIGNED NOT NULL DEFAULT 0,
    last_restocked TIMESTAMP NULL DEFAULT NULL,
    last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT UNSIGNED DEFAULT NULL,
    UNIQUE KEY uk_meal_inventory (meal_id),
    CONSTRAINT fk_inventory_meal FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_updated_by FOREIGN KEY (updated_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create inventory_transactions table if it doesn't exist
CREATE TABLE IF NOT EXISTS inventory_transactions (
    transaction_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    meal_id INT UNSIGNED NOT NULL,
    transaction_type ENUM('RESTOCK', 'SALE', 'WASTE', 'ADJUSTMENT', 'RETURN') NOT NULL,
    quantity INT NOT NULL,
    quantity_before INT UNSIGNED NOT NULL,
    quantity_after INT UNSIGNED NOT NULL,
    unit_cost DECIMAL(10,2) DEFAULT NULL,
    reference_id INT UNSIGNED DEFAULT NULL,
    reference_type VARCHAR(50) DEFAULT NULL,
    notes VARCHAR(255),
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_meal FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE CASCADE,
    CONSTRAINT fk_transactions_created_by FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create vw_inventory_status view if it doesn't exist
CREATE OR REPLACE VIEW IF NOT EXISTS vw_inventory_status AS
SELECT 
    m.meal_id,
    m.meal_name,
    m.category_id,
    c.category_name,
    m.price,
    m.min_stock_level,
    m.max_stock_level,
    COALESCE(mi.current_stock, 0) AS current_stock,
    CASE 
        WHEN COALESCE(mi.current_stock, 0) = 0 THEN 'Out of Stock'
        WHEN COALESCE(mi.current_stock, 0) <= m.min_stock_level THEN 'Low Stock'
        WHEN COALESCE(mi.current_stock, 0) >= m.max_stock_level THEN 'Overstocked'
        ELSE 'In Stock'
    END AS stock_status,
    CASE 
        WHEN m.track_inventory = 0 THEN 'Always Available'
        WHEN COALESCE(mi.current_stock, 0) = 0 THEN 'Not Available'
        WHEN COALESCE(mi.current_stock, 0) <= m.min_stock_level THEN 'Limited'
        ELSE 'Available'
    END AS availability_status,
    m.is_available,
    m.track_inventory,
    mi.last_restocked,
    mi.last_updated
FROM meals m
LEFT JOIN categories c ON m.category_id = c.category_id
LEFT JOIN meal_inventory mi ON m.meal_id = mi.meal_id;

-- Initialize inventory for existing meals
INSERT IGNORE INTO meal_inventory (meal_id, current_stock, last_restocked, updated_by)
SELECT 
    meal_id,
    CASE 
        WHEN meal_id IN (1, 2, 8) THEN 30 -- High stock for popular items
        WHEN meal_id IN (3, 4, 6, 9, 11, 12) THEN 15 -- Medium stock
        WHEN meal_id IN (5, 7, 10) THEN 5 -- Low stock for testing
        ELSE 20
    END AS current_stock,
    NOW() AS last_restocked,
    1 AS updated_by
FROM meals
WHERE meal_id NOT IN (SELECT meal_id FROM meal_inventory);

-- Update order_status enum to include 'Failed - Insufficient Stock'
ALTER TABLE orders 
MODIFY COLUMN order_status ENUM('Pending','Preparing','Out for Delivery','Delivered','Cancelled','Failed - Insufficient Stock') NOT NULL DEFAULT 'Pending';

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_meals_category ON meals(category_id);
CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(order_status);
CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_inventory_meal ON meal_inventory(meal_id);
CREATE INDEX IF NOT EXISTS idx_inventory_transactions_meal ON inventory_transactions(meal_id);

SELECT 'Database migration completed successfully!' as message;
SELECT CONCAT('Updated ', ROW_COUNT(), ' meals with inventory tracking') as meals_updated;
SELECT CONCAT('Created inventory for ', ROW_COUNT(), ' meals') as inventory_initialized;