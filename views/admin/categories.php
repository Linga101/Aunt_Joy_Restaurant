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
                <button class="btn btn-primary" onclick="openCategoryModalNew()">
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

<!-- Category Form Modal -->
<div id="categoryFormModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="categoryFormModalTitle">Add New Category</h2>
            <span class="modal-close" onclick="closeCategoryFormModal()">&times;</span>
        </div>
        <form id="categoryFormNew" class="modal-body" onsubmit="submitCategoryFormNew(event)">
            <input type="hidden" id="categoryIdNew" name="category_id">

            <div class="form-group">
                <label>Category Name *</label>
                <input 
                    type="text" 
                    id="categoryNameNew" 
                    name="category_name"
                    class="form-control"
                    placeholder="e.g., Local Favorites"
                    required
                >
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea 
                    id="categoryDescriptionNew" 
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
                        id="categoryOrderNew" 
                        name="display_order"
                        class="form-control"
                        value="1"
                        min="1"
                    >
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="categoryIsActiveNew" name="is_active" checked>
                        <span>Active</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCategoryFormModal()">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="saveCategoryBtnNew">
                    Create Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Initialize page when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    console.log("Categories page loaded");
    loadCategories();
});

// Helper functions for this page
function openCategoryModalNew(categoryId = null) {
    const modal = document.getElementById('categoryFormModal');
    const form = document.getElementById('categoryFormNew');
    
    if (!modal || !form) {
        showNotification('Error: Modal elements not found', 'error');
        return;
    }

    form.reset();
    document.getElementById('categoryIdNew').value = '';
    document.getElementById('categoryFormModalTitle').textContent = 'Add New Category';
    document.getElementById('saveCategoryBtnNew').textContent = 'Create Category';

    if (categoryId) {
        const category = adminState.categories.find(c => c.category_id == categoryId);
        if (category) {
            document.getElementById('categoryIdNew').value = category.category_id;
            document.getElementById('categoryNameNew').value = category.category_name;
            document.getElementById('categoryDescriptionNew').value = category.description || '';
            document.getElementById('categoryOrderNew').value = category.display_order;
            document.getElementById('categoryIsActiveNew').checked = !!category.is_active;
            document.getElementById('categoryFormModalTitle').textContent = 'Edit Category';
            document.getElementById('saveCategoryBtnNew').textContent = 'Update Category';
        }
    }

    modal.classList.add('active');
}

function closeCategoryFormModal() {
    const modal = document.getElementById('categoryFormModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

async function submitCategoryFormNew(event) {
    event.preventDefault();

    const categoryId = document.getElementById('categoryIdNew').value;
    const categoryName = document.getElementById('categoryNameNew').value;
    const categoryDescription = document.getElementById('categoryDescriptionNew').value;
    const categoryOrder = document.getElementById('categoryOrderNew').value;
    const isActive = document.getElementById('categoryIsActiveNew').checked;

    if (!categoryName.trim()) {
        showNotification('Category name is required', 'error');
        return;
    }

    try {
        const payload = {
            category_id: categoryId ? parseInt(categoryId) : null,
            category_name: categoryName,
            description: categoryDescription,
            display_order: parseInt(categoryOrder),
            is_active: isActive
        };

        const result = await apiCall('admin/save_category.php', 'POST', payload);

        if (result.success) {
            showNotification(result.message || 'Category saved successfully', 'success');
            closeCategoryFormModal();
            await loadCategories();
        } else {
            showNotification(result.message || 'Failed to save category', 'error');
        }
    } catch (error) {
        console.error('Failed to save category:', error);
        showNotification(error.message || 'Failed to save category', 'error');
    }
}

// Override the renderCategories to use the new edit function
function renderCategoriesPage() {
    const container = document.getElementById('categoriesTableBody');
    if (!container) return;

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
            openCategoryModalNew(categoryId);
        });
    });
    
    document.querySelectorAll('#categoriesTableBody button[data-action="delete"]').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteCategory(parseInt(this.dataset.id), this.dataset.name);
        });
    });
}

// Override renderCategories when on this page
const originalRenderCategories = window.renderCategories;
window.renderCategories = function() {
    if (document.getElementById('categoriesTableBody')) {
        renderCategoriesPage();
    } else if (originalRenderCategories) {
        originalRenderCategories();
    }
};
</script>

<?php include '../templates/footer.php'; ?>
