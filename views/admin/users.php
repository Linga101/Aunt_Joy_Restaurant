<?php
require_once __DIR__ . '/../../config/db.php';
requireAuth();
requireRole('Administrator');

$pageTitle = "Manage Users - Aunt Joy's Restaurant";
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
                <h1>User Management</h1>
                <p>Manage system users and roles</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="loadUsers()">
                    🔄 Refresh
                </button>
                <button class="btn btn-primary" onclick="openUserModal()">
                    + Add New User
                </button>
            </div>
        </div>

        <!-- Role Filter -->
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="filterByRole(null)">
                All Users
            </button>
            <button class="filter-tab" onclick="filterByRole(1)">
                Customers
            </button>
            <button class="filter-tab" onclick="filterByRole(2)">
                Administrators
            </button>
            <button class="filter-tab" onclick="filterByRole(3)">
                Sales Personnel
            </button>
            <button class="filter-tab" onclick="filterByRole(4)">
                Managers
            </button>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h2>All Users</h2>
                <div class="search-box">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Search users..."
                        class="form-control"
                    >
                </div>
            </div>
            <div class="card-body">
                <div id="usersTableContainer" class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="7" class="text-center">Loading users...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="userModalTitle">Add New User</h2>
            <span class="modal-close" onclick="closeUserModal()">&times;</span>
        </div>
        <form id="userForm" class="modal-body" onsubmit="submitUserForm(event)">
            <input type="hidden" id="edit_user_id" name="user_id">

            <div class="form-group">
                <label>Full Name *</label>
                <input 
                    type="text" 
                    id="fullName" 
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
                        id="username" 
                        name="username"
                        class="form-control"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Role *</label>
                    <select id="roleId" name="role_id" class="form-control" required>
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
                        id="email" 
                        name="email"
                        class="form-control"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input 
                        type="tel" 
                        id="phoneNumber" 
                        name="phone_number"
                        class="form-control"
                        placeholder="+265 999 123 456"
                    >
                </div>
            </div>

            <div class="form-group">
                <label>Password <span id="passwordLabel">*</span></label>
                <input 
                    type="password" 
                    id="password" 
                    name="password"
                    class="form-control"
                    placeholder="Leave empty to keep current password"
                >
                <small class="form-hint">Minimum 6 characters</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="isActive" name="is_active" checked>
                    <span>Account is active</span>
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="saveUserBtn">
                    Save User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
        closeUserModal();
    }
}
</script>

<?php include '../templates/footer.php'; ?>