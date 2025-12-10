<?php
$pageTitle = "Register - Aunt Joy's Restaurant";
$customCSS = "auth.css";
$customJS = "auth.js";
$showNav = false;
$showFooter = false;
$isModal = !empty($_GET['modal']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'index.php') !== false);
$bodyClass = "auth-page" . ($isModal ? " auth-modal-page" : "");

include '../templates/header.php';

// Redirect if already logged in (only if not in modal)
if (isLoggedIn() && !$isModal) {
    redirect('/aunt_joy/views/customer/menu.php');
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

    <!-- Register Box -->
    <div class="auth-box">
        <!-- Logo -->
        <div class="auth-logo">
            <div class="logo-icon">🍽️</div>
            <h1>Create Account</h1>
            <p>Join Aunt Joy's Restaurant today</p>
        </div>

        <!-- Alert Container -->
        <div id="alertContainer"></div>

        <!-- Registration Form -->
        <form id="registerForm" class="auth-form">
            <!-- Full Name -->
            <div class="form-group">
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        placeholder="Enter your full name"
                        required
                        autocomplete="name"
                    >
                    <span class="input-icon">👤</span>
                </div>
            </div>

            <!-- Username -->
            <div class="form-group">
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Choose a username"
                        required
                        autocomplete="username"
                        minlength="3"
                    >
                    <span class="input-icon">@</span>
                </div>
                <small class="form-hint">At least 3 characters</small>
            </div>

            <!-- Email -->
            <div class="form-group">
                <div class="input-wrapper">
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your email"
                        required
                        autocomplete="email"
                    >
                    <span class="input-icon">✉️</span>
                </div>
            </div>

            <!-- Phone Number -->
            <div class="form-group">
                <div class="input-wrapper">
                    <input 
                        type="tel" 
                        id="phone_number" 
                        name="phone_number" 
                        placeholder="+265 999 123 456"
                        autocomplete="tel"
                    >
                    <span class="input-icon">📞</span>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Create a password"
                        required
                        autocomplete="new-password"
                        minlength="6"
                    >
                    <span class="input-icon password-toggle" onclick="togglePassword('password')">👁️</span>
                </div>
                <small class="form-hint">At least 6 characters</small>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Confirm your password"
                        required
                        autocomplete="new-password"
                    >
                    <span class="input-icon password-toggle" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
            </div>

            <!-- Terms & Conditions -->
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" required>
                    <span>I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a></span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary btn-block">
                <span id="registerBtnText">Create Account</span>
            </button>

            <!-- Divider -->
            <div class="divider">
                <span>Already have an account?</span>
            </div>

            <!-- Login Link -->
            <div class="signup-link">
                <a href="/aunt_joy/views/auth/login.php">Sign in instead</a>
            </div>
        </form>
    </div>
</div>

<?php include '../templates/footer.php'; ?>