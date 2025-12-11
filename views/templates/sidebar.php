<?php
/**
 * Dashboard Sidebar Template
 * Different menu items based on user role
 */

// Ensure database config is loaded
if (!function_exists('getCurrentUserRole')) {
    require_once __DIR__ . '/../../config/db.php';
}

$userRole = getCurrentUserRole();
?>

<aside class="dashboard-sidebar">
    <!-- User Profile Section -->
    <div class="sidebar-profile">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h4><?php echo $_SESSION['full_name'] ?? 'User'; ?></h4>
            <p class="profile-role"><?php echo $userRole; ?></p>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php if($userRole === 'Customer'): ?>
                <!-- Customer Menu -->
                <li class="nav-item">
                    <a href="/aunt_joy/views/customer/menu.php" class="nav-link">
                        <span class="nav-icon">🍽️</span>
                        <span>Browse Menu</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/aunt_joy/views/customer/cart.php" class="nav-link">
                        <span class="nav-icon">🛒</span>
                        <span>My Cart</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/aunt_joy/views/customer/orders.php" class="nav-link">
                        <span class="nav-icon">📦</span>
                        <span>My Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/aunt_joy/views/auth/profile.php" class="nav-link">
                        <span class="nav-icon">👤</span>
                        <span>Profile</span>
                    </a>
                </li>
                
            <?php elseif($userRole === 'Administrator'): ?>
                <!-- Admin Menu -->
                <li class="nav-item">
                    <a href="/aunt_joy/views/admin/dashboard.php" class="nav-link">
                        <span class="nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/aunt_joy/views/admin/meals.php" class="nav-link">
                        <span class="nav-icon">🍽️</span>
                        <span>Meals Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/aunt_joy/views/admin/users.php" class="nav-link">
                        <span class="nav-icon">👥</span>
                        <span>Users Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/aunt_joy/views/admin/categories.php" class="nav-link">
                        <span class="nav-icon">📂</span>
                        <span>Categories Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/aunt_joy/views/auth/profile.php" class="nav-link">
                        <span class="nav-icon">⚙️</span>
                        <span>Settings</span>
                    </a>
                </li>
                
            <?php elseif($userRole === 'Sales Personnel'): ?>
                <!-- Sales Menu -->
                <li class="nav-item">
                    <a href="/aunt_joy/views/sales/dashboard.php" class="nav-link">
                        <span class="nav-icon">📋</span>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/aunt_joy/views/auth/profile.php" class="nav-link">
                        <span class="nav-icon">👤</span>
                        <span>Profile</span>
                    </a>
                </li>
                
            <?php elseif($userRole === 'Manager'): ?>
                <!-- Manager Menu -->
                <li class="nav-item">
                    <a href="/aunt_joy/views/manager/dashboard.php" class="nav-link">
                        <span class="nav-icon">📊</span>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/aunt_joy/views/auth/profile.php" class="nav-link">
                        <span class="nav-icon">👤</span>
                        <span>Profile</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- Logout (All Users) -->
            <li class="nav-item nav-item-logout">
                <a href="/aunt_joy/controllers/auth/logout.php" class="nav-link">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<script>
// Highlight active page
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    
    navLinks.forEach(link => {
        if(link.getAttribute('href') === currentPath) {
            link.parentElement.classList.add('active');
        }
    });
});
</script>