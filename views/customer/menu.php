<?php
$pageTitle = "Browse Menu - Aunt Joy's Restaurant";
$customCSS = "customer.css";
$customJS = "cart.js";
$showNav = true;
$showFooter = true;
$bodyClass = "customer-page";

include '../templates/header.php';
$isCustomer = isLoggedIn() && getCurrentUserRole() === 'Customer';
?>

<div class="menu-page">
    <!-- Top Navigation with Cart -->
    <div class="menu-nav">
        <div class="container">
            <h1>Browse Our Menu</h1>
            <div class="menu-actions">
                <button
                    id="menuCartButton"
                    class="cart-button"
                    data-target="/aunt_joy/views/customer/cart.php"
                    data-locked="false"
                    data-auth-message="Login to manage your cart."
                    data-redirect="/aunt_joy/views/auth/login.php?next=cart"
                >
                    🛒 Cart (<span id="cartCount">0</span>)
                </button>
                <?php if (isLoggedIn()): ?>
                    <a href="/aunt_joy/views/customer/orders.php" class="btn btn-secondary">My Orders</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Search and Filter Section -->
        <div class="search-section">
            <div class="search-bar">
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Search for meals..."
                    class="search-input"
                >
                <button class="search-btn">🔍</button>
            </div>

            <!-- Category Filters -->
            <div class="category-filters" id="categoryFilters">
                <button class="category-chip active" data-category="all">All Meals</button>
                <!-- Categories will be loaded here -->
            </div>
        </div>

        <!-- Meals Grid -->
        <div class="meals-section">
            <div class="section-header">
                <h2 id="sectionTitle">All Meals</h2>
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid">⊞</button>
                    <button class="view-btn" data-view="list">☰</button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="loading-state">
                <div class="spinner"></div>
                <p>Loading delicious meals...</p>
            </div>

            <!-- Meals Grid Container -->
            <div id="mealsGrid" class="meals-grid">
                <!-- Meals will be loaded here -->
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <div class="empty-icon">🔍</div>
                <h3>No meals found</h3>
                <p>Try adjusting your search or filters</p>
            </div>
        </div>
    </div>
</div>

<!-- Meal Details Modal -->
<div id="mealModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeMealModal()">&times;</span>
        <div id="modalBody">
            <!-- Meal details will be loaded here -->
        </div>
    </div>
</div>

<script>
let currentCategory = 'all';
let meals = [];
let categories = [];
const categoryEmojiMap = {
    'Local Favorites': '🥘',
    'International Grill': '🔥',
    'Veggie Delights': '🥗',
    'Desserts & Treats': '🍰',
    'Street Bites': '🍢',
    'Soups & Sips': '🍲'
};

const escapeHtml = (value = '') => {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
};

const getCategoryEmoji = (name = '') => categoryEmojiMap[name] || '🍽️';

const resolveImagePath = (path = '') => {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    if (path.startsWith('/')) return path;
    return `/aunt_joy/${path.replace(/^\/+/, '')}`;
};

// Load categories and meals on page load
document.addEventListener('DOMContentLoaded', async function() {
    updateCartCount();
    await loadCategories();
    await loadMeals();
    setupEventListeners();
    guardMenuCartButton();
});

// Load categories
async function loadCategories() {
    try {
        const response = await fetch('/aunt_joy/controllers/customer/get_meals.php?categories=true');
        const data = await response.json();
        
        if (data.success) {
            categories = data.data;
            renderCategories();
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Render category filters
function renderCategories() {
    const container = document.getElementById('categoryFilters');
    const existingAll = container.querySelector('[data-category="all"]');
    
    if (existingAll) {
        existingAll.addEventListener('click', () => filterByCategory('all', 'All Meals', existingAll));
    }

    categories.forEach(cat => {
        const chip = document.createElement('button');
        chip.className = 'category-chip';
        chip.dataset.category = cat.category_id;
        chip.innerHTML = `
            <span class="chip-icon">${getCategoryEmoji(cat.category_name)}</span>
            <span>${cat.category_name}</span>
        `;
        chip.addEventListener('click', () => filterByCategory(cat.category_id, cat.category_name, chip));
        container.appendChild(chip);
    });

}

// Load meals
async function loadMeals(categoryId = null, search = null) {
    document.getElementById('loadingState').style.display = 'block';
    document.getElementById('mealsGrid').style.display = 'none';
    document.getElementById('emptyState').style.display = 'none';
    
    try {
        let url = '/aunt_joy/controllers/customer/get_meals.php';
        const params = new URLSearchParams();
        
        if (categoryId) params.append('category_id', categoryId);
        if (search) params.append('search', search);
        
        if (params.toString()) url += '?' + params.toString();
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            meals = data.data;
            renderMeals(meals);
        }
    } catch (error) {
        console.error('Error loading meals:', error);
        showNotification('Failed to load meals', 'error');
    } finally {
        document.getElementById('loadingState').style.display = 'none';
    }
}

// Render meals
function renderMeals(mealsToRender) {
    const grid = document.getElementById('mealsGrid');
    const emptyState = document.getElementById('emptyState');
    
    grid.style.display = 'grid';
    
    if (mealsToRender.length === 0) {
        grid.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }
    
    grid.innerHTML = mealsToRender.map(meal => {
        const imagePath = resolveImagePath(meal.image_url);
        const imageMarkup = imagePath
            ? `<img src="${imagePath}" alt="${escapeHtml(meal.meal_name)}" loading="lazy">`
            : `<div class="meal-emoji">${meal.image_url || '🍽️'}</div>`;
        return `
        <article class="meal-card" data-category="${meal.category_id}">
            <div class="meal-media">
                ${imageMarkup}
                ${meal.is_featured ? '<span class="meal-badge">Chef\'s pick</span>' : ''}
            </div>
            <div class="meal-content">
                <div class="meal-category">${escapeHtml(meal.category_name)}</div>
                <h3 class="meal-name">${escapeHtml(meal.meal_name)}</h3>
                <p class="meal-description">${escapeHtml(meal.meal_description || '')}</p>
                <div class="meal-footer">
                    <div class="meal-price">${meal.price_formatted}</div>
                    <button 
                        class="btn btn-primary add-to-cart-btn"
                        data-meal-id="${meal.meal_id}"
                        data-meal-name="${encodeURIComponent(meal.meal_name)}"
                        data-price="${meal.price}"
                        data-image="${meal.image_url || ''}"
                    >
                        Add to Cart
                    </button>
                </div>
            </div>
        </article>
        `;
    }).join('');
}

// Filter by category
function filterByCategory(categoryId, categoryName, chipElement) {
    currentCategory = categoryId;
    
    // Update active chip
    document.querySelectorAll('.category-chip').forEach(chip => {
        chip.classList.remove('active');
    });
    if (chipElement) {
        chipElement.classList.add('active');
    }
    
    // Update section title
    document.getElementById('sectionTitle').textContent = categoryName || 'All Meals';
    
    // Load meals
    loadMeals(categoryId === 'all' ? null : categoryId);
}

// Search meals
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadMeals(currentCategory === 'all' ? null : currentCategory, e.target.value);
    }, 500);
});

// Setup event listeners
function setupEventListeners() {
    // View toggle
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const grid = document.getElementById('mealsGrid');
            if (this.dataset.view === 'list') {
                grid.classList.add('list-view');
            } else {
                grid.classList.remove('list-view');
            }
        });
    });
}

function guardMenuCartButton() {
    const cartButton = document.getElementById('menuCartButton');
    if (!cartButton) return;
    cartButton.addEventListener('click', () => {
        const requiresAuth = cartButton.dataset.locked === 'true';
        const redirect = cartButton.dataset.redirect || '/aunt_joy/views/auth/login.php';
        const target = cartButton.dataset.target || '/aunt_joy/views/customer/cart.php';
        if (requiresAuth) {
            showNotification(cartButton.dataset.authMessage || 'Login to manage your cart.', 'info');
            setTimeout(() => window.location.href = redirect, 900);
            return;
        }
        window.location.href = target;
    });
}

</script>

<?php include '../templates/footer.php'; ?>