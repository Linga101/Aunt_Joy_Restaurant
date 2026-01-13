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

// Log login attempt for security monitoring
logSecurity("Login attempt initiated", [
    'email' => $data['email'] ?? 'not_provided',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

// Validate required fields
if (empty($data['email']) || empty($data['password'])) {
    jsonResponse(false, null, 'Email and password are required');
}

$email = sanitize($data['email']);
$password = $data['password'];

try {
    $db = getDB();
    
    // Find user by email
    $stmt = $db->prepare("
        SELECT u.*, r.role_name 
        FROM users u
        INNER JOIN roles r ON u.role_id = r.role_id
        WHERE u.email = :email
        AND u.is_active = 1
    ");
    
    $stmt->execute([
        'email' => $email
    ]);
    $user = $stmt->fetch();
    
    // Check if user exists and password is correct
if (!$user || !verifyPassword($password, $user['password_hash'])) {
    logSecurity("Login attempt failed", [
        'email' => $email,
        'reason' => 'Invalid credentials',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    jsonResponse(false, null, 'Invalid email or password');
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
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    
    // Remove sensitive data
    unset($user['password_hash']);
    
    // Log successful login
    logSecurity("User logged in successfully", [
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'role' => $user['role_name'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Return success with user data
    jsonResponse(true, $user, 'Login successful');
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Login failed: ' . $e->getMessage());
}
?>