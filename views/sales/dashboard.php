<?php
require_once __DIR__ . '/../../config/db.php';
requireAuth();
requireRole('Sales Personnel', 'Administrator');

$pageTitle = "Sales Dashboard - Aunt Joy's Restaurant";
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
            <div>
                <h1>Order Management</h1>
                <div class="live-indicator">
                    <div class="live-dot"></div>
                    <span>Live Updates</span>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="loadOrders()">
                    🔄 Refresh
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <h3 id="totalOrders">0</h3>
                    <p>Total Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3 id="pendingOrders">0</h3>
                    <p>Pending</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👨‍🍳</div>
                <div class="stat-content">
                    <h3 id="preparingOrders">0</h3>
                    <p>Preparing</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🚚</div>
                <div class="stat-content">
                    <h3 id="deliveryOrders">0</h3>
                    <p>Out for Delivery</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3 id="deliveredOrders">0</h3>
                    <p>Delivered Today</p>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="filterOrders('all')">
                All Orders
            </button>
            <button class="filter-tab" onclick="filterOrders('Pending')">
                Pending
            </button>
            <button class="filter-tab" onclick="filterOrders('Preparing')">
                Preparing
            </button>
            <button class="filter-tab" onclick="filterOrders('Out for Delivery')">
                Out for Delivery
            </button>
            <button class="filter-tab" onclick="filterOrders('Delivered')">
                Delivered
            </button>
        </div>

        <!-- Orders Grid -->
        <div id="ordersGrid" class="orders-grid">
            <!-- Orders will be loaded here -->
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="loading-state" style="display: none;">
            <div class="spinner"></div>
            <p>Loading orders...</p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="empty-state" style="display: none;">
            <div class="empty-icon">📭</div>
            <h3>No orders found</h3>
            <p>There are no orders at the moment.</p>
        </div>
    </main>
</div>

<script>
let allOrders = [];
let currentFilter = 'all';

// Load orders on page load
document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
    
    // Auto-refresh every 10 seconds for real-time updates
    setInterval(loadOrders, 10000);
});

// Listen for visibility change to pause/resume polling when tab is not active
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        console.log('Tab inactive - pausing order updates');
    } else {
        console.log('Tab active - resuming order updates');
        loadOrders();
    }
});

// Load orders
async function loadOrders() {
    console.log("loadOrders called");
    try {
        console.log("Fetching from /aunt_joy/controllers/sales/get_orders.php");
        
        // Use fetch with proper headers for session preservation
        const response = await fetch('/aunt_joy/controllers/sales/get_orders.php', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });
        
        console.log("Response status:", response.status);
        console.log("Response ok:", response.ok);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        console.log("API result:", result);
        
        if (result && result.success) {
            allOrders = result.data || [];
            console.log("Orders loaded:", allOrders.length);
            updateStats();
            renderOrders();
        } else {
            console.error('API Error:', result?.message || 'Failed to load orders');
            showNotification(result?.message || 'Failed to load orders', 'error');
            const grid = document.getElementById('ordersGrid');
            if (grid) {
                grid.style.display = 'none';
                const emptyState = document.getElementById('emptyState');
                if (emptyState) {
                    emptyState.style.display = 'block';
                    emptyState.innerHTML = '<div class="empty-icon">⚠️</div><h3>Error loading orders</h3><p>' + (result?.message || 'Failed to load orders') + '</p>';
                }
            }
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        showNotification('Error loading orders: ' + error.message, 'error');
        const grid = document.getElementById('ordersGrid');
        if (grid) {
            grid.style.display = 'none';
            const emptyState = document.getElementById('emptyState');
            if (emptyState) {
                emptyState.style.display = 'block';
                emptyState.innerHTML = '<div class="empty-icon">⚠️</div><h3>Error loading orders</h3><p>' + error.message + '</p>';
            }
        }
    }
}

// Update stats
function updateStats() {
    document.getElementById('totalOrders').textContent = allOrders.length;
    document.getElementById('pendingOrders').textContent = 
        allOrders.filter(o => o.order_status === 'Pending').length;
    document.getElementById('preparingOrders').textContent = 
        allOrders.filter(o => o.order_status === 'Preparing').length;
    document.getElementById('deliveryOrders').textContent = 
        allOrders.filter(o => o.order_status === 'Out for Delivery').length;
    document.getElementById('deliveredOrders').textContent = 
        allOrders.filter(o => o.order_status === 'Delivered' && 
            new Date(o.order_date).toDateString() === new Date().toDateString()).length;
}

// Render orders
function renderOrders() {
    console.log("renderOrders called, currentFilter:", currentFilter);
    const grid = document.getElementById('ordersGrid');
    const emptyState = document.getElementById('emptyState');
    
    const filtered = currentFilter === 'all' 
        ? allOrders 
        : allOrders.filter(o => o.order_status === currentFilter);
    
    console.log("Total orders:", allOrders.length, "Filtered:", filtered.length);
    
    if (filtered.length === 0) {
        console.log("No orders to display");
        grid.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }
    
    console.log("Rendering", filtered.length, "orders");
    grid.style.display = 'grid';
    emptyState.style.display = 'none';
    
    grid.innerHTML = filtered.map(order => `
        <div class="order-card">
            <div class="order-header">
                <div>
                    <h3>${order.order_number}</h3>
                    <p class="order-time">⏰ ${new Date(order.order_date).toLocaleString('en-GB')}</p>
                </div>
                <span class="status-badge status-${order.order_status.toLowerCase().replace(/ /g, '-')}">
                    ${order.order_status}
                </span>
            </div>
            
            <div class="order-body">
                <div class="order-info-item">
                    <span class="info-icon">👤</span>
                    <span>${order.customer_name}</span>
                </div>
                <div class="order-info-item">
                    <span class="info-icon">📞</span>
                    <span>${order.contact_number}</span>
                </div>
                <div class="order-info-item">
                    <span class="info-icon">📍</span>
                    <span>${order.delivery_address}</span>
                </div>
                ${order.special_instructions ? `
                    <div class="order-info-item">
                        <span class="info-icon">📝</span>
                        <span>${order.special_instructions}</span>
                    </div>
                ` : ''}
                <div class="order-info-item">
                    <span class="info-icon">💰</span>
                    <strong>${order.total_amount_formatted}</strong>
                </div>
                <div class="order-info-item">
                    <span class="info-icon">🍽️</span>
                    <span>${order.item_count} items</span>
                </div>
            </div>
            
            <div class="order-footer">
                ${getStatusButton(order.order_id, order.order_status)}
            </div>
        </div>
    `).join('');
}

// Get status button based on current status
function getStatusButton(orderId, currentStatus) {
    if (currentStatus === 'Pending') {
        return `
            <button class="btn btn-primary btn-block" onclick="updateStatus(${orderId}, 'Preparing')">
                👨‍🍳 Mark as Preparing
            </button>
        `;
    } else if (currentStatus === 'Preparing') {
        return `
            <button class="btn btn-primary btn-block" onclick="updateStatus(${orderId}, 'Out for Delivery')">
                🚚 Out for Delivery
            </button>
        `;
    } else if (currentStatus === 'Out for Delivery') {
        return `
            <button class="btn btn-success btn-block" onclick="updateStatus(${orderId}, 'Delivered')">
                ✅ Mark as Delivered
            </button>
        `;
    } else {
        return `
            <button class="btn btn-secondary btn-block" onclick="viewOrderDetails(${orderId})">
                👁️ View Details
            </button>
        `;
    }
}

// Update order status
async function updateStatus(orderId, newStatus) {
    console.log(`updateStatus called: orderId=${orderId}, newStatus=${newStatus}`);
    
    if (!confirm(`Update order status to "${newStatus}"?`)) {
        return;
    }
    
    try {
        const payload = {
            order_id: orderId,
            new_status: newStatus,
            notes: `Status updated to ${newStatus}`
        };
        
        console.log("Sending payload:", payload);
        
        const response = await fetch('/aunt_joy/controllers/sales/update_status.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });
        
        console.log("Response status:", response.status);
        console.log("Response ok:", response.ok);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        console.log("API result:", result);
        
        if (result && result.success) {
            showNotification(`Order status updated to ${newStatus}`, 'success');
            await loadOrders();
        } else {
            showNotification(result?.message || 'Failed to update status', 'error');
            console.error('Status update error:', result);
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showNotification('Error updating status: ' + error.message, 'error');
    }
}

// Filter orders
function filterOrders(status) {
    console.log(`filterOrders called with status: ${status}`);
    currentFilter = status;
    
    // Update active tab styling
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Find and mark the clicked tab as active
    const tabs = Array.from(document.querySelectorAll('.filter-tab'));
    let targetTab = null;
    
    if (status === 'all') {
        targetTab = tabs[0];
    } else {
        targetTab = tabs.find(tab => tab.textContent.includes(status));
    }
    
    if (targetTab) {
        targetTab.classList.add('active');
    }
    
    console.log("Current filter set to:", currentFilter);
    renderOrders();
}

// View order details
async function viewOrderDetails(orderId) {
    try {
        console.log(`viewOrderDetails called for orderId: ${orderId}`);
        
        const response = await fetch(`/aunt_joy/controllers/sales/get_orders.php?order_id=${orderId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });
        
        console.log("Response status:", response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        console.log("Order details result:", result);
        
        if (result.success) {
            // Show a more formatted modal instead of alert
            showNotification('Order details loaded', 'success');
            // TODO: Implement proper modal for order details
            console.log('Order Data:', result.data);
        } else {
            showNotification(result?.message || 'Failed to load order details', 'error');
        }
    } catch (error) {
        console.error('Error loading order details:', error);
        showNotification('Failed to load order details: ' + error.message, 'error');
    }
}
</script>

<?php include '../templates/footer.php'; ?>