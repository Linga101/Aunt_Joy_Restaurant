<?php
require_once __DIR__ . '/../../config/db.php';
requireAuth();
requireRole('Administrator');

$pageTitle = "Manage Meals - Aunt Joy's Restaurant";
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
                <h1>Meals Management</h1>
                <p>Add, edit, and manage menu items</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="loadMeals()">
                    🔄 Refresh
                </button>
                <button class="btn btn-primary" onclick="openMealModal()">
                    + Add New Meal
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🍽️</div>
                <div class="stat-content">
                    <h3 id="totalMeals">0</h3>
                    <p>Total Meals</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3 id="inStock">0</h3>
                    <p>In Stock</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">❌</div>
                <div class="stat-content">
                    <h3 id="outOfStock">0</h3>
                    <p>Out of Stock</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-content">
                    <h3 id="featured">0</h3>
                    <p>Featured</p>
                </div>
            </div>
        </div>

        <!-- Meals Table -->
        <div class="card">
            <div class="card-header">
                <h2>All Meals</h2>
                <div class="search-box">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Search meals..."
                        class="form-control"
                    >
                </div>
            </div>
            <div class="card-body">
                <div id="mealsTableContainer" class="table-responsive">
                    <table class="table" id="mealsTable">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="mealsTableBody">
                            <tr>
                                <td colspan="6" class="text-center">Loading meals...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Meal Modal -->
<div id="mealModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="mealModalTitle">Add New Meal</h2>
            <span class="modal-close" onclick="closeMealModal()">&times;</span>
        </div>
        <form id="mealForm" class="modal-body" onsubmit="submitMealForm(event)">
            <input type="hidden" id="meal_id" name="meal_id">

            <div class="form-group">
                <label>Meal Name *</label>
                <input 
                    type="text" 
                    id="mealName" 
                    name="meal_name"
                    class="form-control"
                    placeholder="e.g., Grilled Chicken"
                    required
                >
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select id="mealCategory" name="category_id" class="form-control" required>
                    <option value="">Select category</option>
                </select>
            </div>

            <div class="form-group">
                <label>Description *</label>
                <textarea 
                    id="mealDescription" 
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
                        id="mealPrice" 
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
                        id="mealPrepTime" 
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
                    <div class="image-preview" id="mealImagePreview">
                        <span>No image selected</span>
                    </div>
                    <div class="image-upload-actions">
                        <input 
                            type="file" 
                            id="mealImageFile"
                            name="image_file" 
                            class="form-control" 
                            accept="image/png,image/jpeg,image/jpg"
                        >
                        <input type="hidden" id="existingMealImage" name="existing_image">
                        <small class="text-muted">Upload JPG, JPEG or PNG (max 2MB)</small>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="isAvailable" name="is_available" checked>
                        <span>Available for order</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="isFeatured" name="is_featured">
                        <span>Featured item</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeMealModal()">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="saveMealBtn">
                    Save Meal
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../templates/footer.php'; ?>