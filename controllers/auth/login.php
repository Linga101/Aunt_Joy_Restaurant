<?php
/**
 * Login Controller
 * Handles user authentication
 */

require_once '../../config/db.php';

initSession();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Invalid request method');
}

// Get input data from form data
$email = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate required fields
if (empty($email) || empty($password)) {
    jsonResponse(false, null, 'Email and password are required');
}

try {
    $db = getDB();
    
    // Find user by username or email
    $stmt = $db->prepare("
        SELECT u.*, r.role_name 
        FROM users u
        INNER JOIN roles r ON u.role_id = r.role_id
        WHERE (u.username = ? OR u.email = ?)
        AND u.is_active = 1
    ");
    
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch();
    
    // Check if user exists and password is correct
    if (!$user || !verifyPassword($password, $user['password_hash'])) {
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
    
    // Determine redirect based on role
    $redirects = [
        'Customer' => '/Aunt_Joy_Restaurant/views/customer/menu.php',
        'Administrator' => '/Aunt_Joy_Restaurant/views/admin/dashboard.php',
        'Sales Personnel' => '/Aunt_Joy_Restaurant/views/sales/dashboard.php',
        'Manager' => '/Aunt_Joy_Restaurant/views/manager/dashboard.php'
    ];
    $redirect = $redirects[$user['role_name']] ?? '/Aunt_Joy_Restaurant/index.php';
    
    // Return success with user data and redirect
    jsonResponse(true, array_merge($user, ['redirect' => $redirect]), 'Login successful');
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Login failed: ' . $e->getMessage());
}
?>