<?php
require_once __DIR__ . '/../../config/db.php';
requireAuth();
requireRole('Administrator');

$pageTitle = "Manage Categories - Aunt Joy's Restaurant";
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
                <h1>Categories Management</h1>
                <p>Create, edit, and manage meal categories</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="loadCategories()">
                    🔄 Refresh
                </button>
                <button class="btn btn-primary" onclick="openCategoryModal()">
                    + Add New Category
                </button>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="card">
            <div class="card-header">
                <h2>All Categories</h2>
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
    </main>
</div>

<!-- Category Form Modal (Shared with Dashboard) -->
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
        </div>
    </div>
</div>

<script>
// Initialize page when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    console.log("Categories page loaded");
    loadCategories();
});

// Override renderCategories to add proper event listeners on this page
const originalRenderCategories = window.renderCategories;
window.renderCategories = function() {
    const container = document.getElementById('categoriesTableBody');
    if (!container) {
        if (originalRenderCategories) {
            originalRenderCategories();
        }
        return;
    }

    if (adminState.categories.length === 0) {
        container.innerHTML = '<tr><td colspan="5" class="text-center">No categories found. Create one to get started.</td></tr>';
        return;
    }

    container.innerHTML = adminState.categories.map(cat => `
        <tr>
            <td>${escapeHtml(cat.category_name)}</td>
            <td>${escapeHtml(cat.description || '-')}</td>
            <td><span class="badge ${cat.is_active ? 'badge-success' : 'badge-danger'}">${cat.is_active ? 'Active' : 'Inactive'}</span></td>
            <td>${cat.display_order}</td>
            <td class="actions">
                <button type="button" class="btn-icon btn-secondary" data-action="edit" data-id="${cat.category_id}" title="Edit">✎</button>
                <button type="button" class="btn-icon btn-danger" data-action="delete" data-id="${cat.category_id}" data-name="${escapeHtml(cat.category_name)}" title="Delete">🗑</button>
            </td>
        </tr>
    `).join('');
    
    // Add event listeners to dynamically created buttons
    document.querySelectorAll('#categoriesTableBody button[data-action="edit"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = parseInt(this.dataset.id);
            openCategoryModal(categoryId);
        });
    });
    
    document.querySelectorAll('#categoriesTableBody button[data-action="delete"]').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteCategory(parseInt(this.dataset.id), this.dataset.name);
        });
    });
};
</script>

<?php include '../templates/footer.php'; ?>
