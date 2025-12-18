/**
 * Admin Dashboard - Category Management
 * Complete CRUD implementation for meal categories
 */

const adminState = {
    meals: [],
    categories: [],
    users: [],
};

// =========================================================================
// INITIALIZATION
// =========================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('Admin Dashboard Loaded');
    initializeDashboard();
});

async function initializeDashboard() {
    try {
        // Load all data in parallel
        await Promise.all([
            loadStats(),
            loadCategories(),
            loadMeals(),
            loadUsers()
        ]);
        console.log('Dashboard initialization complete');
    } catch (error) {
        console.error('Dashboard initialization failed:', error);
        showNotification('Failed to load dashboard data', 'error');
    }
}

// =========================================================================
// STATS LOADING
// =========================================================================

async function loadStats() {
    try {
        const [meals, users, orders] = await Promise.all([
            apiCall('customer/get_meals.php?include_all=1', 'GET'),
            apiCall('admin/get_users.php', 'GET'),
            apiCall('sales/get_orders.php', 'GET')
        ]);

        // Update stat cards
        if (document.getElementById('totalMeals')) {
            document.getElementById('totalMeals').textContent = meals.data?.length || 0;
        }
        if (document.getElementById('availableMeals')) {
            document.getElementById('availableMeals').textContent = 
                meals.data?.filter(m => m.is_available).length || 0;
        }
        if (document.getElementById('totalUsers')) {
            document.getElementById('totalUsers').textContent = users.data?.length || 0;
        }
        if (document.getElementById('totalOrders')) {
            document.getElementById('totalOrders').textContent = orders.data?.length || 0;
        }

    } catch (error) {
        console.error('Failed to load stats:', error);
    }
}

// =========================================================================
// CATEGORY MANAGEMENT
// =========================================================================

async function loadCategories() {
    try {
        console.log('loadCategories() - requesting data from backend');
        const result = await apiCall('admin/get_categories.php', 'GET');
        console.log('loadCategories() - response received:', result);
        
        if (result.success) {
            adminState.categories = result.data || [];
            console.log(`loadCategories() - ${adminState.categories.length} categories loaded`);
            renderCategories();
        } else {
            console.warn('loadCategories() - API error:', result.message);
        }
    } catch (error) {
        console.error('loadCategories() - exception:', error);
        showNotification('Failed to load categories', 'error');
    }
}

function renderCategories() {
    console.log('renderCategories() called');
    const container = document.getElementById('categoriesTableBody');
    
    if (!container) {
        console.warn('renderCategories() - container not found');
        return;
    }

    if (adminState.categories.length === 0) {
        console.log('renderCategories() - no categories');
        container.innerHTML = '<tr><td colspan="5" class="text-center">No categories found</td></tr>';
        return;
    }

    console.log(`renderCategories() - rendering ${adminState.categories.length} categories`);
    container.innerHTML = adminState.categories.map(cat => `
        <tr>
            <td>${escapeHtml(cat.category_name)}</td>
            <td>${escapeHtml(cat.description || '-')}</td>
            <td><span class="badge ${cat.is_active ? 'badge-success' : 'badge-danger'}">
                ${cat.is_active ? 'Active' : 'Inactive'}</span></td>
            <td>${cat.display_order}</td>
            <td class="actions">
                <button class="btn-icon btn-secondary" onclick="editCategory(${cat.category_id})" title="Edit">✎</button>
                <button class="btn-icon btn-danger" onclick="deleteCategory(${cat.category_id}, '${escapeHtml(cat.category_name)}')" title="Delete">🗑</button>
            </td>
        </tr>
    `).join('');
}

function openCategoryModal(categoryId = null) {
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');
    
    if (!modal || !form) {
        showNotification('Error: Modal not found', 'error');
        return;
    }

    form.reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModalTitle').textContent = 'Add New Category';
    document.getElementById('saveCategoryBtn').textContent = 'Create Category';

    if (categoryId) {
        const category = adminState.categories.find(c => c.category_id == categoryId);
        if (category) {
            document.getElementById('categoryId').value = category.category_id;
            document.getElementById('categoryName').value = category.category_name;
            document.getElementById('categoryDescription').value = category.description || '';
            document.getElementById('categoryOrder').value = category.display_order;
            document.getElementById('categoryIsActive').checked = !!category.is_active;
            document.getElementById('categoryModalTitle').textContent = 'Edit Category';
            document.getElementById('saveCategoryBtn').textContent = 'Update Category';
        }
    }

    modal.classList.add('active');
}

function closeCategoryModal() {
    const modal = document.getElementById('categoryModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

async function submitCategoryForm(event) {
    event.preventDefault();

    const categoryId = document.getElementById('categoryId').value;
    const categoryName = document.getElementById('categoryName').value;
    const description = document.getElementById('categoryDescription').value;
    const displayOrder = parseInt(document.getElementById('categoryOrder').value);
    const isActive = document.getElementById('categoryIsActive').checked;

    if (!categoryName.trim()) {
        showNotification('Category name is required', 'error');
        return;
    }

    try {
        const payload = {
            category_id: categoryId ? parseInt(categoryId) : null,
            category_name: categoryName,
            description: description,
            display_order: displayOrder,
            is_active: isActive
        };

        const result = await apiCall('admin/save_category.php', 'POST', payload);
        
        if (result.success) {
            showNotification(result.message || 'Category saved successfully', 'success');
            closeCategoryModal();
            await loadCategories();
        } else {
            showNotification(result.message || 'Failed to save category', 'error');
        }
    } catch (error) {
        console.error('Error saving category:', error);
        showNotification(error.message || 'Failed to save category', 'error');
    }
}

function editCategory(categoryId) {
    openCategoryModal(categoryId);
}

async function deleteCategory(categoryId, categoryName) {
    if (!confirm(`Delete "${categoryName}"? This cannot be undone.`)) {
        return;
    }

    try {
        const result = await apiCall('admin/delete_category.php', 'POST', { 
            category_id: categoryId 
        });
        
        if (result.success) {
            showNotification('Category deleted successfully', 'success');
            await loadCategories();
        } else {
            showNotification(result.message || 'Failed to delete category', 'error');
        }
    } catch (error) {
        console.error('Error deleting category:', error);
        showNotification(error.message || 'Failed to delete category', 'error');
    }
}

// =========================================================================
// MEALS MANAGEMENT
// =========================================================================

async function loadMeals() {
    try {
        const result = await apiCall('customer/get_meals.php?include_all=1', 'GET');
        if (result.success) {
            adminState.meals = result.data || [];
            console.log(`Loaded ${adminState.meals.length} meals`);
        }
    } catch (error) {
        console.error('Error loading meals:', error);
    }
}

// =========================================================================
// USERS MANAGEMENT
// =========================================================================

async function loadUsers() {
    try {
        const result = await apiCall('admin/get_users.php', 'GET');
        if (result.success) {
            adminState.users = result.data || [];
            console.log(`Loaded ${adminState.users.length} users`);
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

// =========================================================================
// UTILITIES
// =========================================================================

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function showNotification(message, type = 'info') {
    const colors = {
        success: 'linear-gradient(135deg, #4ade80, #22c55e)',
        error: 'linear-gradient(135deg, #ef4444, #dc2626)',
        warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
        info: 'linear-gradient(135deg, #ff8c42, #ff6b35)'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        background: ${colors[type] || colors.info};
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Modal close on outside click
document.addEventListener('click', (e) => {
    const modal = document.getElementById('categoryModal');
    if (modal && e.target === modal) {
        closeCategoryModal();
    }
});


