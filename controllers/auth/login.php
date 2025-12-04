<?php
/**
 * Login Controller
 * Handles user authentication
 */

require_once '../../config/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Invalid request method');
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['username']) || empty($data['password'])) {
    jsonResponse(false, null, 'Username and password are required');
}

$username = sanitize($data['username']);
$password = $data['password'];

try {
    $db = getDB();
    
    // Find user by username or email
    $stmt = $db->prepare("
        SELECT u.*, r.role_name 
        FROM users u
        INNER JOIN roles r ON u.role_id = r.role_id
        WHERE (u.username = :username OR u.email = :email)
        AND u.is_active = 1
    ");
    
    $stmt->execute([
        'username' => $username,
        'email' => $username
    ]);
    $user = $stmt->fetch();
    
    // Check if user exists and password is correct
    if (!$user || !verifyPassword($password, $user['password_hash'])) {
        jsonResponse(false, null, 'Invalid username or password');
    }
    
    // Update last login
    $updateStmt = $db->prepare("
        UPDATE users 
        SET last_login = CURRENT_TIMESTAMP 
        WHERE user_id = :user_id
    ");
    $updateStmt->execute(['user_id' => $user['user_id']]);
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    
    // Remove sensitive data
    unset($user['password_hash']);
    
    // Return success with user data
    jsonResponse(true, $user, 'Login successful');
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Login failed: ' . $e->getMessage());
}
?>