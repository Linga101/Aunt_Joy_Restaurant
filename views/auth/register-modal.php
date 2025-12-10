<?php
/**
 * Register Modal Content (No header/footer)
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
                placeholder="Full name"
                required
                autocomplete="name"
            >
        </div>
    </div>

    <!-- Username -->
    <div class="form-group">
        <div class="input-wrapper">
            <input 
                type="text" 
                id="username" 
                name="username" 
                placeholder="Username"
                required
                autocomplete="username"
                minlength="3"
            >
        </div>
    </div>

    <!-- Email -->
    <div class="form-group">
        <div class="input-wrapper">
            <input 
                type="email" 
                id="email" 
                name="email" 
                placeholder="Email"
                required
                autocomplete="email"
            >
        </div>
    </div>

    <!-- Phone Number -->
    <div class="form-group">
        <div class="input-wrapper">
            <input 
                type="tel" 
                id="phone_number" 
                name="phone_number" 
                placeholder="Phone Number"
                autocomplete="tel"
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
                autocomplete="new-password"
                minlength="6"
            >
        </div>
    </div>

    <!-- Confirm Password -->
    <div class="form-group">
        <div class="input-wrapper">
            <input 
                type="password" 
                id="confirm_password" 
                name="confirm_password" 
                placeholder="Confirm Password"
                required
                autocomplete="new-password"
            >
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
    <button type="submit" class="btn btn-block">
        <span id="registerBtnText">Create Account</span>
    </button>

    <!-- Divider -->
    <div class="divider">
        <span>Already have an account?</span>
    </div>

    <!-- Login Link -->
    <div class="signup-link">
        <a href="#" onclick="switchAuthModal('login'); return false;">Sign in instead</a>
    </div>
</form>
