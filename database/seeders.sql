USE aunt_joys_restaurant;

-- Test Users for Different Roles
-- All passwords are: password123

-- Test Customers
INSERT INTO users (role_id, username, email, password_hash, full_name, phone_number) VALUES
(1, 'jchikwanda', 'joyce.chikwanda@email.com', '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC', 'Joyce Chikwanda', '+265 991 234 567'),
(1, 'mphiri', 'mphiri@email.com', '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC', 'Michael Phiri', '+265 992 345 678'),
(1, 'mbanda', 'angela.mbanda@email.com', '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC', 'Angela Banda', '+265 993 456 789'),
(1, 'kaziwe', 'samuel.kaziwe@email.com', '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC', 'Samuel Kaziwe', '+265 994 567 890')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Test Administrators
INSERT INTO users (role_id, username, email, password_hash, full_name, phone_number) VALUES
(2, 'admin', 'admin@auntjoy.test', '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC', 'System Administrator', '+265 999 000 000'),
(2, 'jadmin', 'joyce.admin@auntjoy.test', '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC', 'Joyce Admin', '+265 888 111 111')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Test Sales Personnel
INSERT INTO users (role_id, username, email, password_hash, full_name, phone_number) VALUES
(3, 'jsales', 'john.sales@auntjoy.test', '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC', 'John Sales', '+265 888 222 222'),
(3, 'msales', 'mary.sales@auntjoy.test', '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC', 'Mary Sales', '+265 888 333 333')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Test Managers
INSERT INTO users (role_id, username, email, password_hash, full_name, phone_number) VALUES
(4, 'jmanager', 'james.manager@auntjoy.test', '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC', 'James Manager', '+265 888 444 444'),
(4, 'smanager', 'sarah.manager@auntjoy.test', '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC', 'Sarah Manager', '+265 888 555 555')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Sample Orders with Different Statuses

-- Get user IDs for reference (assuming they exist from previous insert)
SET @customer1 = (SELECT user_id FROM users WHERE email = 'joyce.chikwanda@email.com' LIMIT 1);
SET @customer2 = (SELECT user_id FROM users WHERE email = 'mphiri@email.com' LIMIT 1);
SET @customer3 = (SELECT user_id FROM users WHERE email = 'angela.mbanda@email.com' LIMIT 1);
SET @customer4 = (SELECT user_id FROM users WHERE email = 'samuel.kaziwe@email.com' LIMIT 1);
SET @sales1 = (SELECT user_id FROM users WHERE email = 'john.sales@auntjoy.test' LIMIT 1);
SET @sales2 = (SELECT user_id FROM users WHERE email = 'mary.sales@auntjoy.test' LIMIT 1);

-- Sample Orders
INSERT INTO orders (order_number, customer_id, delivery_address, contact_number, special_instructions, subtotal, delivery_fee, total_amount, order_status, payment_status, processed_by, order_date, delivered_at) VALUES
-- Pending Orders
('AJ-2025-001', @customer1, 'Area 43, House 123, Lilongwe', '+265 991 234 567', 'Extra chili sauce please', 13000, 1500, 14500, 'Pending', 'Pending', @sales1, '2025-12-17 10:30:00', NULL),
('AJ-2025-002', @customer2, 'Area 12, Flat 4B, Blantyre', '+265 992 345 678', 'Deliver to reception', 9800, 1500, 11300, 'Pending', 'Pending', @sales2, '2025-12-17 11:45:00', NULL),

-- Preparing Orders
('AJ-2025-003', @customer3, 'Mchinji Road, Area 25, Lilongwe', '+265 993 456 789', 'No onions in food please', 15200, 1500, 16700, 'Preparing', 'Paid', @sales1, '2025-12-17 09:15:00', NULL),
('AJ-2025-004', @customer4, 'Capital Hill, Building 7, Lilongwe', '+265 994 567 890', 'Call upon arrival', 11600, 1500, 13100, 'Preparing', 'Paid', @sales2, '2025-12-17 08:30:00', NULL),

-- Out for Delivery
('AJ-2025-005', @customer1, 'Area 43, House 123, Lilongwe', '+265 991 234 567', 'Gate code: 1234', 8700, 1500, 10200, 'Out for Delivery', 'Paid', @sales1, '2025-12-17 07:00:00', NULL),

-- Delivered Orders
('AJ-2025-006', @customer2, 'Area 12, Flat 4B, Blantyre', '+265 992 345 678', NULL, 6500, 1500, 8000, 'Delivered', 'Paid', @sales2, '2025-12-16 18:30:00', '2025-12-16 19:45:00'),
('AJ-2025-007', @customer3, 'Mchinji Road, Area 25, Lilongwe', '+265 993 456 789', 'Leave at doorstep', 11500, 1500, 13000, 'Delivered', 'Paid', @sales1, '2025-12-16 12:00:00', '2025-12-16 13:20:00'),
('AJ-2025-008', @customer4, 'Capital Hill, Building 7, Lilongwe', '+265 994 567 890', NULL, 14800, 1500, 16300, 'Delivered', 'Paid', @sales2, '2025-12-15 19:00:00', '2025-12-15 20:15:00'),

-- Cancelled Orders
('AJ-2025-009', @customer1, 'Area 43, House 123, Lilongwe', '+265 991 234 567', 'Customer cancelled', 5200, 1500, 6700, 'Cancelled', 'Refunded', @sales1, '2025-12-15 14:30:00', NULL)
ON DUPLICATE KEY UPDATE 
    order_status = VALUES(order_status),
    payment_status = VALUES(payment_status);

-- Order Items for Sample Orders


-- Order AJ-2025-001 (Pending) - Joyce Chikwanda
INSERT INTO order_items (order_id, meal_id, meal_name, quantity, unit_price, subtotal) VALUES
(1, 1, 'Nsima & Beef Stew', 2, 3500, 7000),
(1, 2, 'Peri-Peri Chicken', 1, 6500, 6500)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- Order AJ-2025-002 (Pending) - Michael Phiri  
INSERT INTO order_items (order_id, meal_id, meal_name, quantity, unit_price, subtotal) VALUES
(2, 3, 'Veggie Power Bowl', 1, 5200, 5200),
(2, 7, 'Butternut Ginger Soup', 2, 2300, 4600)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- Order AJ-2025-003 (Preparing) - Angela Banda
INSERT INTO order_items (order_id, meal_id, meal_name, quantity, unit_price, subtotal) VALUES
(3, 2, 'Peri-Peri Chicken', 2, 6500, 13000),
(3, 5, 'Coconut Cream Cake', 1, 4200, 4200)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- Order AJ-2025-004 (Preparing) - Samuel Kaziwe
INSERT INTO order_items (order_id, meal_id, meal_name, quantity, unit_price, subtotal) VALUES
(4, 1, 'Nsima & Beef Stew', 1, 3500, 3500),
(4, 6, 'Grilled Chambo', 1, 6800, 6800),
(4, 8, 'Tamarind Glazed Wings', 1, 4500, 4500)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- Order AJ-2025-005 (Out for Delivery) - Joyce Chikwanda
INSERT INTO order_items (order_id, meal_id, meal_name, quantity, unit_price, subtotal) VALUES
(5, 4, 'Classic Cheeseburger', 1, 4800, 4800),
(5, 8, 'Tamarind Glazed Wings', 1, 4500, 4500)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- Order AJ-2025-006 (Delivered) - Michael Phiri
INSERT INTO order_items (order_id, meal_id, meal_name, quantity, unit_price, subtotal) VALUES
(6, 2, 'Peri-Peri Chicken', 1, 6500, 6500)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- Order AJ-2025-007 (Delivered) - Angela Banda
INSERT INTO order_items (order_id, meal_id, meal_name, quantity, unit_price, subtotal) VALUES
(7, 3, 'Veggie Power Bowl', 2, 5200, 10400),
(7, 7, 'Butternut Ginger Soup', 1, 3000, 3000)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- Order AJ-2025-008 (Delivered) - Samuel Kaziwe
INSERT INTO order_items (order_id, meal_id, meal_name, quantity, unit_price, subtotal) VALUES
(8, 1, 'Nsima & Beef Stew', 2, 3500, 7000),
(8, 6, 'Grilled Chambo', 1, 6800, 6800),
(8, 4, 'Classic Cheeseburger', 1, 4800, 4800)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- Order AJ-2025-009 (Cancelled) - Joyce Chikwanda
INSERT INTO order_items (order_id, meal_id, meal_name, quantity, unit_price, subtotal) VALUES
(9, 5, 'Coconut Cream Cake', 1, 4200, 4200),
(9, 7, 'Butternut Ginger Soup', 1, 3000, 3000)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- ---------------------------------------------------------------------------
-- Order Status History
-- ---------------------------------------------------------------------------

-- History for order AJ-2025-005 (Out for Delivery)
INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes, changed_at) VALUES
(5, 'Pending', 'Preparing', @sales1, 'Order received and started preparation', '2025-12-17 07:15:00'),
(5, 'Preparing', 'Out for Delivery', @sales1, 'Food ready and handed to delivery', '2025-12-17 11:30:00')
ON DUPLICATE KEY UPDATE new_status = VALUES(new_status);

-- History for order AJ-2025-006 (Delivered)
INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes, changed_at) VALUES
(6, 'Pending', 'Preparing', @sales2, 'Order received', '2025-12-16 18:45:00'),
(6, 'Preparing', 'Out for Delivery', @sales2, 'Ready for delivery', '2025-12-16 19:15:00'),
(6, 'Out for Delivery', 'Delivered', @sales2, 'Delivered to customer', '2025-12-16 19:45:00')
ON DUPLICATE KEY UPDATE new_status = VALUES(new_status);

-- History for order AJ-2025-007 (Delivered)
INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes, changed_at) VALUES
(7, 'Pending', 'Preparing', @sales1, 'Order confirmed', '2025-12-16 12:15:00'),
(7, 'Preparing', 'Out for Delivery', @sales1, 'Preparation complete', '2025-12-16 12:45:00'),
(7, 'Out for Delivery', 'Delivered', @sales1, 'Left at doorstep as requested', '2025-12-16 13:20:00')
ON DUPLICATE KEY UPDATE new_status = VALUES(new_status);

-- History for order AJ-2025-008 (Delivered)
INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes, changed_at) VALUES
(8, 'Pending', 'Preparing', @sales2, 'Order received', '2025-12-15 19:15:00'),
(8, 'Preparing', 'Out for Delivery', @sales2, 'Order ready', '2025-12-15 19:45:00'),
(8, 'Out for Delivery', 'Delivered', @sales2, 'Customer received order', '2025-12-15 20:15:00')
ON DUPLICATE KEY UPDATE new_status = VALUES(new_status);

-- History for order AJ-2025-009 (Cancelled)
INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes, changed_at) VALUES
(9, 'Pending', 'Cancelled', @sales1, 'Customer called to cancel order', '2025-12-15 15:00:00')
ON DUPLICATE KEY UPDATE new_status = VALUES(new_status);

-- ---------------------------------------------------------------------------
-- Additional Test Meals (for variety in testing)
-- ---------------------------------------------------------------------------

INSERT INTO meals (category_id, meal_name, meal_description, price, image_url, is_featured, is_available, created_by) VALUES
(1, 'Chambo & Nsima', 'Fresh Lake Malawi chambo grilled to perfection served with traditional nsima and pumpkin leaves', 7200, 'assets/images/uploads/meals/chambo-nsima.jpg', 1, 1, 1),
(2, 'Mixed Grill Platter', 'Assorted grilled meats including chicken, beef, and sausages with peri-peri sauces', 8500, 'assets/images/uploads/meals/mixed-grill.jpg', 1, 1, 1),
(3, 'Mango Avocado Salad', 'Fresh local mango, avocado, mixed greens with citrus vinaigrette', 3800, 'assets/images/uploads/meals/mango-salad.jpg', 0, 1, 1),
(4, 'Chocolate Lava Cake', 'Warm chocolate cake with molten center served with vanilla ice cream', 4800, 'assets/images/uploads/meals/chocolate-lava.jpg', 1, 1, 1),
(5, 'Samosa Platter', 'Assorted vegetable and meat samosas with tamarind chutney', 3500, 'assets/images/uploads/meals/samosas.jpg', 0, 1, 1),
(6, 'Fresh Lemonade', 'Freshly squeezed lemonade with mint leaves', 2000, 'assets/images/uploads/meals/lemonade.jpg', 0, 1, 1)
ON DUPLICATE KEY UPDATE meal_name = VALUES(meal_name);

-- ---------------------------------------------------------------------------
-- Update statistics for testing
-- ---------------------------------------------------------------------------

-- Update user activity timestamps
UPDATE users SET last_login = NOW() WHERE role_id IN (2,3,4);

-- Set some meals as unavailable for testing
UPDATE meals SET is_available = 0 WHERE meal_id IN (5,7);

-- ---------------------------------------------------------------------------
-- Test Credentials Summary
-- ---------------------------------------------------------------------------
/*
All test passwords: password123

CUSTOMERS:
- jchikwanda / joyce.chikwanda@email.com (Joyce Chikwanda)
- mphiri / mphiri@email.com (Michael Phiri)  
- mbanda / angela.mbanda@email.com (Angela Banda)
- kaziwe / samuel.kaziwe@email.com (Samuel Kaziwe)

ADMINISTRATORS:
- admin / admin@auntjoy.test (System Administrator)
- jadmin / joyce.admin@auntjoy.test (Joyce Admin)

SALES PERSONNEL:
- jsales / john.sales@auntjoy.test (John Sales)
- msales / mary.sales@auntjoy.test (Mary Sales)

MANAGERS:
- jmanager / james.manager@auntjoy.test (James Manager)
- smanager / sarah.manager@auntjoy.test (Sarah Manager)
*/

SELECT 'Seeders loaded successfully!' as message;
SELECT CONCAT('Created ', COUNT(*), ' test users') as user_count FROM users;
SELECT CONCAT('Created ', COUNT(*), ' sample orders') as order_count FROM orders;
SELECT CONCAT('Created ', COUNT(*), ' order items') as item_count FROM order_items;