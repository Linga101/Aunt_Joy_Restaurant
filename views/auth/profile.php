<?php
require_once __DIR__ . '/../../config/db.php';
requireAuth();

$pageTitle = "My Profile - Aunt Joy's Restaurant";
$customCSS = "dashboard.css";
$showNav = true;
$showFooter = true;
$bodyClass = "dashboard-page";

include '../templates/header.php';
?>

<div class="dashboard-layout">
    <?php include '../templates/sidebar.php'; ?>
    
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>My Profile</h1>
            <p>Manage your account information</p>
        </div>

        <div class="profile-container">
            <!-- Profile Card -->
            <div class="card">
                <div class="card-header">
                    <h2>Personal Information</h2>
                </div>
                <div class="card-body">
                    <div class="profile-avatar-section">
                        <div class="profile-avatar-large">
                            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                        </div>
                        <div class="profile-info">
                            <h3><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
                            <p class="profile-role"><?php echo htmlspecialchars($_SESSION['role_name']); ?></p>
                        </div>
                    </div>

                    <div class="profile-details">
                        <div class="detail-item">
                            <span class="detail-label">Username:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">User ID:</span>
                            <span class="detail-value">#<?php echo $_SESSION['user_id']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Actions -->
            <div class="card">
                <div class="card-header">
                    <h2>Account Actions</h2>
                </div>
                <div class="card-body">
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="alert('Edit profile feature coming soon!')">
                            <span>✏️</span>
                            <span>Edit Profile</span>
                        </button>
                        <button class="btn btn-secondary" onclick="alert('Change password feature coming soon!')">
                            <span>🔒</span>
                            <span>Change Password</span>
                        </button>
                        <a href="/aunt_joy/controllers/auth/logout.php" class="btn btn-danger">
                            <span>🚪</span>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>

            <?php if (getCurrentUserRole() === 'Customer'): ?>
            <!-- Quick Stats for Customers -->
            <div class="card">
                <div class="card-header">
                    <h2>My Activity</h2>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon">📦</div>
                            <div class="stat-value" id="totalOrders">-</div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">⏳</div>
                            <div class="stat-value" id="pendingOrders">-</div>
                            <div class="stat-label">Pending Orders</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">✅</div>
                            <div class="stat-value" id="completedOrders">-</div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            // Load customer stats
            fetch('/aunt_joy/controllers/customer/get_orders.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const orders = data.data;
                        document.getElementById('totalOrders').textContent = orders.length;
                        document.getElementById('pendingOrders').textContent = 
                            orders.filter(o => o.order_status === 'Pending').length;
                        document.getElementById('completedOrders').textContent = 
                            orders.filter(o => o.order_status === 'Delivered').length;
                    }
                });
            </script>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../templates/footer.php'; ?>