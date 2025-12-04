<?php
/**
 * Get Users Controller
 * Fetches all users with role information
 */

require_once '../../config/db.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, null, 'Invalid request method');
}

// Check authentication and authorization
if (!isLoggedIn()) {
    jsonResponse(false, null, 'Unauthorized - Please log in');
}

if (!hasRole('Administrator')) {
    jsonResponse(false, null, 'Access denied - Administrator role required');
}

try {
    $db = getDB();
    
    $userId = $_GET['user_id'] ?? null;
    $roleId = $_GET['role_id'] ?? null;
    
    if ($userId) {
        // Get single user
        $stmt = $db->prepare("
            SELECT 
                u.user_id,
                u.role_id,
                u.username,
                u.email,
                u.full_name,
                u.phone_number,
                u.is_active,
                u.created_at,
                u.last_login,
                r.role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = :user_id
        ");
        
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(false, null, 'User not found');
        }
        
        jsonResponse(true, $user, 'User retrieved successfully');
        
    } else {
        // Get all users with optional role filter
        $query = "
            SELECT 
                u.user_id,
                u.role_id,
                u.username,
                u.email,
                u.full_name,
                u.phone_number,
                u.is_active,
                u.created_at,
                u.last_login,
                r.role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
        ";
        
        $params = [];
        
        if ($roleId) {
            $query .= " WHERE u.role_id = :role_id";
            $params['role_id'] = $roleId;
        }
        
        $query .= " ORDER BY u.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        jsonResponse(true, $users, 'Users retrieved successfully');
    }
    
} catch (PDOException $e) {
    jsonResponse(false, null, 'Failed to fetch users: ' . $e->getMessage());
}
?>