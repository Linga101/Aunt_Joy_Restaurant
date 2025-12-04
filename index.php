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
            <a href="/aunt_joy/views/auth/login.php" class="btn btn-secondary">User Login</a>
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
            <p class="eyebrow">Ready to experience joy?</p>
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
            <a href="/aunt_joy/views/auth/register.php" class="btn btn-secondary">Partner with us</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/views/templates/footer.php'; ?>

