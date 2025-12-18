/**
 * Shopping Cart JavaScript
 * Manages cart operations
 */

function hasCartAccess(showPrompt = false) {
    if (showPrompt) {
        const loggedIn = window.AUNT_JOY?.isLoggedIn;
        const role = window.AUNT_JOY?.role;
        const allowed = Boolean(loggedIn && role === 'Customer');
        if (!allowed) {
            showNotification('Please log in as a customer to use the cart.', 'info');
            setTimeout(() => {
                openAuthModal('login');
            }, 300);
        }
        return allowed;
    }
    return true; // Always allow access when showPrompt is false
}

function getCartStorageKey() {
    const userId = window.AUNT_JOY?.userId || 'guest';
    return `auntJoyCart_${userId}`;
}

/**
 * Get cart from localStorage
 * @return {Array} Cart items
 */
function getCart() {
    const storageKey = getCartStorageKey();
    const cart = localStorage.getItem(storageKey);
    return cart ? JSON.parse(cart) : [];
}

/**
 * Save cart to localStorage
 * @param {Array} cart - Cart items
 */
function saveCart(cart) {
    const storageKey = getCartStorageKey();
    localStorage.setItem(storageKey, JSON.stringify(cart));
}

/**
 * Add item to cart
 * @param {number} mealId - Meal ID
 * @param {string} mealName - Meal name
 * @param {number} price - Meal price
 * @param {string} imageUrl - Meal image
 * @param {number} quantity - Quantity (default: 1)
 */
function addToCart(mealId, mealName, price, imageUrl = '', quantity = 1) {
    const cart = getCart();
    
    // Check if item already exists
    const existingItem = cart.find(item => item.meal_id === mealId);
    
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({
            meal_id: mealId,
            meal_name: mealName,
            unit_price: parseFloat(price),
            image_url: imageUrl,
            quantity: quantity
        });
    }
    
    saveCart(cart);
    updateCartCount();
    showNotification(`${mealName} added to cart!`, 'success');
}

/**
 * Remove item from cart
 * @param {number} index - Item index in cart
 */
function removeFromCart(index) {
    const cart = getCart();
    cart.splice(index, 1);
    saveCart(cart);
    updateCartCount();
    
    // Trigger custom event for cart page to listen to
    window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { cart } }));
}

/**
 * Update cart item quantity
 * @param {number} index - Item index
 * @param {number} change - Quantity change (+1 or -1)
 */
function updateQuantity(index, change) {
    const cart = getCart();
    if (cart[index]) {
        cart[index].quantity += change;
        
        if (cart[index].quantity < 1) {
            cart[index].quantity = 1;
        }
        
        if (cart[index].quantity > 10) {
            cart[index].quantity = 10;
            showNotification('Maximum quantity is 10 per item', 'warning');
        }
        
        saveCart(cart);
        updateCartCount();
        
        // Trigger custom event for cart page to listen to
        window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { cart } }));
    }
}

/**
 * Clear entire cart
 */
function clearCart() {
    if (getCart().length === 0) {
        showNotification('Your cart is already empty.', 'info');
        return;
    }
    if (!confirm('Clear all items from your cart?')) {
        return;
    }
    const storageKey = getCartStorageKey();
    localStorage.removeItem(storageKey);
    updateCartCount();
    
    // Trigger custom event for cart page to listen to
    window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { cart: [] } }));
    
    showNotification('Cart cleared', 'success');
}

/**
 * Get cart count
 * @return {number} Total items in cart
 */
function getCartCount() {
    const cart = getCart();
    return cart.reduce((total, item) => total + item.quantity, 0);
}

/**
 * Get cart subtotal
 * @return {number} Subtotal amount
 */
function getCartSubtotal() {
    const cart = getCart();
    return cart.reduce((total, item) => total + (item.unit_price * item.quantity), 0);
}

/**
 * Update cart count badge
 */
function updateCartCount() {
    const count = getCartCount();
    const badges = document.querySelectorAll('#cartCount, #cartBadge, #floatingCartBadge');
    
    badges.forEach(badge => {
        if (badge) {
            badge.textContent = count;
            
            // Add animation
            badge.style.transform = 'scale(1.2)';
            setTimeout(() => {
                badge.style.transform = 'scale(1)';
            }, 200);
        }
    });
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
});

// Add to cart button handler for dynamically loaded content
const decodeDatasetValue = (value = '') => {
    try {
        return decodeURIComponent(value);
    } catch (error) {
        return value;
    }
};

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart-btn')) {
        const mealId = e.target.dataset.mealId;
        const mealName = decodeDatasetValue(e.target.dataset.mealName);
        const price = e.target.dataset.price;
        const imageUrl = e.target.dataset.image;
        
        if (mealId && mealName && price) {
            addToCart(parseInt(mealId, 10), mealName, parseFloat(price), imageUrl);
        }
    }
});