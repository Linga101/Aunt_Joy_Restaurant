/**
 * Admin Dashboard - Category Management
 * Complete CRUD implementation for meal categories
 */

const adminState = {
    meals: [],
    categories: [],
    users: [],
};

// Debug: indicate the admin-dashboard script file was loaded
console.log('admin-dashboard.js loaded');

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
            loadUsers(),
            loadRecentOrders()
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
// RECENT ORDERS LOADING
// =========================================================================

async function loadRecentOrders() {
    try {
        const response = await apiCall('sales/get_orders.php', 'GET');
        
        if (response && response.success) {
            // Take only the first 5 recent orders
            const recentOrders = (response.data || []).slice(0, 5);
            displayRecentOrders(recentOrders);
        } else {
            console.error('Failed to load recent orders:', response?.message);
            document.getElementById('recentOrders').innerHTML = 
                '<p class="text-muted">Failed to load recent orders</p>';
        }
    } catch (error) {
        console.error('Error loading recent orders:', error);
        document.getElementById('recentOrders').innerHTML = 
            '<p class="text-muted">Error loading recent orders</p>';
    }
}

function displayRecentOrders(orders) {
    const container = document.getElementById('recentOrders');
    
    if (!orders || orders.length === 0) {
        container.innerHTML = '<p class="text-muted">No recent orders found</p>';
        return;
    }
    
    let html = `
        <table class="table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    orders.forEach(order => {
        const statusClass = getStatusClass(order.order_status);
        const formattedDate = new Date(order.order_date).toLocaleDateString();
        
        html += `
            <tr>
                <td><strong>${order.order_number}</strong></td>
                <td>${order.customer_name}</td>
                <td><span class="status-badge ${statusClass}">${order.order_status}</span></td>
                <td>${order.total_amount_formatted}</td>
                <td>${formattedDate}</td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    container.innerHTML = html;
}

function getStatusClass(status) {
    // Convert status to CSS class format: lowercase with hyphens
    return 'status-' + status.toLowerCase().replace(/ /g, '-');
}

async function loadMeals() {
    try {
        console.log("loadMeals called");
        const response = await apiCall("customer/get_meals.php?include_all=1");
        console.log("Meals response:", response);
        
        if (response && response.success) {
            adminState.meals = response.data || [];
            console.log("Meals loaded:", adminState.meals.length);
            renderMeals(adminState.meals);
            updateMealStats();
            if (!adminState.meals.length) {
                showNotification('No meals found. Add some to get started.', 'info');
            }
        } else {
            const errorMsg = response?.message || "Failed to load meals";
            showNotification(errorMsg, "error");
            console.error("Meals API error:", response);
            const tbody = document.getElementById("mealsTableBody");
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">Failed to load meals</td></tr>';
            }
        }
    } catch (error) {
        console.error("Failed to load meals:", error);
        showNotification("Error loading meals: " + (error?.message || 'Network error'), "error");
        const tbody = document.getElementById("mealsTableBody");
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Error loading meals</td></tr>';
        }
    }
}

function renderMeals(meals) {
    const tbody = document.getElementById("mealsTableBody");
    if (!tbody) return;

    if (!meals.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No meals found</td></tr>';
        return;
    }

    tbody.innerHTML = meals
        .map((meal) => {
            const hasImage = meal.image_url && meal.image_url.includes("/");
            const imageMarkup = hasImage
                ? `<img src="/aunt_joy/${meal.image_url}" alt="${meal.meal_name}" class="meal-thumb">`
                : `<span class="meal-image-fallback">🍽️</span>`;
            return `
        <tr>
            <td class="meal-image-cell">${imageMarkup}</td>
            <td><strong>${meal.meal_name}</strong></td>
            <td>${meal.category_name}</td>
            <td><strong>${meal.price_formatted || formatCurrency(meal.price)}</strong></td>
            <td>
                <span class="status-badge ${
                    meal.is_available ? "status-available" : "status-unavailable"
                }">
                    ${meal.is_available ? "Available" : "Out of Stock"}
                </span>
                ${meal.is_featured ? '<span class="badge-featured">⭐</span>' : ""}
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon" onclick="openMealModal(${meal.meal_id})" title="Edit">✏️</button>
                    <button class="btn-icon btn-danger" onclick="deleteMeal(${meal.meal_id}, '${meal.meal_name.replace(
                        /'/g,
                        "\\'"
                    )}')" title="Delete">🗑️</button>
                </div>
            </td>
        </tr>
    `;
        })
        .join("");
}

function updateMealStats() {
    const meals = adminState.meals;
    setText("totalMeals", meals.length);
    setText(
        "inStock",
        meals.filter((meal) => meal.is_available).length
    );
    setText(
        "outOfStock",
        meals.filter((meal) => !meal.is_available).length
    );
    setText(
        "featured",
        meals.filter((meal) => meal.is_featured).length
    );
}

function filterMeals(keyword = "") {
    const term = keyword.toLowerCase();
    const filtered = adminState.meals.filter(
        (meal) =>
            meal.meal_name.toLowerCase().includes(term) ||
            (meal.meal_description || "").toLowerCase().includes(term) ||
            meal.category_name.toLowerCase().includes(term)
    );
    renderMeals(filtered);
}

async function loadMealCategories() {
    const select = document.getElementById("mealCategory");
    if (!select) return;

    // Only reload if empty or in add mode
    if (select.options.length > 1) return;

    select.innerHTML = '<option value="">Select category</option>';

    try {
        const response = await apiCall("customer/get_meals.php?categories=true");
        if (response && response.success && response.data) {
            response.data.forEach((cat) => {
                const option = document.createElement("option");
                option.value = cat.category_id;
                option.textContent = cat.category_name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error("Failed to load categories:", error);
    }
}

async function openMealModal(mealId = null) {
    const modal = document.getElementById("mealModal");
    const form = document.getElementById("mealForm");
    const title = document.getElementById("mealModalTitle");
    
    // Load categories first
    await loadMealCategories();
    
    // Reset form first
    form.reset();
    document.getElementById("meal_id").value = "";
    resetMealImageInputs("");
    
    if (mealId) {
        // Edit mode
        title.textContent = "Edit Meal";
        document.getElementById("meal_id").value = mealId;
        
        // Find meal in cached data
        if (adminState.meals && adminState.meals.length > 0) {
            const meal = adminState.meals.find(m => m.meal_id == mealId);
            if (meal) {
                // Load meal data into form
                document.getElementById("mealName").value = meal.meal_name;
                document.getElementById("mealCategory").value = meal.category_id;
                document.getElementById("mealDescription").value = meal.meal_description;
                document.getElementById("mealPrice").value = meal.price;
                document.getElementById("mealPrepTime").value = meal.preparation_time || 20;
                document.getElementById("isAvailable").checked = !!meal.is_available;
                document.getElementById("isFeatured").checked = !!meal.is_featured;
                resetMealImageInputs(meal.image_url || "");
            } else {
                showNotification("Meal data could not be loaded", "error");
                return;
            }
        }
    } else {
        // Add mode
        title.textContent = "Add New Meal";
    }
    
    // Show modal
    modal.classList.add("active");
}

function closeMealModal() {
    const modal = document.getElementById("mealModal");
    modal.classList.remove("active");
    const form = document.getElementById("mealForm");
    if (form) {
        form.reset();
    }
}

async function submitMealForm(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = document.getElementById("saveMealBtn");

    if (!validateForm(form)) {
        showNotification("Please complete the required fields", "warning");
        return;
    }

    const fileInput = document.getElementById("mealImageFile");
    const mealIdField = document.getElementById("meal_id");
    const mealNameField = document.getElementById("mealName");
    const mealCategoryField = document.getElementById("mealCategory");
    const mealDescField = document.getElementById("mealDescription");
    const mealPriceField = document.getElementById("mealPrice");
    const mealPrepTimeField = document.getElementById("mealPrepTime");
    const isAvailableField = document.getElementById("isAvailable");
    const isFeaturedField = document.getElementById("isFeatured");
    const existingImageField = document.getElementById("existingMealImage");

    const hasExistingImage = existingImageField.value.trim() !== "";
    const fileSelected = fileInput && fileInput.files.length > 0;

    if (!hasExistingImage && !fileSelected) {
        showNotification("Please upload a meal photo.", "warning");
        return;
    }

    // Validate image size before submission
    if (fileSelected && fileInput.files[0].size > 2 * 1024 * 1024) {
        showNotification("Image is too large. Maximum size is 2MB.", "warning");
        return;
    }

    const payload = new FormData();
    payload.append("meal_id", mealIdField.value || "");
    payload.append("category_id", mealCategoryField.value);
    payload.append("meal_name", mealNameField.value.trim());
    payload.append("meal_description", mealDescField.value.trim());
    payload.append("price", parseFloat(mealPriceField.value));
    payload.append("preparation_time", parseInt(mealPrepTimeField.value, 10) || 20);
    payload.append("is_available", isAvailableField.checked ? "1" : "0");
    payload.append("is_featured", isFeaturedField.checked ? "1" : "0");
    payload.append("existing_image", existingImageField.value.trim());
    if (fileSelected) {
        payload.append("image_file", fileInput.files[0]);
    }

    try {
        showLoading(submitBtn);
        const result = await apiCall("admin/save_meal.php", "POST", payload);
        if (result && result.success) {
            showNotification(result.message || "Meal saved successfully", "success");
            closeMealModal();
            await loadMeals();
        } else {
            showNotification((result?.message || "Failed to save meal"), "error");
        }
    } catch (error) {
        console.error("Failed to save meal:", error);
        showNotification((error?.message || "Failed to save meal"), "error");
    } finally {
        hideLoading(submitBtn);
    }
}

async function deleteMeal(mealId, mealName) {
    if (!confirm(`Are you sure you want to permanently delete "${mealName}"? This action cannot be undone and may affect related data.`)) {
        return;
    }

    try {
        const result = await apiCall("admin/delete_meal.php", "POST", { meal_id: mealId });
        showNotification(result.message || "Meal deleted", "success");
        await loadMeals();
    } catch (error) {
        console.error("Failed to delete meal:", error);
        showNotification(error.message || "Failed to delete meal", "error");
    }
}

function handleMealImageChange(event) {
    const file = event.target.files[0];
    if (file) {
        const previewUrl = URL.createObjectURL(file);
        renderMealImagePreview(previewUrl, true);
    }
}

function resetMealImageInputs(existingPath = "") {
    const fileInput = document.getElementById("mealImageFile");
    if (fileInput) {
        fileInput.value = "";
    }
    setExistingMealImage(existingPath);
    if (existingPath) {
        renderMealImagePreview(existingPath);
    } else {
        renderMealImagePreview(null);
    }
}

function setExistingMealImage(path) {
    const existingInput = document.getElementById("existingMealImage");
    if (existingInput) {
        existingInput.value = path || "";
    }
}

function renderMealImagePreview(src, isBlob = false) {
    const preview = document.getElementById("mealImagePreview");
    if (!preview) return;
    if (src) {
        let resolved = src;
        if (!isBlob && !src.startsWith("http") && !src.startsWith("/") && !src.startsWith("blob:")) {
            resolved = `/aunt_joy/${src}`;
        }
        preview.innerHTML = `<img src="${resolved}" alt="Meal preview">`;
    } else {
        preview.innerHTML = "<span>No image selected</span>";
    }
}

/* --------------------------------------------------------------------------
   Helpers
   -------------------------------------------------------------------------- */
function setText(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = value;
    }
}

/* --------------------------------------------------------------------------
   User Management Functions
   -------------------------------------------------------------------------- */

function initUserManagement() {
    // Pre-load users for edit functionality
    loadUsers();
    
    // Add search functionality
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
        searchInput.addEventListener(
            "input",
            debounce((event) => {
                filterUsers(event.target.value);
            }, 300)
        );
    }

    // Close modal when clicking outside
    window.addEventListener("click", (event) => {
        const modal = document.getElementById("userModal");
        if (event.target === modal) {
            closeUserModal();
        }
    });
}

function loadUsers(roleId = null) {
    const tbody = document.getElementById("usersTableBody");
    
    let url = 'admin/get_users.php';
    if (roleId) {
        url += `?role_id=${roleId}`;
    }
    
    // Show loading state
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Loading users...</td></tr>';
    }
    
    apiCall(url, 'GET')
    .then(response => {
        if (response && response.success) {
            adminState.users = response.data || [];
            renderUsers(adminState.users);
            if (!response.data || response.data.length === 0) {
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">No users found</td></tr>';
                }
            }
        } else {
            const errorMsg = response?.message || 'Unknown error';
            showNotification('Failed to load users: ' + errorMsg, 'error');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Failed to load users</td></tr>';
            }
        }
    })
    .catch(error => {
        console.error('Error loading users:', error);
        showNotification('Error loading users: ' + (error?.message || 'Network error'), 'error');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Error loading users</td></tr>';
        }
    });
}

function renderUsers(users) {
    const tbody = document.getElementById("usersTableBody");
    if (!tbody) return;
    
    if (!users || users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">No users found</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>${user.user_id}</td>
            <td>${escapeHtml(user.full_name)}</td>
            <td>${escapeHtml(user.username)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td>${escapeHtml(user.role_name)}</td>
            <td>
                <span class="status-badge ${user.is_active ? 'status-active' : 'status-inactive'}">
                    ${user.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon" onclick="editUser(${user.user_id})" title="Edit">✏️</button>
                    <button class="btn-icon btn-danger" onclick="deleteUser(${user.user_id})" title="Delete">🗑️</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function openUserModal(userId = null) {
    const modal = document.getElementById("userModal");
    const form = document.getElementById("userForm");
    const title = document.getElementById("userModalTitle");
    const passwordInput = document.getElementById("password");
    const passwordLabel = document.getElementById("passwordLabel");
    
    // Reset form first
    form.reset();
    document.getElementById("edit_user_id").value = "";
    
    if (userId) {
        // Edit mode
        title.textContent = "Edit User";
        document.getElementById("edit_user_id").value = userId;
        
        // Set password field as optional for edit
        if (passwordLabel) {
            passwordLabel.textContent = "(Leave empty to keep current)";
        }
        if (passwordInput) {
            passwordInput.required = false;
            passwordInput.placeholder = "Leave empty to keep current password";
        }
        
        // Find user in cached data
        if (adminState.users && adminState.users.length > 0) {
            const user = adminState.users.find(u => u.user_id == userId);
            if (user) {
                // Load user data into form
                document.getElementById("fullName").value = user.full_name;
                document.getElementById("username").value = user.username;
                document.getElementById("email").value = user.email;
                document.getElementById("roleId").value = user.role_id;
                document.getElementById("phoneNumber").value = user.phone_number || '';
                document.getElementById("isActive").checked = !!user.is_active;
            } else {
                showNotification("User data could not be loaded", "error");
                return;
            }
        }
    } else {
        // Add mode
        title.textContent = "Add New User";
        document.getElementById("isActive").checked = true;
        
        // Set password field as required for new user
        if (passwordLabel) {
            passwordLabel.textContent = "*";
        }
        if (passwordInput) {
            passwordInput.required = true;
            passwordInput.placeholder = "Minimum 6 characters";
        }
    }
    
    // Show modal
    modal.classList.add("active");
}

function closeUserModal() {
    const modal = document.getElementById("userModal");
    modal.classList.remove("active");
    const form = document.getElementById("userForm");
    if (form) {
        form.reset();
    }
}

function editUser(userId) {
    openUserModal(userId);
}

function submitUserForm(event) {
    if (event) {
        event.preventDefault();
    }
    
    const form = document.getElementById("userForm");
    if (!form) return;
    
    // Validate form using main.js function
    if (!validateForm(form)) {
        return;
    }
    
    const userId = document.getElementById("edit_user_id").value;
    const roleId = document.getElementById("roleId").value;
    const username = document.getElementById("username").value;
    const email = document.getElementById("email").value;
    const fullName = document.getElementById("fullName").value;
    const phoneNumber = document.getElementById("phoneNumber").value;
    const password = document.getElementById("password").value;
    const isActive = document.getElementById("isActive").checked;
    
    // Validate required fields
    if (!roleId || !username || !email || !fullName) {
        showNotification('Please fill all required fields', 'error');
        return;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showNotification('Invalid email format', 'error');
        return;
    }
    
    // If creating new user, password is required
    if (!userId && !password) {
        showNotification('Password is required for new users', 'error');
        return;
    }
    
    // If password provided, validate length
    if (password && password.length < 6) {
        showNotification('Password must be at least 6 characters', 'error');
        return;
    }
    
    // Build payload
    const payload = {
        role_id: parseInt(roleId),
        username: username,
        email: email,
        full_name: fullName,
        phone_number: phoneNumber,
        is_active: isActive
    };
    
    // Add user_id if editing
    if (userId) {
        payload.user_id = parseInt(userId);
    }
    
    // Add password if provided
    if (password) {
        payload.password = password;
    }
    
    const saveUserBtn = document.getElementById('saveUserBtn');
    showLoading(saveUserBtn);
    
    apiCall('admin/save_user.php', 'POST', payload)
    .then(response => {
        hideLoading(saveUserBtn);
        
        if (response && response.success) {
            showNotification(response.message || 'User saved successfully', 'success');
            closeUserModal();
            loadUsers();
        } else {
            showNotification('Error: ' + (response?.message || 'Failed to save user'), 'error');
        }
    })
    .catch(error => {
        hideLoading(saveUserBtn);
        console.error('Error saving user:', error);
        showNotification('Error saving user: ' + (error?.message || 'Network error'), 'error');
    });
}

function deleteUser(userId) {
    if (!confirm('Are you sure you want to permanently delete this user? This action cannot be undone and may affect related data.')) {
        return;
    }
    
    apiCall('admin/delete_user.php', 'POST', { user_id: userId })
    .then(response => {
        if (response.success) {
            showNotification(response.message || 'User deleted successfully', 'success');
            loadUsers();
        } else {
            showNotification('Error: ' + (response.message || 'Failed to delete user'), 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting user:', error);
        showNotification('Error deleting user: ' + error.message, 'error');
    });
}

function filterByRole(roleId) {
    // Update active tab styling
    const filterTabs = document.querySelectorAll('.filter-tab');
    filterTabs.forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Set active on clicked tab
    if (roleId === null || roleId === 'all' || roleId === '') {
        // Find "All Users" tab
        filterTabs[0].classList.add('active');
        loadUsers(null);
    } else {
        // Find the tab with matching role_id
        let tabIndex = -1;
        if (roleId === 1) tabIndex = 1; // Customers
        else if (roleId === 2) tabIndex = 2; // Administrators
        else if (roleId === 3) tabIndex = 3; // Sales Personnel
        else if (roleId === 4) tabIndex = 4; // Managers
        
        if (tabIndex >= 0 && filterTabs[tabIndex]) {
            filterTabs[tabIndex].classList.add('active');
        }
        loadUsers(parseInt(roleId));
    }
}

function filterUsers(keyword = "") {
    const term = keyword.toLowerCase();
    const filtered = adminState.users.filter(
        (user) =>
            user.full_name.toLowerCase().includes(term) ||
            user.username.toLowerCase().includes(term) ||
            (user.email || "").toLowerCase().includes(term) ||
            (user.role_name || "").toLowerCase().includes(term)
    );
    renderUsers(filtered);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/* --------------------------------------------------------------------------
   Dashboard Modal Initialization
   -------------------------------------------------------------------------- */

function initDashboardMealModal() {
    console.log("initDashboardMealModal called");
    const modal = document.getElementById("dashboardMealModal");
    const fileInput = document.getElementById("dashboardMealImageFile");
    
    console.log("Modal element:", modal);
    console.log("File input element:", fileInput);
    
    if (fileInput) {
        fileInput.addEventListener("change", handleDashboardMealImageChange);
        console.log("Added change listener to file input");
    }

    // Close modal when clicking outside
    window.addEventListener("click", (event) => {
        const modal = document.getElementById("dashboardMealModal");
        if (event.target === modal) {
            closeDashboardMealModal();
        }
    });
    console.log("Added click outside listener");
    
    // Load categories on init
    loadDashboardMealCategories();
}

function initDashboardUserModal() {
    console.log("initDashboardUserModal called");
    const modal = document.getElementById("dashboardUserModal");
    
    console.log("Modal element:", modal);

    // Close modal when clicking outside
    window.addEventListener("click", (event) => {
        const modal = document.getElementById("dashboardUserModal");
        if (event.target === modal) {
            closeDashboardUserModal();
        }
    });
    console.log("Added click outside listener");
}

function initCategoryModal() {
    console.log("initCategoryModal called");
    const modal = document.getElementById("categoryModal");
    
    console.log("Modal element:", modal);

    // Close modal when clicking outside
    window.addEventListener("click", (event) => {
        const modal = document.getElementById("categoryModal");
        if (event.target === modal) {
            closeCategoryModal();
        }
    });
    console.log("Added click outside listener for category modal");
    
    // Load categories on init
    loadCategories();
}

/* --------------------------------------------------------------------------
   Dashboard Meal Modal Functions
   -------------------------------------------------------------------------- */

function closeDashboardMealModal() {
    console.log("closeDashboardMealModal called");
    const modal = document.getElementById("dashboardMealModal");
    if (modal) {
        modal.classList.remove("active");
    }
    const form = document.getElementById("dashboardMealForm");
    if (form) {
        form.reset();
    }
}

async function loadDashboardMealCategories() {
    const select = document.getElementById("dashboardMealCategory");
    if (!select) return;

    select.innerHTML = '<option value="">Select category</option>';

    try {
        console.log("loadDashboardMealCategories called");
        const response = await apiCall("customer/get_meals.php?categories=true");
        console.log("Dashboard Categories response:", response);
        
        if (response && response.success && response.data) {
            adminState.categories = response.data;
            console.log("Dashboard Categories loaded:", adminState.categories.length);
            
            adminState.categories.forEach((cat) => {
                const option = document.createElement("option");
                option.value = cat.category_id;
                option.textContent = cat.category_name;
                select.appendChild(option);
            });
        } else {
            console.error("Failed to load dashboard categories:", response?.message);
            showNotification("Unable to load categories", "error");
        }
    } catch (error) {
        console.error("Failed to load dashboard categories:", error);
        showNotification("Unable to load categories: " + (error?.message || 'Network error'), "error");
    }
}

function handleDashboardMealImageChange(event) {
    const file = event.target.files[0];
    if (file) {
        const previewUrl = URL.createObjectURL(file);
        renderDashboardMealImagePreview(previewUrl, true);
    }
}

function resetDashboardMealImageInputs(existingPath = "") {
    const fileInput = document.getElementById("dashboardMealImageFile");
    if (fileInput) {
        fileInput.value = "";
    }
    document.getElementById("dashboardExistingMealImage").value = existingPath || "";
    if (existingPath) {
        renderDashboardMealImagePreview(existingPath);
    } else {
        renderDashboardMealImagePreview(null);
    }
}

function renderDashboardMealImagePreview(src, isBlob = false) {
    const preview = document.getElementById("dashboardMealImagePreview");
    if (!preview) return;
    if (src) {
        let resolved = src;
        if (!isBlob && !src.startsWith("http") && !src.startsWith("/") && !src.startsWith("blob:")) {
            resolved = `/aunt_joy/${src}`;
        }
        preview.innerHTML = `<img src="${resolved}" alt="Meal preview">`;
    } else {
        preview.innerHTML = "<span>No image selected</span>";
    }
}

async function submitDashboardMealForm(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = document.getElementById("saveDashboardMealBtn");

    console.log("submitDashboardMealForm called");

    if (!validateForm(form)) {
        showNotification("Please complete the required fields", "warning");
        return;
    }

    const fileInput = document.getElementById("dashboardMealImageFile");
    const hasExistingImage = document.getElementById("dashboardExistingMealImage").value.trim() !== "";
    const fileSelected = fileInput && fileInput.files.length > 0;

    if (!hasExistingImage && !fileSelected) {
        showNotification("Please upload a meal photo.", "warning");
        return;
    }

    // Validate image size before submission
    if (fileSelected && fileInput.files[0].size > 2 * 1024 * 1024) {
        showNotification("Image is too large. Maximum size is 2MB.", "warning");
        return;
    }

    const payload = new FormData();
    payload.append("meal_id", document.getElementById("dashboardMealId").value || "");
    payload.append("category_id", document.getElementById("dashboardMealCategory").value);
    payload.append("meal_name", document.getElementById("dashboardMealName").value.trim());
    payload.append("meal_description", document.getElementById("dashboardMealDescription").value.trim());
    payload.append("price", parseFloat(document.getElementById("dashboardMealPrice").value));
    payload.append("preparation_time", parseInt(document.getElementById("dashboardMealPrepTime").value, 10) || 20);
    payload.append("is_available", document.getElementById("dashboardIsAvailable").checked ? "1" : "0");
    payload.append("is_featured", document.getElementById("dashboardIsFeatured").checked ? "1" : "0");
    payload.append("existing_image", document.getElementById("dashboardExistingMealImage").value.trim());
    if (fileSelected) {
        payload.append("image_file", fileInput.files[0]);
    }

    try {
        showLoading(submitBtn);
        const result = await apiCall("admin/save_meal.php", "POST", payload);
        if (result && result.success) {
            showNotification(result.message || "Meal saved successfully", "success");
            closeDashboardMealModal();
            // Reload meals for both dashboards
            await loadMeals();
        } else {
            showNotification((result?.message || "Failed to save meal"), "error");
            console.error("Meal save error:", result);
        }
    } catch (error) {
        console.error("Failed to save meal:", error);
        showNotification((error?.message || "Failed to save meal"), "error");
    } finally {
        hideLoading(submitBtn);
    }
}

/* --------------------------------------------------------------------------
   Dashboard User Modal Functions
   -------------------------------------------------------------------------- */

async function openDashboardUserModal(userId = null) {
    console.log("openDashboardUserModal called with userId:", userId);
    
    const modal = document.getElementById("dashboardUserModal");
    const form = document.getElementById("dashboardUserForm");
    const modalTitle = document.getElementById("dashboardUserModalTitle");
    const passwordInput = document.getElementById("dashboardPassword");
    const passwordLabel = document.getElementById("dashboardPasswordLabel");
    
    if (!modal || !form) {
        showNotification("Error: Modal elements not found on page", "error");
        return;
    }

    // Reset form
    form.reset();
    
    if (userId) {
        // Edit mode
        modalTitle.textContent = "Edit User";
        document.getElementById("dashboardUserId").value = userId;
        if (passwordLabel) {
            passwordLabel.textContent = "(Leave empty to keep current)";
        }
        if (passwordInput) {
            passwordInput.required = false;
            passwordInput.placeholder = "Leave empty to keep current password";
        }
        
        // Load user data if available
        if (adminState.users && adminState.users.length > 0) {
            const user = adminState.users.find(u => u.user_id == userId);
            if (user) {
                document.getElementById("dashboardUsername").value = user.username;
                document.getElementById("dashboardEmail").value = user.email;
                document.getElementById("dashboardFullName").value = user.full_name;
                document.getElementById("dashboardPhoneNumber").value = user.phone_number || '';
                document.getElementById("dashboardRoleId").value = user.role_id;
                document.getElementById("dashboardIsActive").checked = !!user.is_active;
            }
        }
    } else {
        // Create mode
        modalTitle.textContent = "Add New User";
        document.getElementById("dashboardUserId").value = '';
        document.getElementById("dashboardIsActive").checked = true;
        if (passwordLabel) {
            passwordLabel.textContent = "*";
        }
        if (passwordInput) {
            passwordInput.required = true;
            passwordInput.placeholder = "Minimum 6 characters";
        }
    }
    
    // Show modal
    modal.classList.add("active");
    console.log("Modal displayed");
}
function closeDashboardUserModal() {
    console.log("closeDashboardUserModal called");
    const modal = document.getElementById("dashboardUserModal");
    if (modal) {
        modal.classList.remove("active");
    }
    const form = document.getElementById("dashboardUserForm");
    if (form) {
        form.reset();
    }
}

function submitDashboardUserForm(event) {
    if (event) {
        event.preventDefault();
    }
    
    console.log("submitDashboardUserForm called");
    
    const form = document.getElementById("dashboardUserForm");
    if (!form) {
        console.error("dashboardUserForm not found");
        return;
    }
    
    // Validate form
    if (!validateForm(form)) {
        console.error("Form validation failed");
        return;
    }
    
    const userId = document.getElementById("dashboardUserId").value;
    const roleId = document.getElementById("dashboardRoleId").value;
    const username = document.getElementById("dashboardUsername").value;
    const email = document.getElementById("dashboardEmail").value;
    const fullName = document.getElementById("dashboardFullName").value;
    const phoneNumber = document.getElementById("dashboardPhoneNumber").value;
    const password = document.getElementById("dashboardPassword").value;
    const isActive = document.getElementById("dashboardIsActive").checked;
    
    console.log("Form data:", {userId, roleId, username, email, fullName});
    
    // Validate required fields
    if (!roleId || !username || !email || !fullName) {
        showNotification('Please fill all required fields', 'error');
        return;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showNotification('Invalid email format', 'error');
        return;
    }
    
    // If creating new user, password is required
    if (!userId && !password) {
        showNotification('Password is required for new users', 'error');
        return;
    }
    
    // If password provided, validate length
    if (password && password.length < 6) {
        showNotification('Password must be at least 6 characters', 'error');
        return;
    }
    
    // Build payload
    const payload = {
        role_id: parseInt(roleId),
        username: username,
        email: email,
        full_name: fullName,
        phone_number: phoneNumber,
        is_active: isActive
    };
    
    // Add user_id if editing
    if (userId) {
        payload.user_id = parseInt(userId);
    }
    
    // Add password if provided
    if (password) {
        payload.password = password;
    }
    
    console.log("Payload to send:", payload);
    
    const saveUserBtn = document.getElementById('saveDashboardUserBtn');
    showLoading(saveUserBtn);
    
    apiCall('admin/save_user.php', 'POST', payload)
    .then(response => {
        hideLoading(saveUserBtn);
        
        console.log("API response:", response);
        
        if (response && response.success) {
            showNotification(response.message || 'User saved successfully', 'success');
            closeDashboardUserModal();
            loadUsers();
        } else {
            showNotification('Error: ' + (response?.message || 'Failed to save user'), 'error');
            console.error("API error response:", response);
        }
    })
    .catch(error => {
        hideLoading(saveUserBtn);
        console.error('Error saving user:', error);
        showNotification('Error saving user: ' + (error?.message || 'Network error'), 'error');
    });
}

/* --------------------------------------------------------------------------
   Coming Soon Modals
   -------------------------------------------------------------------------- */
// =========================================================================
// CATEGORY MANAGEMENT
// =========================================================================

async function loadCategories() {
    try {
        console.log('loadCategories() - requesting data from backend');
        // Resolve and log the full URL used for the API call
        try {
            const resolved = resolveEndpoint('admin/get_categories.php');
            console.log('Resolved get_categories endpoint:', resolved);
        } catch (e) {
            console.warn('Could not resolve endpoint:', e);
        }

        // Primary attempt: admin endpoint (requires role)
        let result = null;
        try {
            result = await apiCall('admin/get_categories.php', 'GET');
            console.log('loadCategories() - admin endpoint response:', result);
        } catch (err) {
            console.warn('loadCategories() - admin endpoint failed:', err.message || err);
        }

        // If admin endpoint didn't return usable data, fall back to public customer endpoint
        if (!result || !result.success || !Array.isArray(result.data)) {
            console.log('loadCategories() - falling back to customer/get_meals.php?categories=true');
            try {
                result = await apiCall('customer/get_meals.php?categories=true', 'GET');
                console.log('loadCategories() - fallback response:', result);
            } catch (err) {
                console.error('loadCategories() - fallback failed:', err.message || err);
            }
        }

        if (result && result.success) {
            adminState.categories = result.data || [];
            console.log(`loadCategories() - ${adminState.categories.length} categories loaded`);
            renderCategories();
        } else {
            console.warn('loadCategories() - API error or no data returned');
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

function filterCategories() {
    const searchInput = document.getElementById('categoriesSearchInput');
    if (!searchInput) return;
    
    const searchTerm = searchInput.value.toLowerCase().trim();
    const container = document.getElementById('categoriesTableBody');
    if (!container) return;
    
    if (!adminState.categories || adminState.categories.length === 0) {
        return;
    }
    
    if (!searchTerm) {
        // Show all categories
        renderCategories();
        return;
    }
    
    const filtered = adminState.categories.filter(cat => 
        cat.category_name.toLowerCase().includes(searchTerm) ||
        (cat.description || '').toLowerCase().includes(searchTerm)
    );
    
    if (filtered.length === 0) {
        container.innerHTML = '<tr><td colspan="5" class="text-center">No categories found matching "' + escapeHtml(searchTerm) + '"</td></tr>';
        return;
    }
    
    container.innerHTML = filtered.map(cat => `
        <tr>
            <td>${escapeHtml(cat.category_name)}</td>
            <td>${escapeHtml(cat.description || '-')}</td>
            <td><span class="badge ${cat.is_active ? 'badge-success' : 'badge-danger'}">${cat.is_active ? 'Active' : 'Inactive'}</span></td>
            <td>${cat.display_order}</td>
            <td class="actions">
                <button class="btn-icon btn-secondary" onclick="editCategory(${cat.category_id})" title="Edit">✎</button>
                <button class="btn-icon btn-danger" onclick="deleteCategory(${cat.category_id}, '${escapeHtml(cat.category_name)}')" title="Delete">🗑</button>
            </td>
        </tr>
    `).join('');
}

// Expose category functions and utilities to window object
window.loadCategories = loadCategories;
window.renderCategories = renderCategories;
window.openCategoryModal = openCategoryModal;
window.closeCategoryModal = closeCategoryModal;
window.submitCategoryForm = submitCategoryForm;
window.deleteCategory = deleteCategory;
window.editCategory = editCategory;
window.filterCategories = filterCategories;

// Expose meal functions to window object
window.loadMeals = loadMeals;
window.renderMeals = renderMeals;
window.openMealModal = openMealModal;
window.closeMealModal = closeMealModal;
window.submitMealForm = submitMealForm;
window.deleteMeal = deleteMeal;
window.filterMeals = filterMeals;
window.loadMealCategories = loadMealCategories;
window.updateMealStats = updateMealStats;
window.handleMealImageChange = handleMealImageChange;

// Expose user functions to window object
window.loadUsers = loadUsers;
window.renderUsers = renderUsers;
window.openUserModal = openUserModal;
window.closeUserModal = closeUserModal;
window.submitUserForm = submitUserForm;
window.deleteUser = deleteUser;
window.editUser = editUser;
window.filterUsers = filterUsers;
window.filterByRole = filterByRole;

window.escapeHtml = escapeHtml;
window.adminState = adminState;

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


