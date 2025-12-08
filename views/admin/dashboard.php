<?php
require_once __DIR__ . '/../../config/db.php';
requireAuth();
requireRole('Administrator');

$pageTitle = "Admin Dashboard - Aunt Joy's Restaurant";
$customCSS = "dashboard.css";
$customJS = "admin-dashboard.js";
$showNav = true;
$showFooter = true;
$bodyClass = "dashboard-page";

include '../templates/header.php';
?>

<div class="dashboard-layout">
    <?php include '../templates/sidebar.php'; ?>
    
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Overview of system statistics</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="location.reload()">
                    🔄 Refresh
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🍽️</div>
                <div class="stat-content">
                    <h3 id="totalMeals">-</h3>
                    <p>Total Meals</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3 id="availableMeals">-</h3>
                    <p>Available</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3 id="totalUsers">-</h3>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                    <h3 id="totalOrders">-</h3>
                    <p>Total Orders</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <button class="action-card" onclick="location.href='/aunt_joy/views/admin/meals.php'">
                        <div class="action-icon">🍽️</div>
                        <h3>Manage Meals</h3>
                        <p>Add, edit or delete meals</p>
                    </button>

                    <button class="action-card" onclick="location.href='/aunt_joy/views/admin/users.php'">
                        <div class="action-icon">👥</div>
                        <h3>Manage Users</h3>
                        <p>View and manage system users</p>
                    </button>

                    <button class="action-card" onclick="openCategoryModal()">
                        <div class="action-icon">📂</div>
                        <h3>Categories</h3>
                        <p>Add, edit or manage categories</p>
                    </button>

                    <button class="action-card" onclick="openSettingsModal()" disabled>
                        <div class="action-icon">⚙️</div>
                        <h3>System Settings</h3>
                        <p>Coming in next update</p>
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Add Buttons Row -->
        <div class="card">
            <div class="card-header">
                <h2>Quick Add</h2>
                <p class="text-muted" style="margin: 0; font-size: 0.9rem;">Add new items directly from dashboard</p>
            </div>
            <div class="card-body">
                <div class="quick-add-buttons">
                    <button class="btn btn-primary" onclick="openDashboardMealModal()">
                        + Add New Meal
                    </button>
                    <button class="btn btn-secondary" onclick="openDashboardUserModal()">
                        + Add New User
                    </button>
                    <button class="btn btn-secondary" onclick="openCategoryModal()">
                        + Manage Categories
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h2>Recent Orders</h2>
                <a href="/aunt_joy/views/sales/dashboard.php" class="btn btn-secondary btn-sm">
                    View All
                </a>
            </div>
            <div class="card-body">
                <div id="recentOrders" class="table-responsive">
                    <p class="text-muted">Loading recent orders...</p>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Dashboard Meal Modal -->
<div id="dashboardMealModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="dashboardMealModalTitle">Add New Meal</h2>
            <span class="modal-close" onclick="closeDashboardMealModal()">&times;</span>
        </div>
        <form id="dashboardMealForm" class="modal-body" onsubmit="submitDashboardMealForm(event)">
            <input type="hidden" id="dashboardMealId" name="meal_id">

            <div class="form-group">
                <label>Meal Name *</label>
                <input 
                    type="text" 
                    id="dashboardMealName" 
                    name="meal_name"
                    class="form-control"
                    placeholder="e.g., Grilled Chicken"
                    required
                >
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select id="dashboardMealCategory" name="category_id" class="form-control" required>
                    <option value="">Select category</option>
                </select>
            </div>

            <div class="form-group">
                <label>Description *</label>
                <textarea 
                    id="dashboardMealDescription" 
                    name="meal_description"
                    class="form-control"
                    placeholder="Describe the meal..."
                    rows="3"
                    required
                ></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Price (MK) *</label>
                    <input 
                        type="number" 
                        id="dashboardMealPrice" 
                        name="price"
                        class="form-control"
                        placeholder="2500"
                        min="0"
                        step="100"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Preparation Time (min)</label>
                    <input 
                        type="number" 
                        id="dashboardMealPrepTime" 
                        name="preparation_time"
                        class="form-control"
                        placeholder="20"
                        min="0"
                        value="20"
                    >
                </div>
            </div>

            <div class="form-group">
                <label>Meal Image *</label>
                <div class="image-upload">
                    <div class="image-preview" id="dashboardMealImagePreview">
                        <span>No image selected</span>
                    </div>
                    <div class="image-upload-actions">
                        <input 
                            type="file" 
                            id="dashboardMealImageFile"
                            name="image_file" 
                            class="form-control" 
                            accept="image/png,image/jpeg,image/jpg"
                        >
                        <input type="hidden" id="dashboardExistingMealImage" name="existing_image">
                        <small class="text-muted">Upload JPG, JPEG or PNG (max 2MB)</small>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="dashboardIsAvailable" name="is_available" checked>
                        <span>Available for order</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="dashboardIsFeatured" name="is_featured">
                        <span>Featured item</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDashboardMealModal()">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="saveDashboardMealBtn">
                    Save Meal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Dashboard User Modal -->
<div id="dashboardUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="dashboardUserModalTitle">Add New User</h2>
            <span class="modal-close" onclick="closeDashboardUserModal()">&times;</span>
        </div>
        <form id="dashboardUserForm" class="modal-body" onsubmit="submitDashboardUserForm(event)">
            <input type="hidden" id="dashboardUserId" name="user_id">

            <div class="form-group">
                <label>Full Name *</label>
                <input 
                    type="text" 
                    id="dashboardFullName" 
                    name="full_name"
                    class="form-control"
                    required
                >
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Username *</label>
                    <input 
                        type="text" 
                        id="dashboardUsername" 
                        name="username"
                        class="form-control"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Role *</label>
                    <select id="dashboardRoleId" name="role_id" class="form-control" required>
                        <option value="">Select role</option>
                        <option value="1">Customer</option>
                        <option value="2">Administrator</option>
                        <option value="3">Sales Personnel</option>
                        <option value="4">Manager</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input 
                        type="email" 
                        id="dashboardEmail" 
                        name="email"
                        class="form-control"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input 
                        type="tel" 
                        id="dashboardPhoneNumber" 
                        name="phone_number"
                        class="form-control"
                        placeholder="+265 999 123 456"
                    >
                </div>
            </div>

            <div class="form-group">
                <label>Password <span id="dashboardPasswordLabel">*</span></label>
                <input 
                    type="password" 
                    id="dashboardPassword" 
                    name="password"
                    class="form-control"
                    placeholder="Leave empty to keep current password"
                >
                <small class="form-hint">Minimum 6 characters</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="dashboardIsActive" name="is_active" checked>
                    <span>Account is active</span>
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDashboardUserModal()">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="saveDashboardUserBtn">
                    Save User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Category Management Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="categoryModalTitle">Add New Category</h2>
            <span class="modal-close" onclick="closeCategoryModal()">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Category Form -->
            <form id="categoryForm" onsubmit="submitCategoryForm(event)">
                <input type="hidden" id="categoryId" name="category_id">

                <div class="form-group">
                    <label>Category Name *</label>
                    <input 
                        type="text" 
                        id="categoryName" 
                        name="category_name"
                        class="form-control"
                        placeholder="e.g., Local Favorites"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea 
                        id="categoryDescription" 
                        name="description"
                        class="form-control"
                        placeholder="Describe this category..."
                        rows="3"
                    ></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Display Order</label>
                        <input 
                            type="number" 
                            id="categoryOrder" 
                            name="display_order"
                            class="form-control"
                            value="1"
                            min="1"
                        >
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="categoryIsActive" name="is_active" checked>
                            <span>Active</span>
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveCategoryBtn">
                        Create Category
                    </button>
                </div>
            </form>

            <!-- Categories List -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>All Categories</h3>
                    <div class="search-box">
                        <input 
                            type="text" 
                            id="categoriesSearchInput" 
                            placeholder="Search categories..."
                            class="form-control"
                            onkeyup="filterCategories()"
                        >
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTableBody">
                                <tr>
                                    <td colspan="5" class="text-center">Loading categories...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>