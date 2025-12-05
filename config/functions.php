<?php
/**
 * Business Logic Functions
 * Centralized functions for users, meals, orders, and other business operations
 * These functions encapsulate database operations and validation logic
 */

// =========================================================================
// USER FUNCTIONS
// =========================================================================

/**
 * Add a new user to the database
 * @param int $roleId User role ID (1=Customer, 2=Admin, 3=Sales, 4=Manager)
 * @param string $username Unique username
 * @param string $email Unique email address
 * @param string $password Plain text password (will be hashed)
 * @param string $fullName Full name of user
 * @param string $phoneNumber Optional phone number
 * @param bool $isActive User active status (default: true)
 * @return array ['success' => bool, 'user_id' => int|null, 'message' => string]
 */
function addUser($roleId, $username, $email, $password, $fullName, $phoneNumber = '', $isActive = true) {
    try {
        $db = getDB();
        
        // Validate inputs
        if (!validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        // Check for duplicate username or email
        $checkStmt = $db->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Insert new user
        $stmt = $db->prepare("
            INSERT INTO users (role_id, username, email, password_hash, full_name, phone_number, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $roleId,
            $username,
            $email,
            hashPassword($password),
            $fullName,
            $phoneNumber,
            $isActive ? 1 : 0
        ]);
        
        return [
            'success' => true,
            'user_id' => $db->lastInsertId(),
            'message' => 'User created successfully'
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Update existing user
 * @param int $userId User ID to update
 * @param int $roleId Role ID
 * @param string $username Username
 * @param string $email Email
 * @param string $fullName Full name
 * @param string $phoneNumber Phone number
 * @param string|null $password New password (optional)
 * @param bool $isActive Active status
 * @return array ['success' => bool, 'message' => string]
 */
function updateUser($userId, $roleId, $username, $email, $fullName, $phoneNumber = '', $password = null, $isActive = true) {
    try {
        $db = getDB();
        
        // Validate email
        if (!validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check for duplicate username/email (excluding current user)
        $checkStmt = $db->prepare("
            SELECT user_id FROM users 
            WHERE (username = ? OR email = ?) AND user_id != ?
        ");
        $checkStmt->execute([$username, $email, $userId]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Build update query
        if ($password) {
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters'];
            }
            
            $stmt = $db->prepare("
                UPDATE users SET
                    role_id = ?, username = ?, email = ?, password_hash = ?,
                    full_name = ?, phone_number = ?, is_active = ?
                WHERE user_id = ?
            ");
            
            $stmt->execute([
                $roleId, $username, $email, hashPassword($password),
                $fullName, $phoneNumber, $isActive ? 1 : 0, $userId
            ]);
        } else {
            $stmt = $db->prepare("
                UPDATE users SET
                    role_id = ?, username = ?, email = ?,
                    full_name = ?, phone_number = ?, is_active = ?
                WHERE user_id = ?
            ");
            
            $stmt->execute([
                $roleId, $username, $email,
                $fullName, $phoneNumber, $isActive ? 1 : 0, $userId
            ]);
        }
        
        return ['success' => true, 'message' => 'User updated successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Delete user by ID
 * @param int $userId User ID to delete
 * @return array ['success' => bool, 'message' => string]
 */
function deleteUser($userId) {
    try {
        $db = getDB();
        
        // Prevent deletion of admin account (user_id = 1)
        if ($userId == 1) {
            return ['success' => false, 'message' => 'Cannot delete system administrator'];
        }
        
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        return ['success' => true, 'message' => 'User deleted successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get user by ID
 * @param int $userId User ID
 * @return array|false User data or false if not found
 */
function getUserById($userId) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                u.user_id, u.role_id, u.username, u.email, u.full_name,
                u.phone_number, u.is_active, u.created_at, u.last_login,
                r.role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = ?
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get all users with optional filters
 * @param int|null $roleId Filter by role ID
 * @param bool|null $isActive Filter by active status
 * @return array Array of user records
 */
function getAllUsers($roleId = null, $isActive = null) {
    try {
        $db = getDB();
        
        $query = "
            SELECT 
                u.user_id, u.role_id, u.username, u.email, u.full_name,
                u.phone_number, u.is_active, u.created_at, u.last_login,
                r.role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($roleId !== null) {
            $query .= " AND u.role_id = ?";
            $params[] = $roleId;
        }
        
        if ($isActive !== null) {
            $query .= " AND u.is_active = ?";
            $params[] = $isActive ? 1 : 0;
        }
        
        $query .= " ORDER BY u.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// =========================================================================
// MEAL FUNCTIONS
// =========================================================================

/**
 * Add a new meal to the menu
 * @param int $categoryId Category ID
 * @param string $mealName Meal name
 * @param string $mealDescription Description
 * @param float $price Price
 * @param string $imageUrl Image URL path
 * @param int $preparationTime Prep time in minutes
 * @param bool $isAvailable Available status
 * @param bool $isFeatured Featured status
 * @param int $userId User creating the meal
 * @return array ['success' => bool, 'meal_id' => int|null, 'message' => string]
 */
function addMeal($categoryId, $mealName, $mealDescription, $price, $imageUrl, $preparationTime = 20, $isAvailable = true, $isFeatured = false, $userId = null) {
    try {
        $db = getDB();
        
        // Validate inputs
        if (empty($mealName) || empty($mealDescription) || $price <= 0) {
            return ['success' => false, 'message' => 'Invalid meal data'];
        }
        
        // Check if category exists
        $catStmt = $db->prepare("SELECT category_id FROM categories WHERE category_id = ?");
        $catStmt->execute([$categoryId]);
        if (!$catStmt->fetch()) {
            return ['success' => false, 'message' => 'Invalid category'];
        }
        
        // Insert meal
        $stmt = $db->prepare("
            INSERT INTO meals (
                category_id, meal_name, meal_description, price, image_url,
                preparation_time, is_available, is_featured, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $categoryId, $mealName, $mealDescription, $price, $imageUrl,
            $preparationTime, $isAvailable ? 1 : 0, $isFeatured ? 1 : 0, $userId
        ]);
        
        return [
            'success' => true,
            'meal_id' => $db->lastInsertId(),
            'message' => 'Meal created successfully'
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Update existing meal
 * @param int $mealId Meal ID to update
 * @param int $categoryId Category ID
 * @param string $mealName Meal name
 * @param string $mealDescription Description
 * @param float $price Price
 * @param string $imageUrl Image URL
 * @param int $preparationTime Prep time
 * @param bool $isAvailable Available status
 * @param bool $isFeatured Featured status
 * @return array ['success' => bool, 'message' => string]
 */
function updateMeal($mealId, $categoryId, $mealName, $mealDescription, $price, $imageUrl, $preparationTime = 20, $isAvailable = true, $isFeatured = false) {
    try {
        $db = getDB();
        
        if ($price <= 0) {
            return ['success' => false, 'message' => 'Price must be greater than zero'];
        }
        
        $stmt = $db->prepare("
            UPDATE meals SET
                category_id = ?, meal_name = ?, meal_description = ?,
                price = ?, image_url = ?, preparation_time = ?,
                is_available = ?, is_featured = ?
            WHERE meal_id = ?
        ");
        
        $stmt->execute([
            $categoryId, $mealName, $mealDescription, $price, $imageUrl,
            $preparationTime, $isAvailable ? 1 : 0, $isFeatured ? 1 : 0, $mealId
        ]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Meal not found'];
        }
        
        return ['success' => true, 'message' => 'Meal updated successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Delete meal by ID
 * @param int $mealId Meal ID to delete
 * @return array ['success' => bool, 'message' => string]
 */
function deleteMeal($mealId) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("DELETE FROM meals WHERE meal_id = ?");
        $stmt->execute([$mealId]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Meal not found'];
        }
        
        return ['success' => true, 'message' => 'Meal deleted successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get meal by ID
 * @param int $mealId Meal ID
 * @return array|false Meal data or false
 */
function getMealById($mealId) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                m.meal_id, m.category_id, m.meal_name, m.meal_description,
                m.price, m.image_url, m.is_available, m.is_featured,
                m.preparation_time, m.created_at, m.updated_at,
                c.category_name
            FROM meals m
            INNER JOIN categories c ON m.category_id = c.category_id
            WHERE m.meal_id = ?
        ");
        
        $stmt->execute([$mealId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get all meals with optional filters
 * @param int|null $categoryId Filter by category
 * @param bool|null $isAvailable Filter by availability
 * @param bool|null $isFeatured Filter by featured status
 * @return array Array of meals
 */
function getAllMeals($categoryId = null, $isAvailable = null, $isFeatured = null) {
    try {
        $db = getDB();
        
        $query = "
            SELECT 
                m.meal_id, m.category_id, m.meal_name, m.meal_description,
                m.price, m.image_url, m.is_available, m.is_featured,
                m.preparation_time, m.created_at, m.updated_at,
                c.category_name
            FROM meals m
            INNER JOIN categories c ON m.category_id = c.category_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($categoryId !== null) {
            $query .= " AND m.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($isAvailable !== null) {
            $query .= " AND m.is_available = ?";
            $params[] = $isAvailable ? 1 : 0;
        }
        
        if ($isFeatured !== null) {
            $query .= " AND m.is_featured = ?";
            $params[] = $isFeatured ? 1 : 0;
        }
        
        $query .= " ORDER BY m.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// =========================================================================
// ORDER FUNCTIONS
// =========================================================================

/**
 * Place a new order
 * @param int $customerId Customer user ID
 * @param string $deliveryAddress Delivery address
 * @param string $contactNumber Contact phone number
 * @param array $items Array of order items with meal_id, quantity, unit_price
 * @param float $subtotal Subtotal amount
 * @param float $deliveryFee Delivery fee
 * @param float $discountAmount Discount applied
 * @param float $totalAmount Total amount
 * @param string $specialInstructions Special instructions
 * @return array ['success' => bool, 'order_id' => int|null, 'order_number' => string|null, 'message' => string]
 */
function placeOrder($customerId, $deliveryAddress, $contactNumber, $items, $subtotal, $deliveryFee, $discountAmount, $totalAmount, $specialInstructions = '') {
    try {
        $db = getDB();
        
        // Validate inputs
        if (empty($items) || !is_array($items)) {
            return ['success' => false, 'message' => 'Order items are required'];
        }
        
        if ($totalAmount <= 0) {
            return ['success' => false, 'message' => 'Invalid order total'];
        }
        
        $db->beginTransaction();
        
        // Generate order number
        $orderNumber = generateOrderNumber();
        
        // Insert order
        $orderStmt = $db->prepare("
            INSERT INTO orders (
                order_number, customer_id, delivery_address, contact_number,
                special_instructions, subtotal, delivery_fee, discount_amount, total_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $orderStmt->execute([
            $orderNumber, $customerId, $deliveryAddress, $contactNumber,
            $specialInstructions, $subtotal, $deliveryFee, $discountAmount, $totalAmount
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Insert order items
        $itemStmt = $db->prepare("
            INSERT INTO order_items (
                order_id, meal_id, meal_name, quantity, unit_price, subtotal
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($items as $item) {
            $itemStmt->execute([
                $orderId,
                $item['meal_id'],
                $item['meal_name'],
                $item['quantity'],
                $item['unit_price'],
                $item['subtotal']
            ]);
        }
        
        // Log order status
        $historyStmt = $db->prepare("
            INSERT INTO order_status_history (order_id, new_status, changed_by, notes)
            VALUES (?, ?, ?, ?)
        ");
        
        $historyStmt->execute([$orderId, 'Pending', $customerId, 'Order placed by customer']);
        
        $db->commit();
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'message' => 'Order placed successfully'
        ];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get order by ID with items
 * @param int $orderId Order ID
 * @return array|false Order data with items or false
 */
function getOrderById($orderId) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                o.order_id, o.order_number, o.customer_id, o.delivery_address,
                o.contact_number, o.special_instructions, o.subtotal,
                o.delivery_fee, o.discount_amount, o.total_amount,
                o.created_at, o.updated_at,
                u.full_name, u.email
            FROM orders o
            INNER JOIN users u ON o.customer_id = u.user_id
            WHERE o.order_id = ?
        ");
        
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            return false;
        }
        
        // Get order items
        $itemStmt = $db->prepare("
            SELECT order_item_id, meal_id, meal_name, quantity, unit_price, subtotal
            FROM order_items
            WHERE order_id = ?
        ");
        
        $itemStmt->execute([$orderId]);
        $order['items'] = $itemStmt->fetchAll();
        
        return $order;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get all orders for a customer
 * @param int $customerId Customer user ID
 * @param string|null $status Filter by status
 * @return array Array of orders
 */
function getCustomerOrders($customerId, $status = null) {
    try {
        $db = getDB();
        
        $query = "
            SELECT 
                o.order_id, o.order_number, o.delivery_address,
                o.subtotal, o.delivery_fee, o.total_amount,
                o.created_at, o.updated_at
            FROM orders o
            WHERE o.customer_id = ?
        ";
        
        $params = [$customerId];
        
        if ($status) {
            $query .= " AND o.order_status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY o.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Update order status
 * @param int $orderId Order ID
 * @param string $newStatus New status
 * @param int|null $changedBy User ID making the change
 * @param string $notes Status change notes
 * @return array ['success' => bool, 'message' => string]
 */
function updateOrderStatus($orderId, $newStatus, $changedBy = null, $notes = '') {
    try {
        $db = getDB();
        
        // Validate status
        $validStatuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Out for Delivery', 'Delivered', 'Cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        // Log status change
        $historyStmt = $db->prepare("
            INSERT INTO order_status_history (order_id, new_status, changed_by, notes)
            VALUES (?, ?, ?, ?)
        ");
        
        $historyStmt->execute([$orderId, $newStatus, $changedBy, $notes]);
        
        return ['success' => true, 'message' => 'Order status updated successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get all orders with optional filters
 * @param string|null $status Filter by status
 * @param int|null $limit Limit number of results
 * @param int|null $offset Offset for pagination
 * @return array Array of orders
 */
function getAllOrders($status = null, $limit = null, $offset = null) {
    try {
        $db = getDB();
        
        $query = "
            SELECT 
                o.order_id, o.order_number, o.customer_id, o.delivery_address,
                o.subtotal, o.delivery_fee, o.total_amount, o.created_at,
                u.full_name, u.phone_number
            FROM orders o
            INNER JOIN users u ON o.customer_id = u.user_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($status) {
            $query .= " AND o.order_status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY o.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT ?";
            $params[] = $limit;
        }
        
        if ($offset) {
            $query .= " OFFSET ?";
            $params[] = $offset;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// =========================================================================
// CATEGORY FUNCTIONS
// =========================================================================

/**
 * Get all active categories
 * @return array Array of categories
 */
function getAllCategories() {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                category_id, category_name, description, display_order, is_active
            FROM categories
            WHERE is_active = 1
            ORDER BY display_order ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get category by ID
 * @param int $categoryId Category ID
 * @return array|false Category data or false
 */
function getCategoryById($categoryId) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT category_id, category_name, description, display_order, is_active
            FROM categories
            WHERE category_id = ?
        ");
        
        $stmt->execute([$categoryId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

// =========================================================================
// REPORTING/ANALYTICS FUNCTIONS
// =========================================================================

/**
 * Get sales report for date range
 * @param string $startDate Start date (YYYY-MM-DD)
 * @param string $endDate End date (YYYY-MM-DD)
 * @return array Sales data
 */
function getSalesReport($startDate, $endDate) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                DATE(o.created_at) as order_date,
                COUNT(o.order_id) as total_orders,
                SUM(o.total_amount) as total_revenue,
                AVG(o.total_amount) as avg_order_value,
                SUM(o.subtotal) as subtotal,
                SUM(o.delivery_fee) as total_delivery_fees
            FROM orders o
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY DATE(o.created_at)
            ORDER BY o.created_at DESC
        ");
        
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get top selling meals
 * @param int $limit Number of top meals to return
 * @return array Array of top meals with quantities
 */
function getTopSellingMeals($limit = 10) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                oi.meal_name,
                oi.meal_id,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.subtotal) as total_revenue,
                COUNT(DISTINCT oi.order_id) as order_count
            FROM order_items oi
            GROUP BY oi.meal_id, oi.meal_name
            ORDER BY total_quantity DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get order statistics
 * @return array Statistics data
 */
function getOrderStatistics() {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                COUNT(DISTINCT customer_id) as unique_customers
            FROM orders
        ");
        
        $stmt->execute();
        return $stmt->fetch();
    } catch (Exception $e) {
        return [];
    }
}

?>
