<?php
require_once __DIR__ . '/config/db.php';

$pageTitle = "Welcome to Aunt Joy's Restaurant";
$customCSS = null; // Base stylesheet already loaded in header
$showNav = true;
$showFooter = true;
$bodyClass = 'landing-page';

try {
    $db = getDB();

    $featuredMealsStmt = $db->prepare("
        SELECT 
            m.meal_id,
            m.meal_name,
            m.price,
            m.image_url,
            c.category_name
        FROM meals m
        INNER JOIN categories c ON c.category_id = m.category_id
        WHERE m.is_available = 1 AND m.is_featured = 1
        ORDER BY m.updated_at DESC
        LIMIT 4
    ");
    $featuredMealsStmt->execute();
    $featuredMeals = $featuredMealsStmt->fetchAll();

    $categoryHighlightsStmt = $db->prepare("
        SELECT 
            c.category_id,
            c.category_name,
            c.description,
            COUNT(m.meal_id) AS meal_count
        FROM categories c
        LEFT JOIN meals m ON m.category_id = c.category_id AND m.is_available = 1
        WHERE c.is_active = 1
        GROUP BY c.category_id, c.category_name, c.description, c.display_order
        ORDER BY c.display_order ASC
        LIMIT 4
    ");
    $categoryHighlightsStmt->execute();
    $categoryHighlights = $categoryHighlightsStmt->fetchAll();
} catch (PDOException $exception) {
    $featuredMeals = [];
    $categoryHighlights = [];
}

include __DIR__ . '/views/templates/header.php';
?>

<header class="hero-section" id="hero">
    <div class="hero-content">
        <p class="eyebrow with-icon">Taste the Joy</p>
        <h1>Mzuzu's favorite meals, plated with creative flair.</h1>
        <p class="lead">
            Order freshly prepared local and international dishes, track your orders in real-time,
            and manage your restaurant operations from one smart dashboard.
        </p>
        <div class="cta-buttons">
            <a href="/aunt_joy/views/customer/menu.php" class="btn btn-primary">Order Now</a>
        </div>
        <div class="hero-highlight">
            <span>🍲 120+ menu items</span>
            <span>⏱️ 30 min avg. delivery</span>
            <span>⭐ 4.8 customer rating</span>
        </div>
    </div>
    <div class="hero-visual">
        <div class="hero-sculpture">
            <div class="hero-orb orb-one"></div>
            <div class="hero-orb orb-two"></div>
            <div class="hero-wave"></div>
        </div>
        <div class="hero-card">
            <p class="hero-card-label">Today's special</p>
            <h3>Spicy Peri-Peri Chicken</h3>
            <p>Served with seasoned fries & salad</p>
            <strong>MK 6,500</strong>
        </div>
        <div class="hero-card secondary">
            <p class="hero-card-label">Delivery tracker</p>
            <h3>Order #AJ-2548</h3>
            <p class="status">Out for delivery</p>
            <p class="eta">ETA: 12 minutes</p>
        </div>
    </div>
</header>

<section class="menu-preview" id="menu">
    <div class="section-header">
        <p class="eyebrow">Menu Preview</p>
        <h2>Popular picks to crave right now</h2>
    </div>

    <?php if (!empty($featuredMeals)): ?>
    <div class="menu-slider" data-autoplay="true">
        <button class="slider-btn prev" type="button" aria-label="Previous dishes" data-direction="prev">
            ←
        </button>
        <div class="menu-slider-window">
            <div class="menu-slider-track">
                <?php foreach ($featuredMeals as $meal): ?>
                    <article class="menu-preview-card">
                        <div class="preview-media">
                            <?php if (!empty($meal['image_url']) && strpos($meal['image_url'], '/') !== false): ?>
                                <img src="/aunt_joy/<?php echo $meal['image_url']; ?>" alt="<?php echo htmlspecialchars($meal['meal_name']); ?>">
                            <?php else: ?>
                                <span class="preview-emoji">🍽️</span>
                            <?php endif; ?>
                        </div>
                        <div class="preview-body">
                            <span class="preview-category"><?php echo htmlspecialchars($meal['category_name']); ?></span>
                            <h3><?php echo htmlspecialchars($meal['meal_name']); ?></h3>
                            <p><?php echo formatCurrency($meal['price']); ?></p>
                            <a href="/aunt_joy/views/customer/menu.php" class="preview-link">View meal →</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <button class="slider-btn next" type="button" aria-label="Next dishes" data-direction="next">
            →
        </button>
    </div>
    <?php else: ?>
        <div class="menu-preview-grid">
            <article class="menu-preview-card placeholder">
                <div class="preview-body">
                    <h3>Menu loading...</h3>
                    <p>Run the database seed script to view highlighted dishes.</p>
                </div>
            </article>
        </div>
    <?php endif; ?>

    <div class="menu-category-pills">
        <?php if (!empty($categoryHighlights)): ?>
            <?php foreach ($categoryHighlights as $category): ?>
                <div class="category-pill">
                    <span class="category-name"><?php echo htmlspecialchars($category['category_name']); ?></span>
                    <span class="category-count"><?php echo (int)$category['meal_count']; ?> meals</span>
                    <p><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="category-pill placeholder">
                <span class="category-name">Categories</span>
                <p>Activate categories in the dashboard to showcase them here.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="features-section">
    <div class="section-header">
        <p class="eyebrow">Why Aunt Joy?</p>
        <h2>Everything you need to run a modern restaurant</h2>
    </div>
    <div class="features-grid">
        <article class="feature-card">
            <span class="feature-icon">📱</span>
            <h3>Easy Ordering</h3>
            <p>Customers browse the menu, add meals to cart, and pay in minutes.</p>
        </article>
        <article class="feature-card">
            <span class="feature-icon">📊</span>
            <h3>Smart Dashboards</h3>
            <p>Admins manage menus, sales track deliveries, and managers get instant reports.</p>
        </article>
        <article class="feature-card">
            <span class="feature-icon">🚚</span>
            <h3>Live Order Tracking</h3>
            <p>Real-time SMS/email updates keep customers informed at every step.</p>
        </article>
        <article class="feature-card">
            <span class="feature-icon">🛡️</span>
            <h3>Secure Accounts</h3>
            <p>Role-based access with audit logs for every critical operation.</p>
        </article>
    </div>
</section>

<section class="cta-strip">
    <div class="cta-strip-content">
        <div>
            <p class="eyebrow2">Ready to experience joy?</p>
            <h2>Place your first order and taste the difference.</h2>
        </div>
        <a href="/aunt_joy/views/customer/menu.php" class="btn btn-primary">Browse Menu</a>
    </div>
</section>

<section class="contact-section" id="contact">
    <div class="contact-card">
        <div>
            <p class="eyebrow">Visit & Contact</p>
            <h2>Mzuzu · City Centre</h2>
            <p class="lead">Open daily from 08:00 - 22:00 for dine-in, takeaway, and deliveries.</p>
        </div>
        <ul class="contact-list">
            <li>📞 +265 999 123 456</li>
            <li>✉️ hello@auntjoys.mw</li>
            <li>📍 Kenyatta Drive, Plot 14</li>
        </ul>
        <div class="contact-actions">
            <a href="tel:+265999123456" class="btn btn-primary">Call Now</a>
            <button onclick="openRegisterModal()" class="btn btn-secondary">Partner with us</button>
        </div>
    </div>
</section>

<!-- Login Modal -->
<div id="loginModal" class="modal">
    <div class="modal-content modal-auth">
        <span class="modal-close" onclick="closeLoginModal()">&times;</span>
        <div class="auth-logo">
            <div class="logo-icon">🍽️</div>
            <h2>Welcome Back</h2>
            <p>Sign in to continue to Aunt Joy's</p>
        </div>
        
        <div id="loginAlertContainer"></div>
        
        <form id="loginForm" class="auth-form">
            <div class="form-group">
                <label for="login-username">Username or Email</label>
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="login-username" 
                        name="username" 
                        placeholder="Enter your username or email"
                        required
                        autocomplete="username"
                    >
                    <span class="input-icon">👤</span>
                </div>
            </div>

            <div class="form-group">
                <label for="login-password">Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="login-password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <span class="input-icon password-toggle" onclick="togglePassword('login-password')">👁️</span>
                </div>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
                <a href="#" class="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>

            <div class="divider"><span>Don't have an account?</span></div>

            <button type="button" onclick="switchToRegisterModal()" class="btn btn-secondary btn-block">
                Create Account
            </button>
        </form>
    </div>
</div>

<!-- Register Modal -->
<div id="registerModal" class="modal">
    <div class="modal-content modal-auth">
        <span class="modal-close" onclick="closeRegisterModal()">&times;</span>
        <div class="auth-logo">
            <div class="logo-icon">🍽️</div>
            <h2>Create Account</h2>
            <p>Join Aunt Joy's Restaurant today</p>
        </div>
        
        <div id="registerAlertContainer"></div>
        
        <form id="registerForm" class="auth-form">
            <div class="form-group">
                <label for="register-full_name">Full Name</label>
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="register-full_name" 
                        name="full_name" 
                        placeholder="Enter your full name"
                        required
                        autocomplete="name"
                    >
                    <span class="input-icon">👤</span>
                </div>
            </div>

            <div class="form-group">
                <label for="register-username">Username</label>
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="register-username" 
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

            <div class="form-group">
                <label for="register-email">Email Address</label>
                <div class="input-wrapper">
                    <input 
                        type="email" 
                        id="register-email" 
                        name="email" 
                        placeholder="Enter your email"
                        required
                        autocomplete="email"
                    >
                    <span class="input-icon">✉️</span>
                </div>
            </div>

            <div class="form-group">
                <label for="register-phone">Phone Number</label>
                <div class="input-wrapper">
                    <input 
                        type="tel" 
                        id="register-phone" 
                        name="phone_number" 
                        placeholder="+265 999 123 456"
                        autocomplete="tel"
                    >
                    <span class="input-icon">📞</span>
                </div>
            </div>

            <div class="form-group">
                <label for="register-password">Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="register-password" 
                        name="password" 
                        placeholder="Create a password"
                        required
                        autocomplete="new-password"
                        minlength="6"
                    >
                    <span class="input-icon password-toggle" onclick="togglePassword('register-password')">👁️</span>
                </div>
                <small class="form-hint">At least 6 characters</small>
            </div>

            <div class="form-group">
                <label for="register-confirm">Confirm Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="register-confirm" 
                        name="confirm_password" 
                        placeholder="Confirm your password"
                        required
                        autocomplete="new-password"
                    >
                    <span class="input-icon password-toggle" onclick="togglePassword('register-confirm')">👁️</span>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" required>
                    <span>I agree to the <a href="#">Terms & Conditions</a></span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>

            <div class="divider"><span>Already have an account?</span></div>

            <button type="button" onclick="switchToLoginModal()" class="btn btn-secondary btn-block">
                Sign In
            </button>
        </form>
    </div>
</div>

<script>
// Modal functions for login and register
function openLoginModal() {
    document.getElementById('loginModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLoginModal() {
    document.getElementById('loginModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    // Clear form on close
    const loginForm = document.getElementById('loginForm');
    if (loginForm) loginForm.reset();
    const alertContainer = document.getElementById('loginAlertContainer');
    if (alertContainer) alertContainer.innerHTML = '';
}

function openRegisterModal() {
    document.getElementById('registerModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeRegisterModal() {
    document.getElementById('registerModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    // Clear form on close
    const registerForm = document.getElementById('registerForm');
    if (registerForm) registerForm.reset();
    const alertContainer = document.getElementById('registerAlertContainer');
    if (alertContainer) alertContainer.innerHTML = '';
}

function switchToLoginModal() {
    closeRegisterModal();
    openLoginModal();
}

function switchToRegisterModal() {
    closeLoginModal();
    openRegisterModal();
}

// Handle login form
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const alertContainer = document.getElementById('loginAlertContainer');
            
            const payload = {
                username: document.getElementById('login-username').value.trim(),
                password: document.getElementById('login-password').value,
            };

            try {
                showLoading(submitBtn);
                const result = await apiCall('auth/login.php', 'POST', payload);
                
                // Show success message in modal
                if (alertContainer) {
                    alertContainer.innerHTML = `<div class="alert alert-success">${result.message || 'Login successful'}</div>`;
                }
                showNotification(result.message || 'Login successful', 'success');

                const userRole = result.data?.role_name;
                const redirects = {
                    Customer: '/aunt_joy/views/customer/menu.php',
                    Administrator: '/aunt_joy/views/admin/dashboard.php',
                    'Sales Personnel': '/aunt_joy/views/sales/dashboard.php',
                    Manager: '/aunt_joy/views/manager/dashboard.php',
                };

                setTimeout(() => {
                    window.location.href = redirects[userRole] || '/aunt_joy/index.php';
                }, 500);
            } catch (error) {
                // Show error message in modal
                if (alertContainer) {
                    alertContainer.innerHTML = `<div class="alert alert-error">${error.message || 'Login failed'}</div>`;
                }
                showNotification(error.message || 'Login failed', 'error');
            } finally {
                hideLoading(submitBtn);
            }
        });
    }

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const alertContainer = document.getElementById('registerAlertContainer');
            
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm').value;

            if (password !== confirmPassword) {
                if (alertContainer) {
                    alertContainer.innerHTML = `<div class="alert alert-error">Passwords do not match.</div>`;
                }
                showNotification('Passwords do not match.', 'error');
                return;
            }

            const payload = {
                full_name: document.getElementById('register-full_name').value.trim(),
                username: document.getElementById('register-username').value.trim(),
                email: document.getElementById('register-email').value.trim(),
                phone_number: document.getElementById('register-phone').value.trim(),
                password,
            };

            try {
                showLoading(submitBtn);
                const result = await apiCall('auth/register.php', 'POST', payload);
                
                if (alertContainer) {
                    alertContainer.innerHTML = `<div class="alert alert-success">${result.message || 'Registration successful'}</div>`;
                }
                showNotification(result.message || 'Registration successful', 'success');
                
                setTimeout(() => {
                    window.location.href = '/aunt_joy/views/customer/menu.php';
                }, 600);
            } catch (error) {
                if (alertContainer) {
                    alertContainer.innerHTML = `<div class="alert alert-error">${error.message || 'Registration failed'}</div>`;
                }
                showNotification(error.message || 'Registration failed', 'error');
            } finally {
                hideLoading(submitBtn);
            }
        });
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    if (event.target === loginModal) {
        closeLoginModal();
    }
    if (event.target === registerModal) {
        closeRegisterModal();
    }
}
</script>

<?php include __DIR__ . '/views/templates/footer.php'; ?>

