<?php
/**
 * Login Modal Content (No header/footer)
 */
require_once __DIR__ . '/../../config/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    echo json_encode(['redirect' => '/aunt_joy/index.php']);
    exit;
}
?>

<!-- Logo -->
<div class="auth-logo">
    <div class="logo-icon">🍽️</div>
    <h1>Welcome Back</h1>
    <p>Sign in to continue to Aunt Joy's</p>
</div>

<!-- Display logout message if exists -->
<?php if (isset($_SESSION['logout_message'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['logout_message'];
        unset($_SESSION['logout_message']);
        ?>
    </div>
<?php endif; ?>

<!-- Alert Container -->
<div id="alertContainer"></div>

<!-- Login Form -->
<form id="loginForm" class="auth-form">
    <!-- Username -->
    <div class="form-group">
        <div class="input-wrapper">
            <input 
                type="text" 
                id="username" 
                name="username" 
                placeholder="Username or Email"
                required
                autocomplete="username"
            >
        </div>
    </div>

    <!-- Password -->
    <div class="form-group">
        <div class="input-wrapper">
            <input 
                type="password" 
                id="password" 
                name="password" 
                placeholder="Password"
                required
                autocomplete="current-password"
            >
        </div>
    </div>

    <!-- Remember Me & Forgot Password -->
    <div class="form-options">
        <label class="remember-me">
            <input type="checkbox" name="remember">
            <span>Remember me</span>
        </label>
        <a href="#" class="forgot-password">Forgot Password?</a>
    </div>

    <!-- Submit Button -->
    <button type="submit" class="btn btn-block">
        <span id="loginBtnText">Sign In</span>
    </button>

    <!-- Divider -->
    <div class="divider">
        <span>Don't have an account?</span>
    </div>

    <!-- Sign Up Link -->
    <div class="signup-link">
        New customer? <a href="#" onclick="switchAuthModal('register'); return false;">Create an account</a>
    </div>
</form>
