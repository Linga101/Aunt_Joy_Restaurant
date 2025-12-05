<?php
/**
 * Database Configuration
 * Central database connection and helper functions
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'aunt_joys_restaurant');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get database connection
 * @return PDO Database connection object
 */
function getDB() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]));
        }
    }
    
    return $conn;
}

/**
 * Start session if not already started
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    initSession();
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 * @param string ...$roles Allowed roles (variadic)
 * @return bool
 */
function hasRole(...$roles) {
    initSession();
    if (empty($roles)) {
        return false;
    }
    return isset($_SESSION['role_name']) && in_array($_SESSION['role_name'], $roles);
}

/**
 * Require authentication
 * Redirects to login if not authenticated
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /aunt_joy/views/auth/login.php');
        exit();
    }
}

/**
 * Require specific role
 * @param string ...$roles Allowed roles
 */
function requireRole(...$roles) {
    requireAuth();
    
    if (!in_array($_SESSION['role_name'], $roles)) {
        die(json_encode([
            'success' => false,
            'message' => 'Access denied. Insufficient permissions.'
        ]));
    }
}

/**
 * Redirect to URL
 * @param string $url Target URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Send JSON response
 * @param bool $success Success status
 * @param mixed $data Response data
 * @param string $message Response message
 */
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

/**
 * Sanitize input data
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Validate email format
 * @param string $email Email address
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    initSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRole() {
    initSession();
    return $_SESSION['role_name'] ?? null;
}

/**
 * Format currency
 * @param float $amount Amount
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    return 'MK ' . number_format($amount, 2);
}

/**
 * Generate unique order number
 * @return string
 */
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Initialize session
initSession();

// Include business logic functions
require_once __DIR__ . '/functions.php';
?>