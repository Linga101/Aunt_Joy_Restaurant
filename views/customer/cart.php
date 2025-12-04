<?php
require_once '../../config/db.php';
requireAuth();
requireRole('Customer');

$pageTitle = "Shopping Cart - Aunt Joy's Restaurant";
$customCSS = "customer.css";
$customJS = "cart.js";
$showNav = true;
$showFooter = true;
$bodyClass = "customer-page";

include '../templates/header.php';
?>

<div class="cart-page">
    <div class="container">
        <!-- Page Header -->
        <div class="cart-page-header">
            <div class="header-content">
                <h1 class="page-title">Shopping Cart</h1>
                <p class="page-subtitle">Review your items and complete your order</p>
            </div>
            <a href="/aunt_joy/views/customer/menu.php" class="btn btn-secondary btn-icon-left">
                ← Continue Shopping
            </a>
        </div>

        <!-- Main Cart Layout -->
        <div class="cart-main-layout">
            <!-- Left: Cart Items Section -->
            <section class="cart-items-section">
                <!-- Section Header -->
                <div class="section-header-bar">
                    <div>
                        <h2 class="section-title">Order Items</h2>
                        <span class="item-count-badge">
                            <span id="itemCount">0</span> items
                        </span>
                    </div>
                    <button class="btn-link-danger" onclick="clearCart()" title="Clear all items">
                        🗑️ Clear Cart
                    </button>
                </div>

                <!-- Cart Items Container -->
                <div id="cartItemsContainer" class="cart-items-list">
                    <!-- Items will be rendered here by JavaScript -->
                </div>

                <!-- Empty Cart State -->
                <div id="emptyCartState" class="empty-state-container" style="display: none;">
                    <div class="empty-state-icon">🛒</div>
                    <h3 class="empty-state-title">Your cart is empty</h3>
                    <p class="empty-state-text">Add some delicious meals from our menu to get started</p>
                    <a href="/aunt_joy/views/customer/menu.php" class="btn btn-primary btn-lg">
                        Browse Menu
                    </a>
                </div>
            </section>

            <!-- Right: Order Summary & Checkout Section -->
            <aside class="order-summary-sidebar">
                <!-- Order Summary Card -->
                <div class="summary-card">
                    <h2 class="summary-card-title">Order Summary</h2>
                    
                    <!-- Price Breakdown -->
                    <div class="price-breakdown">
                        <div class="price-row">
                            <span class="price-label">Subtotal</span>
                            <span class="price-value" id="subtotal">MK 0.00</span>
                        </div>
                        <div class="price-row">
                            <span class="price-label">Delivery Fee</span>
                            <span class="price-value" id="deliveryFee">MK 500.00</span>
                        </div>
                        <div class="price-row discount-row">
                            <span class="price-label">Discount</span>
                            <span class="price-value text-success" id="discount">-MK 0.00</span>
                        </div>
                        <div class="divider"></div>
                        <div class="price-row total-row">
                            <span class="price-label-total">Total Amount</span>
                            <span class="price-value-total" id="total">MK 0.00</span>
                        </div>
                    </div>

                    <!-- Promo Code Section -->
                    <div class="promo-code-section">
                        <label class="promo-label">Promo Code</label>
                        <div class="promo-input-wrapper">
                            <input 
                                type="text" 
                                id="promoCode" 
                                placeholder="Enter code (e.g., WELCOME10)"
                                class="form-control promo-input"
                                autocomplete="off"
                            >
                            <button class="btn btn-secondary btn-sm" onclick="applyPromo()">
                                Apply
                            </button>
                        </div>
                        <p class="promo-hint">Try: WELCOME10, SAVE500, or FIRSTORDER</p>
                    </div>
                </div>

                <!-- Delivery Details Card -->
                <div class="delivery-card">
                    <h3 class="card-title">Delivery Information</h3>
                    
                    <div class="form-group-large">
                        <label class="form-label" for="deliveryAddress">
                            Delivery Address <span class="required">*</span>
                        </label>
                        <textarea 
                            id="deliveryAddress" 
                            placeholder="Enter your detailed delivery address in Mzuzu (e.g., Chipata, near Post Office)"
                            class="form-control form-textarea"
                            rows="4"
                            required
                        ></textarea>
                        <p class="form-hint">We deliver within Mzuzu city</p>
                    </div>

                    <div class="form-group-large">
                        <label class="form-label" for="contactNumber">
                            Contact Number <span class="required">*</span>
                        </label>
                        <input 
                            type="tel" 
                            id="contactNumber" 
                            placeholder="+265 999 123 456"
                            class="form-control"
                            required
                        >
                        <p class="form-hint">We'll use this to confirm your order</p>
                    </div>

                    <div class="form-group-large">
                        <label class="form-label" for="specialInstructions">
                            Special Instructions <span class="optional">(Optional)</span>
                        </label>
                        <input 
                            type="text" 
                            id="specialInstructions" 
                            placeholder="e.g., No onions, extra spicy, ring doorbell twice"
                            class="form-control"
                        >
                        <p class="form-hint">Any special requests for your order</p>
                    </div>
                </div>

                <!-- Checkout Section -->
                <div class="checkout-section">
                    <button 
                        id="checkoutBtn" 
                        class="btn btn-primary btn-lg btn-block"
                        onclick="checkout()"
                        disabled
                    >
                        🛍️ Place Order
                    </button>
                    
                    <div class="security-badge">
                        <span class="security-icon">🔒</span>
                        <span class="security-text">Secure Checkout</span>
                    </div>
                    
                    <p class="terms-text">
                        By placing an order, you agree to our 
                        <a href="#" class="link">terms and conditions</a>
                    </p>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
const DELIVERY_FEE = 500;
let discountAmount = 0;

// Load cart on page load
document.addEventListener('DOMContentLoaded', function() {
    renderCart();
    updateSummary();
    
    // Setup event delegation for cart item buttons (only once, on initial load)
    setupCartEventDelegation();
});

// Setup event delegation for cart buttons - this only runs ONCE on page load
function setupCartEventDelegation() {
    const container = document.getElementById('cartItemsContainer');
    if (!container) return;
    
    // Delegate quantity increase clicks
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-qty-increase')) {
            const index = parseInt(e.target.dataset.itemIndex);
            updateQuantity(index, 1);
        }
    });
    
    // Delegate quantity decrease clicks
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-qty-decrease')) {
            const index = parseInt(e.target.dataset.itemIndex);
            updateQuantity(index, -1);
        }
    });
    
    // Delegate remove item clicks
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-cart-item')) {
            const index = parseInt(e.target.dataset.itemIndex);
            removeFromCart(index);
        }
    });
}

// Listen for cart updates from cart.js (when quantity changes, items added, etc.)
window.addEventListener('cartUpdated', function() {
    renderCart();
    updateSummary();
});

// Render cart items
function renderCart() {
    const container = document.getElementById('cartItemsContainer');
    const emptyState = document.getElementById('emptyCartState');
    const cart = getCart();
    
    document.getElementById('itemCount').textContent = cart.length;
    
    if (cart.length === 0) {
        container.style.display = 'none';
        emptyState.style.display = 'block';
        document.getElementById('checkoutBtn').disabled = true;
        return;
    }
    
    container.style.display = 'block';
    emptyState.style.display = 'none';
    document.getElementById('checkoutBtn').disabled = false;
    
    container.innerHTML = cart.map((item, index) => `
        <div class="cart-item-card" data-item-index="${index}">
            <div class="cart-item-media">
                <div class="item-image-wrapper">
                    ${item.image_url && item.image_url.trim() ? `
                        <img src="/aunt_joy/${item.image_url}" alt="${item.meal_name}" class="item-image">
                    ` : `
                        <div class="item-image-placeholder">🍽️</div>
                    `}
                </div>
            </div>
            
            <div class="cart-item-content">
                <div class="item-header">
                    <div>
                        <h3 class="item-name">${item.meal_name}</h3>
                        <p class="item-price">MK ${item.unit_price.toFixed(2)} each</p>
                    </div>
                    <button class="btn-remove-item btn-remove-cart-item" data-item-index="${index}" title="Remove item" aria-label="Remove ${item.meal_name}">
                        ✕
                    </button>
                </div>
                
                <div class="item-footer">
                    <div class="quantity-selector">
                        <button class="qty-btn btn-qty-decrease" data-item-index="${index}" aria-label="Decrease quantity">−</button>
                        <span class="qty-display">${item.quantity}</span>
                        <button class="qty-btn btn-qty-increase" data-item-index="${index}" aria-label="Increase quantity">+</button>
                    </div>
                    <div class="item-subtotal">
                        MK ${(item.unit_price * item.quantity).toFixed(2)}
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Update summary
function updateSummary() {
    const cart = getCart();
    const subtotal = cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
    const deliveryFee = cart.length > 0 ? DELIVERY_FEE : 0;
    const total = subtotal + deliveryFee - discountAmount;
    
    document.getElementById('subtotal').textContent = 'MK ' + subtotal.toFixed(2);
    document.getElementById('deliveryFee').textContent = 'MK ' + deliveryFee.toFixed(2);
    document.getElementById('discount').textContent = '-MK ' + discountAmount.toFixed(2);
    document.getElementById('total').textContent = 'MK ' + total.toFixed(2);
}

// Attach event listeners for cart item buttons
function attachCartEventListeners() {
    // This function is no longer used - we use event delegation instead
    console.warn('attachCartEventListeners() is deprecated. Event delegation is now used.');
}

// Update quantity
function updateQuantity(index, change) {
    try {
        const cart = getCart();
        if (cart[index]) {
            cart[index].quantity += change;
            
            if (cart[index].quantity < 1) {
                cart[index].quantity = 1;
                return; // Don't go below 1
            }
            
            if (cart[index].quantity > 10) {
                showNotification('Maximum quantity is 10 per item', 'warning');
                cart[index].quantity = 10;
            }
            
            saveCart(cart);
            renderCart();
            updateSummary();
            updateCartCount(); // Update cart badge in header
        }
    } catch (error) {
        console.error('Error updating quantity:', error);
        showNotification('Error updating quantity. Please try again.', 'error');
    }
}

// Remove from cart
function removeFromCart(index) {
    const cart = getCart();
    if (confirm(`Remove ${cart[index].meal_name} from cart?`)) {
        cart.splice(index, 1);
        saveCart(cart);
        discountAmount = 0; // Reset discount when items removed
        renderCart();
        updateSummary();
        showNotification('Item removed from cart', 'success');
    }
}

// Clear cart
function clearCart() {
    if (getCart().length === 0) return;
    
    if (confirm('Are you sure you want to clear your cart?')) {
        const storageKey = getCartStorageKey();
        if (storageKey) {
            localStorage.removeItem(storageKey);
        }
        discountAmount = 0; // Reset discount when cart cleared
        document.getElementById('promoCode').value = ''; // Clear promo code input
        renderCart();
        updateSummary();
        showNotification('Cart cleared', 'success');
    }
}

// Apply promo code
function applyPromo() {
    const promoCode = document.getElementById('promoCode').value.trim().toUpperCase();
    
    if (!promoCode) {
        showNotification('Please enter a promo code', 'warning');
        return;
    }
    
    // Sample promo codes
    const promoCodes = {
        'WELCOME10': 10,  // 10% discount
        'SAVE500': 500,   // Fixed 500 discount
        'FIRSTORDER': 15  // 15% discount
    };
    
    if (promoCodes[promoCode] !== undefined) {
        const subtotal = getCart().reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
        
        if (promoCode === 'SAVE500') {
            discountAmount = promoCodes[promoCode];
        } else {
            discountAmount = Math.round(subtotal * (promoCodes[promoCode] / 100));
        }
        
        updateSummary();
        showNotification(`Promo code applied! You saved MK ${discountAmount.toFixed(2)}`, 'success');
    } else {
        showNotification('Invalid promo code', 'error');
    }
}

// Checkout
async function checkout() {
    const address = document.getElementById('deliveryAddress').value.trim();
    const phone = document.getElementById('contactNumber').value.trim();
    const instructions = document.getElementById('specialInstructions').value.trim();
    
    if (!address) {
        showNotification('Please enter your delivery address', 'warning');
        document.getElementById('deliveryAddress').focus();
        return;
    }
    
    if (!phone) {
        showNotification('Please enter your contact number', 'warning');
        document.getElementById('contactNumber').focus();
        return;
    }
    
    // Basic phone validation (Malawi format or flexible)
    if (!/^\+?265\d{7,9}$|^\d{10,}$/.test(phone.replace(/\s+/g, ''))) {
        showNotification('Please enter a valid contact number', 'warning');
        document.getElementById('contactNumber').focus();
        return;
    }
    
    const cart = getCart();
    if (cart.length === 0) {
        showNotification('Your cart is empty', 'warning');
        return;
    }
    
    const subtotal = cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
    
    const orderData = {
        delivery_address: address,
        contact_number: phone,
        special_instructions: instructions,
        subtotal: subtotal,
        delivery_fee: DELIVERY_FEE,
        discount_amount: discountAmount,
        total_amount: subtotal + DELIVERY_FEE - discountAmount,
        items: cart.map(item => ({
            meal_id: item.meal_id,
            meal_name: item.meal_name,
            quantity: item.quantity,
            unit_price: item.unit_price,
            subtotal: item.unit_price * item.quantity
        }))
    };
    
    const btn = document.getElementById('checkoutBtn');
    const originalText = btn.textContent;
    btn.textContent = 'Processing...';
    btn.disabled = true;
    
    try {
        const response = await fetch('/aunt_joy/controllers/customer/place_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`Order ${result.data.order_number} placed successfully!`, 'success');
            const storageKey = getCartStorageKey();
            if (storageKey) {
                localStorage.removeItem(storageKey);
            }
            
            setTimeout(() => {
                window.location.href = '/aunt_joy/views/customer/orders.php';
            }, 2000);
        } else {
            showNotification(result.message || 'Failed to place order', 'error');
            btn.textContent = originalText;
            btn.disabled = false;
        }
    } catch (error) {
        showNotification('Failed to place order. Please try again.', 'error');
        btn.textContent = originalText;
        btn.disabled = false;
    }
}
</script>

<?php include '../templates/footer.php'; ?>