<?php
/**
 * Logout Controller
 * Handles user session termination
 */

require_once '../../config/db.php';

// Destroy all session data
session_unset();
session_destroy();

// Start new session for flash message
session_start();
$_SESSION['logout_message'] = 'You have been successfully logged out';

// Redirect to login page
redirect('/aunt_joy/views/auth/login.php');
?>