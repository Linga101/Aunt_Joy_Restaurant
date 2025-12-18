<?php
$pageTitle = "Login - Aunt Joy's Restaurant";
$customCSS = "auth.css";
$customJS = "auth.js";
$showNav = false;
$showFooter = false;
$isModal = !empty($_GET['modal']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'index.php') !== false);
$bodyClass = "auth-page" . ($isModal ? " auth-modal-page" : "");

include '../templates/header.php';

// Redirect if already logged in (only if not in modal)
if (isLoggedIn() && !$isModal) {
    $role = getCurrentUserRole();
    $redirects = [
        'Customer' => '/aunt_joy/views/customer/menu.php',
        'Administrator' => '/aunt_joy/views/admin/dashboard.php',
        'Sales Personnel' => '/aunt_joy/views/sales/dashboard.php',
        'Manager' => '/aunt_joy/views/manager/dashboard.php'
    ];
    redirect($redirects[$role] ?? '/aunt_joy/index.php');
}
?>

<div class="auth-container">
    <!-- Background Shapes (matching welcome page) -->
    <div class="hero-sculpture">
        <div class="hero-orb orb-one"></div>
        <div class="hero-orb orb-two"></div>
        <div class="hero-wave"></div>
    </div>

    <!-- Back to Home -->
    <div class="back-home">
        <a href="/aunt_joy/index.php">
            <span>←</span>
            <span>Back to Home</span>
        </a>
    </div>

    <!-- Login Box -->
    <div class="auth-box">
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
                        placeholder="Enter your username or email"
                        required
                        autocomplete="username"
                    >
                    <span class="input-icon">👤</span>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <span class="input-icon password-toggle" onclick="togglePassword('password')">👁️</span>
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
            <button type="submit" class="btn btn-primary btn-block">
                <span id="loginBtnText">Sign In</span>
            </button>

            <!-- Divider -->
            <div class="divider">
                <span>Don't have an account?</span>
            </div>

            <!-- Sign Up Link -->
            <div class="signup-link">
                New customer? <a href="/aunt_joy/views/auth/register.php">Create an account</a>
            </div>
        </form>

    </div>
</div>

<?php include '../templates/footer.php'; ?>