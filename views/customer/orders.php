<?php
require_once __DIR__ . '/../../config/db.php';

$pageTitle = "My Orders - Aunt Joy's Restaurant";
$customCSS = "customer.css";
$showNav = true;
$showFooter = true;
$bodyClass = "customer-page";

include '../templates/header.php';
?>

<div class="orders-page">
    <div class="container">
        <!-- Page Header -->
        <div class="orders-page-header">
            <div class="header-content">
                <h1 class="page-title">My Orders</h1>
                <p class="page-subtitle">Track and manage all your orders in one place</p>
            </div>
            <a href="/aunt_joy/views/customer/menu.php" class="btn btn-primary btn-icon-right">
                + New Order
            </a>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs-container">
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterOrders('all')">
                    <span class="filter-icon">📋</span>
                    All Orders
                </button>
                <button class="filter-tab" onclick="filterOrders('Pending')">
                    <span class="filter-icon">⏳</span>
                    Pending
                </button>
                <button class="filter-tab" onclick="filterOrders('Preparing')">
                    <span class="filter-icon">👨‍🍳</span>
                    Preparing
                </button>
                <button class="filter-tab" onclick="filterOrders('Out for Delivery')">
                    <span class="filter-icon">🚚</span>
                    Out for Delivery
                </button>
                <button class="filter-tab" onclick="filterOrders('Delivered')">
                    <span class="filter-icon">✅</span>
                    Delivered
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="loading-state-container">
            <div class="spinner"></div>
            <p class="loading-text">Loading your orders...</p>
        </div>

        <!-- Orders Grid -->
        <div id="ordersContainer" class="orders-grid">
            <!-- Orders will be loaded here -->
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="empty-state-container" style="display: none;">
            <div class="empty-state-icon">📦</div>
            <h3 class="empty-state-title">No orders yet</h3>
            <p class="empty-state-text">You haven't placed any orders yet. Browse our menu and place your first order!</p>
            <a href="/aunt_joy/views/customer/menu.php" class="btn btn-primary btn-lg">
                Browse Menu
            </a>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content modal-large">
        <span class="modal-close" onclick="closeOrderModal()">&times;</span>
        <div id="orderModalBody">
            <!-- Order details will be loaded here -->
        </div>
    </div>
</div>

<script>
let allOrders = [];
let currentFilter = 'all';

// Check if user is logged in on page load
document.addEventListener('DOMContentLoaded', function() {
    if (!window.AUNT_JOY?.isLoggedIn) {
        showNotification('Please log in to view your orders', 'warning');
        setTimeout(() => {
            window.location.href = '/aunt_joy/views/auth/login.php?next=orders';
        }, 900);
        return;
    }
    
    loadOrders();
    
    // Auto-refresh every 15 seconds to show status updates
    setInterval(loadOrders, 15000);
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
    document.getElementById('loadingState').style.display = 'block';
    document.getElementById('ordersContainer').style.display = 'none';
    document.getElementById('emptyState').style.display = 'none';
    
    try {
        const response = await fetch('/aunt_joy/controllers/customer/get_orders.php');
        const result = await response.json();
        
        if (result.success) {
            allOrders = result.data;
            renderOrders();
        } else {
            showNotification(result.message || 'Failed to load orders', 'error');
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        showNotification('Failed to load orders', 'error');
    } finally {
        document.getElementById('loadingState').style.display = 'none';
    }
}

// Render orders
function renderOrders() {
    const container = document.getElementById('ordersContainer');
    const emptyState = document.getElementById('emptyState');
    
    // Filter orders
    const filteredOrders = currentFilter === 'all' 
        ? allOrders 
        : allOrders.filter(order => order.order_status === currentFilter);
    
    if (filteredOrders.length === 0) {
        container.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }
    
    container.style.display = 'block';
    emptyState.style.display = 'none';
    
    container.innerHTML = filteredOrders.map(order => `
        <div class="order-card-item">
            <div class="order-card-header">
                <div class="order-info">
                    <h3 class="order-number">${order.order_number}</h3>
                    <p class="order-date">
                        📅 ${new Date(order.order_date).toLocaleDateString('en-GB', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}
                    </p>
                </div>
                <span class="status-badge status-${order.order_status.toLowerCase().replace(/ /g, '-')}">
                    ${order.order_status}
                </span>
            </div>
            
            <div class="order-card-body">
                <div class="order-detail-row">
                    <span class="detail-label">📍 Delivery Address</span>
                    <span class="detail-value">${order.delivery_address}</span>
                </div>
                <div class="order-detail-row">
                    <span class="detail-label">💰 Total Amount</span>
                    <span class="detail-value amount-highlight">${order.total_amount_formatted}</span>
                </div>
            </div>
            
            <div class="order-card-actions">
                <button class="btn btn-secondary btn-sm" onclick="viewOrderDetails(${order.order_id})">
                    View Details
                </button>
                ${order.order_status === 'Delivered' ? `
                    <button class="btn btn-primary btn-sm" onclick="reorder(${order.order_id})">
                        Order Again
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// Filter orders
function filterOrders(status) {
    currentFilter = status;
    
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');
    
    renderOrders();
}

// View order details
async function viewOrderDetails(orderId) {
    try {
        const response = await fetch(`/aunt_joy/controllers/customer/get_orders.php?order_id=${orderId}`);
        const result = await response.json();
        
        if (result.success) {
            showOrderModal(result.data);
        } else {
            showNotification('Failed to load order details', 'error');
        }
    } catch (error) {
        console.error('Error loading order details:', error);
        showNotification('Failed to load order details', 'error');
    }
}

// Show order modal
function showOrderModal(order) {
    const modal = document.getElementById('orderModal');
    const body = document.getElementById('orderModalBody');
    
    body.innerHTML = `
        <h2>Order Details</h2>
        
        <div class="order-details-header">
            <div>
                <h3>${order.order_number}</h3>
                <p class="text-muted">${new Date(order.order_date).toLocaleString('en-GB')}</p>
            </div>
            <span class="status-badge status-${order.order_status.toLowerCase().replace(/ /g, '-')}">
                ${order.order_status}
            </span>
        </div>
        
        <div class="details-section">
            <h4>Delivery Information</h4>
            <p><strong>Address:</strong> ${order.delivery_address}</p>
            <p><strong>Contact:</strong> ${order.contact_number}</p>
            ${order.special_instructions ? `<p><strong>Instructions:</strong> ${order.special_instructions}</p>` : ''}
        </div>
        
        <div class="details-section">
            <h4>Order Items</h4>
            <div class="order-items-list">
                ${order.items.map(item => `
                    <div class="order-item-detail">
                        <div>
                            <strong>${item.meal_name}</strong>
                            <span class="text-muted">× ${item.quantity}</span>
                        </div>
                        <span>MK ${item.subtotal.toFixed(2)}</span>
                    </div>
                `).join('')}
            </div>
        </div>
        
        <div class="details-section">
            <div class="order-summary-detail">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>${order.subtotal_formatted}</span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee</span>
                    <span>${order.delivery_fee_formatted}</span>
                </div>
                ${order.discount_amount > 0 ? `
                    <div class="summary-row">
                        <span>Discount</span>
                        <span class="text-success">-${order.discount_amount_formatted}</span>
                    </div>
                ` : ''}
                <div class="summary-row total">
                    <span>Total</span>
                    <span>${order.total_amount_formatted}</span>
                </div>
            </div>
        </div>
    `;
    
    modal.style.display = 'block';
}

// Close order modal
function closeOrderModal() {
    document.getElementById('orderModal').style.display = 'none';
}

// Reorder
function reorder(orderId) {
    showNotification('Reorder feature coming soon!', 'info');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('orderModal');
    if (event.target === modal) {
        closeOrderModal();
    }
}
</script>

<?php include '../templates/footer.php'; ?>