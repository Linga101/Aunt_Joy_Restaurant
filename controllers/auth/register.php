<?php
/**
 * Registration Controller
 * Handles new customer registration
 */

require_once '../../config/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Invalid request method');
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['username', 'email', 'password', 'full_name'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        jsonResponse(false, null, ucfirst($field) . ' is required');
    }
}

// Sanitize inputs
$username = sanitize($data['username']);
$email = sanitize($data['email']);
$full_name = sanitize($data['full_name']);
$phone = sanitize($data['phone_number'] ?? '');
$password = $data['password'];

// Validate email format
if (!validateEmail($email)) {
    jsonResponse(false, null, 'Invalid email format');
}

// Validate password strength (minimum 6 characters)
if (strlen($password) < 6) {
    jsonResponse(false, null, 'Password must be at least 6 characters long');
}

try {
    $db = getDB();
    
    // Check if username already exists
    $checkStmt = $db->prepare("SELECT user_id FROM users WHERE username = :username");
    $checkStmt->execute(['username' => $username]);
    if ($checkStmt->fetch()) {
        jsonResponse(false, null, 'Username already exists');
    }
    
    // Check if email already exists
    $checkStmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
    $checkStmt->execute(['email' => $email]);
    if ($checkStmt->fetch()) {
        jsonResponse(false, null, 'Email already registered');
    }
    
    // Hash password
    $passwordHash = hashPassword($password);
    
    // Insert new user (role_id = 1 for Customer)
    $stmt = $db->prepare("
        INSERT INTO users (role_id, username, email, password_hash, full_name, phone_number)
        VALUES (1, :username, :email, :password_hash, :full_name, :phone_number)
    ");
    
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
        'full_name' => $full_name,
        'phone_number' => $phone
    ]);
    
    $userId = $db->lastInsertId();
    
    // Auto-login after registration
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['role_id'] = 1;
    $_SESSION['role_name'] = 'Customer';
    
    jsonResponse(true, [
        'user_id' => $userId,
        'username' => $username,
        'email' => $email,
        'full_name' => $full_name
    ], 'Registration successful');
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Registration failed: ' . $e->getMessage());
}
?>