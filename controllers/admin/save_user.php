<?php
/**
 * Save User Controller
 * Handles creating and updating users
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

if (!hasRole('Administrator')) {
    jsonResponse(false, null, 'Access denied - Administrator role required');
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['role_id', 'username', 'email', 'full_name'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        jsonResponse(false, null, ucfirst(str_replace('_', ' ', $field)) . ' is required');
    }
}

$userId = $data['user_id'] ?? null;
$roleId = intval($data['role_id']);
$username = sanitize($data['username']);
$email = sanitize($data['email']);
$fullName = sanitize($data['full_name']);
$phoneNumber = sanitize($data['phone_number'] ?? '');
$password = $data['password'] ?? null;
$isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;

// Validate email
if (!validateEmail($email)) {
    jsonResponse(false, null, 'Invalid email format');
}

// Validate role
if (!in_array($roleId, [1, 2, 3, 4])) {
    jsonResponse(false, null, 'Invalid role ID');
}

try {
    $db = getDB();
    
    if ($userId) {
        // Update existing user
        
        // Check for duplicate username/email (excluding current user)
        $checkStmt = $db->prepare("
            SELECT user_id FROM users 
            WHERE (username = :username OR email = :email) 
            AND user_id != :user_id
        ");
        $checkStmt->execute([
            'username' => $username,
            'email' => $email,
            'user_id' => $userId
        ]);
        
        if ($checkStmt->fetch()) {
            jsonResponse(false, null, 'Username or email already exists');
        }
        
        // Build update query
        if ($password) {
            $stmt = $db->prepare("
                UPDATE users SET
                    role_id = :role_id,
                    username = :username,
                    email = :email,
                    password_hash = :password_hash,
                    full_name = :full_name,
                    phone_number = :phone_number,
                    is_active = :is_active
                WHERE user_id = :user_id
            ");
            
            $params = [
                'user_id' => $userId,
                'role_id' => $roleId,
                'username' => $username,
                'email' => $email,
                'password_hash' => hashPassword($password),
                'full_name' => $fullName,
                'phone_number' => $phoneNumber,
                'is_active' => $isActive
            ];
        } else {
            $stmt = $db->prepare("
                UPDATE users SET
                    role_id = :role_id,
                    username = :username,
                    email = :email,
                    full_name = :full_name,
                    phone_number = :phone_number,
                    is_active = :is_active
                WHERE user_id = :user_id
            ");
            
            $params = [
                'user_id' => $userId,
                'role_id' => $roleId,
                'username' => $username,
                'email' => $email,
                'full_name' => $fullName,
                'phone_number' => $phoneNumber,
                'is_active' => $isActive
            ];
        }
        
        $stmt->execute($params);
        
        jsonResponse(true, ['user_id' => $userId], 'User updated successfully');
        
    } else {
        // Create new user
        
        // Password is required for new users
        if (!$password) {
            jsonResponse(false, null, 'Password is required for new users');
        }
        
        if (strlen($password) < 6) {
            jsonResponse(false, null, 'Password must be at least 6 characters');
        }
        
        // Check for duplicate username/email
        $checkStmt = $db->prepare("
            SELECT user_id FROM users 
            WHERE username = :username OR email = :email
        ");
        $checkStmt->execute([
            'username' => $username,
            'email' => $email
        ]);
        
        if ($checkStmt->fetch()) {
            jsonResponse(false, null, 'Username or email already exists');
        }
        
        $stmt = $db->prepare("
            INSERT INTO users (
                role_id, username, email, password_hash,
                full_name, phone_number, is_active
            ) VALUES (
                :role_id, :username, :email, :password_hash,
                :full_name, :phone_number, :is_active
            )
        ");
        
        $stmt->execute([
            'role_id' => $roleId,
            'username' => $username,
            'email' => $email,
            'password_hash' => hashPassword($password),
            'full_name' => $fullName,
            'phone_number' => $phoneNumber,
            'is_active' => $isActive
        ]);
        
        $newUserId = $db->lastInsertId();
        
        jsonResponse(true, ['user_id' => $newUserId], 'User created successfully');
    }
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to save user: ' . $e->getMessage());
}
?>