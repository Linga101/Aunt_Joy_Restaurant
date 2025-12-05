-- ---------------------------------------------------------------------------
-- Aunt Joy's Restaurant - Database Schema
-- ---------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS aunt_joys_restaurant
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE aunt_joys_restaurant;

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Drop existing tables to avoid schema drift when re-importing
-- (order matters because of foreign keys)
-- ---------------------------------------------------------------------------
DROP VIEW IF EXISTS vw_order_summary;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS meals;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- ---------------------------------------------------------------------------
-- Roles
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    role_id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (role_id, role_name)
VALUES
    (1, 'Customer'),
    (2, 'Administrator'),
    (3, 'Sales Personnel'),
    (4, 'Manager')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

-- ---------------------------------------------------------------------------
-- Users
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    role_id TINYINT UNSIGNED NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone_number VARCHAR(30),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_roles FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create a default admin user (password: password123)
INSERT INTO users (user_id, role_id, username, email, password_hash, full_name, phone_number)
VALUES
    (
        1,
        2,
        'admin',
        'admin@auntjoy.test',
        '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC',
        'System Administrator',
        '+265 999 000 000'
    )
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- ---------------------------------------------------------------------------
-- Categories
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    category_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(80) NOT NULL,
    description VARCHAR(255),
    display_order INT UNSIGNED NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (category_id, category_name, description, display_order)
VALUES
    (1, 'Local Favorites', 'Comfort dishes inspired by Malawian homes', 1),
    (2, 'International Grill', 'Continental classics & peri-peri signatures', 2),
    (3, 'Veggie Delights', 'Plant-forward dishes for everyone', 3),
    (4, 'Desserts & Treats', 'Sweet bakes and chilled puddings', 4),
    (5, 'Street Bites', 'Handheld bites & sharable starters', 5),
    (6, 'Soups & Sips', 'Warm bowls and refreshing drinks', 6)
ON DUPLICATE KEY UPDATE 
    category_name = VALUES(category_name),
    description = VALUES(description),
    display_order = VALUES(display_order);

-- ---------------------------------------------------------------------------
-- Meals
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS meals (
    meal_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    meal_name VARCHAR(120) NOT NULL,
    meal_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(120),
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    preparation_time INT UNSIGNED NOT NULL DEFAULT 20,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_meals_category FOREIGN KEY (category_id) REFERENCES categories(category_id),
    CONSTRAINT fk_meals_created_by FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO meals (meal_id, category_id, meal_name, meal_description, price, image_url, is_featured, created_by)
VALUES
    (
        1,
        1,
        'Nsima & Beef Stew',
        'Stone-ground maize meal with slow-braised beef in tomato gravy and seasonal greens.',
        3500,
        'assets/images/meals/nsima-beef.jpg',
        1,
        1
    ),
    (
        2,
        2,
        'Peri-Peri Chicken',
        'Flame-grilled quarter chicken finished with citrus peri-peri glaze and fire-roasted peppers.',
        6500,
        'assets/images/meals/peri-peri-chicken.jpeg',
        1,
        1
    ),
    (
        3,
        3,
        'Veggie Power Bowl',
        'Roasted butternut, quinoa, avocado crema, and crunchy seeds with herb dressing.',
        5200,
        'assets/images/meals/veggie-power-bowl.jpg',
        1,
        1
    ),
    (
        4,
        5,
        'Classic Cheeseburger',
        'Smoked cheddar, caramelized onions, butter lettuce, and pickled relish on brioche.',
        4800,
        'assets/images/meals/classic-cheeseburger.jpg',
        0,
        1
    ),
    (
        5,
        4,
        'Coconut Cream Cake',
        'Three-layer coconut sponge with vanilla bean custard and roasted flakes.',
        4200,
        'assets/images/meals/coconut-cream-cake.jpeg',
        0,
        1
    ),
    (
        6,
        2,
        'Grilled Chambo',
        'Lake Malawi chambo fillet brushed with garlic butter, served with lemon rice.',
        6800,
        'assets/images/meals/grilled-chambo.jpg',
        1,
        1
    ),
    (
        7,
        6,
        'Butternut Ginger Soup',
        'Silky roasted butternut simmered with ginger, coconut milk, and pumpkin seeds.',
        3000,
        'assets/images/meals/butternut-soup.jpeg',
        0,
        1
    ),
    (
        8,
        5,
        'Tamarind Glazed Wings',
        'Double-fried chicken wings tossed in sticky tamarind and toasted sesame.',
        4500,
        'assets/images/meals/tamarind-wings.jpeg',
        1,
        1
    )
ON DUPLICATE KEY UPDATE 
    meal_name = VALUES(meal_name),
    meal_description = VALUES(meal_description),
    price = VALUES(price),
    image_url = VALUES(image_url),
    is_featured = VALUES(is_featured);

-- ---------------------------------------------------------------------------
-- Orders
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    order_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(30) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NOT NULL,
    delivery_address TEXT NOT NULL,
    contact_number VARCHAR(30) NOT NULL,
    special_instructions TEXT,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    order_status ENUM('Pending','Preparing','Out for Delivery','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
    payment_status ENUM('Pending','Paid','Refunded') NOT NULL DEFAULT 'Pending',
    processed_by INT UNSIGNED DEFAULT NULL,
    order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES users(user_id),
    CONSTRAINT fk_orders_processed_by FOREIGN KEY (processed_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Order Items
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    meal_id INT UNSIGNED DEFAULT NULL,
    meal_name VARCHAR(120) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
    CONSTRAINT fk_order_items_meal FOREIGN KEY (meal_id) REFERENCES meals(meal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Order Status History
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_status_history (
    history_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(40),
    new_status VARCHAR(40) NOT NULL,
    changed_by INT UNSIGNED NOT NULL,
    notes VARCHAR(255),
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
    CONSTRAINT fk_history_user FOREIGN KEY (changed_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Sample Orders Data for Testing
-- ---------------------------------------------------------------------------
-- Create sample customer user
INSERT INTO users (user_id, role_id, username, email, password_hash, full_name, phone_number)
VALUES
    (
        2,
        1,
        'john_customer',
        'john@example.com',
        '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC',
        'John Customer',
        '+265 999 123 456'
    ),
    (
        3,
        1,
        'jane_customer',
        'jane@example.com',
        '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC',
        'Jane Doe',
        '+265 999 654 321'
    ),
    (
        4,
        4,
        'manager_user',
        'manager@auntjoy.test',
        '$2y$10$1CE1rFJ0ZnIcXtNCmieYGO/vpGt8aV.SvtTDHkJ/xZ4wNG0Ax7AnC',
        'Restaurant Manager',
        '+265 999 111 111'
    )
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Create sample orders for December 2025
INSERT INTO orders (order_id, order_number, customer_id, delivery_address, contact_number, special_instructions, subtotal, delivery_fee, discount_amount, total_amount, order_status, payment_status, processed_by, order_date)
VALUES
    (1, 'AJ-2501', 2, 'City Center, Plot 14', '+265 999 123 456', 'Extra spicy please', 13000.00, 1500.00, 0, 14500.00, 'Delivered', 'Paid', 4, '2025-12-01 10:30:00'),
    (2, 'AJ-2502', 3, 'Downtown Mzuzu', '+265 999 654 321', '', 10200.00, 1500.00, 500, 11200.00, 'Delivered', 'Paid', 4, '2025-12-02 14:15:00'),
    (3, 'AJ-2503', 2, 'City Center, Plot 14', '+265 999 123 456', 'No onions', 21000.00, 2000.00, 0, 23000.00, 'Delivered', 'Paid', 4, '2025-12-03 11:45:00'),
    (4, 'AJ-2504', 3, 'Downtown Mzuzu', '+265 999 654 321', '', 15500.00, 1500.00, 1000, 16000.00, 'Delivered', 'Paid', 4, '2025-12-04 12:20:00'),
    (5, 'AJ-2505', 2, 'City Center, Plot 14', '+265 999 123 456', 'Rush delivery', 9500.00, 1500.00, 0, 11000.00, 'Out for Delivery', 'Paid', 4, '2025-12-05 09:00:00')
ON DUPLICATE KEY UPDATE order_status = VALUES(order_status);

-- Add order items
INSERT INTO order_items (order_id, meal_id, meal_name, quantity, unit_price, subtotal)
VALUES
    (1, 2, 'Peri-Peri Chicken', 2, 6500.00, 13000.00),
    (2, 1, 'Nsima & Beef Stew', 2, 3500.00, 7000.00),
    (2, 3, 'Veggie Power Bowl', 1, 5200.00, 5200.00),
    (3, 2, 'Peri-Peri Chicken', 2, 6500.00, 13000.00),
    (3, 6, 'Grilled Chambo', 1, 6800.00, 6800.00),
    (3, 8, 'Tamarind Glazed Wings', 1, 4500.00, 4500.00),
    (4, 1, 'Nsima & Beef Stew', 3, 3500.00, 10500.00),
    (4, 3, 'Veggie Power Bowl', 1, 5200.00, 5200.00),
    (5, 2, 'Peri-Peri Chicken', 1, 6500.00, 6500.00),
    (5, 8, 'Tamarind Glazed Wings', 1, 4500.00, 4500.00)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- ---------------------------------------------------------------------------
-- Views to help reporting (optional but handy)
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_order_summary AS
SELECT 
    o.order_id,
    o.order_number,
    o.customer_id,
    u.full_name AS customer_name,
    o.total_amount,
    o.order_status,
    o.order_date,
    COUNT(oi.order_item_id) AS item_count
FROM orders o
INNER JOIN users u ON o.customer_id = u.user_id
LEFT JOIN order_items oi ON o.order_id = oi.order_id
GROUP BY o.order_id;

SET FOREIGN_KEY_CHECKS = 1;

