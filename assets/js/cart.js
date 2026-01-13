/**
 * Shopping Cart JavaScript
 * Manages cart operations with inventory validation
 */

function hasCartAccess(showPrompt = false) {
    if (showPrompt) {
        // Guests can browse cart, but need login for checkout
        const allowed = true; // Allow everyone to browse cart
        if (!allowed) {
            showNotification('Cart access restricted', 'info');
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
 * Get inventory data for a specific meal
 * @param {number} mealId - Meal ID
 * @return {Promise<Object>} Inventory information
 */
async function getMealInventory(mealId) {
    try {
        const response = await apiCall(`customer/get_inventory.php?meal_id=${mealId}`, 'GET');
        if (response.success) {
            return response.data;
        }
        return { current_stock: 0, max_quantity: 0, in_stock: false };
    } catch (error) {
        console.error('Error fetching inventory:', error);
        return { current_stock: 0, max_quantity: 0, in_stock: false };
    }
}

/**
 * Check if meal is available and get max allowed quantity
 * @param {number} mealId - Meal ID
 * @return {Promise<Object>} Availability info
 */
async function checkMealAvailability(mealId) {
    try {
        const inventory = await getMealInventory(mealId);
        return {
            available: inventory.in_stock && inventory.current_stock > 0,
            maxQuantity: Math.min(inventory.current_stock, inventory.max_quantity || 10),
            currentStock: inventory.current_stock,
            trackInventory: inventory.track_inventory !== false
        };
    } catch (error) {
        console.error('Error checking availability:', error);
        return { available: false, maxQuantity: 0, currentStock: 0, trackInventory: true };
    }
}

/**
 * Validate cart quantities against current inventory
 * @return {Promise<Object>} Validation results
 */
async function validateCartInventory() {
    const cart = getCart();
    const validationResults = {
        valid: true,
        items: [],
        updatedCart: [...cart]
    };
    
    for (let i = 0; i < cart.length; i++) {
        const item = cart[i];
        const availability = await checkMealAvailability(item.meal_id);
        
        if (!availability.available) {
            validationResults.valid = false;
            validationResults.items.push({
                index: i,
                item: item,
                issue: 'Out of Stock',
                maxQuantity: 0
            });
            // Remove out of stock items from cart
            validationResults.updatedCart.splice(i, 1);
            i--; // Adjust index after removal
        } else if (item.quantity > availability.maxQuantity) {
            validationResults.valid = false;
            validationResults.items.push({
                index: i,
                item: item,
                issue: 'Exceeds Available Stock',
                maxQuantity: availability.maxQuantity,
                currentStock: availability.currentStock
            });
            // Update quantity to maximum available
            validationResults.updatedCart[i].quantity = availability.maxQuantity;
        }
    }
    
    return validationResults;
}

/**
 * Validate cart before placing order
 * @return {Promise<Object>} Validation results
 */
async function validateCartBeforeOrder() {
    const validation = await validateCartInventory();
    
    if (!validation.valid) {
        let errorMessage = 'Cart validation issues found:\n';
        let hasOutOfStock = false;
        
        validation.items.forEach(item => {
            if (item.issue === 'Out of Stock') {
                hasOutOfStock = true;
                errorMessage += `• ${item.item.meal_name} is out of stock and removed from cart\n`;
            } else {
                errorMessage += `• ${item.item.meal_name}: Only ${item.maxQuantity} available (you had ${item.item.quantity})\n`;
            }
        });
        
        // Update cart with validated quantities
        saveCart(validation.updatedCart);
        updateCartCount();
        
        if (hasOutOfStock) {
            showNotification('Some items in your cart are out of stock and have been removed', 'error');
        } else {
            showNotification('Some item quantities have been adjusted to match available stock', 'warning');
        }
        
        // Trigger cart update to refresh UI
        window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { cart: validation.updatedCart } }));
        
        return false;
    }
    
    return true;
}

/**
 * Add item to cart (inventory-driven)
 * @param {number} mealId - Meal ID
 * @param {string} mealName - Meal name
 * @param {number} price - Meal price
 * @param {string} imageUrl - Meal image
 * @param {number} quantity - Quantity (default: 1)
 */
async function addToCart(mealId, mealName, price, imageUrl = '', quantity = 1) {
    try {
        // Check inventory availability first
        const availability = await checkMealAvailability(mealId);
        
        if (!availability.available) {
            showNotification(`${mealName} is currently out of stock`, 'error');
            return;
        }
        
        const cart = getCart();
        
        // Check if item already exists
        const existingItem = cart.find(item => item.meal_id === mealId);
        const currentQuantity = existingItem ? existingItem.quantity : 0;
        const requestedTotal = currentQuantity + quantity;
        
        // Validate against available stock
        if (requestedTotal > availability.maxQuantity) {
            const availableToAdd = availability.maxQuantity - currentQuantity;
            if (availableToAdd <= 0) {
                showNotification(`Maximum quantity of ${mealName} (${availability.maxQuantity}) already in cart`, 'warning');
                return;
            } else {
                showNotification(`Only ${availableToAdd} more ${mealName} can be added to cart`, 'warning');
                quantity = availableToAdd;
            }
        }
        
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
        
        if (quantity > 0) {
            showNotification(`${quantity} x ${mealName} added to cart!`, 'success');
        }
        
    } catch (error) {
        console.error('Error adding to cart:', error);
        logClientError('Cart item addition failed', error, { 
            component: 'cart',
            action: 'add_item'
        });
        showNotification('Error adding item to cart. Please try again.', 'error');
    }
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

<<<<<<< HEAD
/**
 * Update cart item quantity (inventory-driven)
 * @param {number} index - Item index
 * @param {number} change - Quantity change (+1 or -1)
 */
async function updateQuantity(index, change) {
=======
function updateQuantity(index, change) {
>>>>>>> 954f58417debf5cdd8d6cc2c361134972c319be8
    const cart = getCart();
    if (!cart[index]) return;
    
    const item = cart[index];
    const newQuantity = item.quantity + change;
    
    // Don't allow quantity less than 1
    if (newQuantity < 1) {
        showNotification('Minimum quantity is 1 per item', 'warning');
        return;
    }
    
    try {
        // Check inventory availability
        const availability = await checkMealAvailability(item.meal_id);
        
        // Check if meal is still available
        if (!availability.available) {
            showNotification(`${item.meal_name} is out of stock and has been removed from cart`, 'error');
            removeFromCart(index);
            return;
        }
        
<<<<<<< HEAD
        // Check against available stock
        if (newQuantity > availability.maxQuantity) {
            if (availability.trackInventory) {
                showNotification(`Only ${availability.maxQuantity} ${item.meal_name} available in stock`, 'warning');
                cart[index].quantity = availability.maxQuantity;
            } else {
                // For items that don't track inventory, keep the original limit
                cart[index].quantity = Math.min(newQuantity, 10);
                showNotification('Maximum quantity is 10 per item', 'warning');
            }
        } else {
            cart[index].quantity = newQuantity;
        }
        
=======
>>>>>>> 954f58417debf5cdd8d6cc2c361134972c319be8
        saveCart(cart);
        updateCartCount();
        
        // Trigger custom event for cart page to listen to
        window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { cart } }));
        
    } catch (error) {
        console.error('Error updating quantity:', error);
        showNotification('Error updating quantity. Please try again.', 'error');
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
    
    showNotification('Cart cleared successfully', 'success');
}

/**
 * Update cart count display
 */
function updateCartCount() {
    const cart = getCart();
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    
    // Update cart badge in header if it exists
    const cartBadge = document.getElementById('cartBadge');
    if (cartBadge) {
        cartBadge.textContent = totalItems;
        cartBadge.style.display = totalItems > 0 ? 'inline-block' : 'none';
    }
    
    // Update cart count displays
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = totalItems;
    });
}

/**
 * Calculate cart totals
 * @return {Object} Cart totals
 */
function calculateCartTotals() {
    const cart = getCart();
    const subtotal = cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
    const deliveryFee = cart.length > 0 ? 500 : 0; // Free delivery for empty cart
    const discount = 0; // Can add discount logic later
    const total = subtotal + deliveryFee - discount;
    
    return {
        subtotal,
        deliveryFee,
        discount,
        total,
        itemCount: cart.reduce((sum, item) => sum + item.quantity, 0)
    };
}

/**
 * Format currency display
 * @param {number} amount - Amount to format
 * @return {string} Formatted currency
 */
function formatCurrency(amount) {
    return 'MK ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Initialize cart functionality when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    // Auto-validate cart inventory every 30 seconds
    if (window.location.pathname.includes('cart.php')) {
        setInterval(async () => {
            const validation = await validateCartInventory();
            if (!validation.valid) {
                saveCart(validation.updatedCart);
                updateCartCount();
                window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { cart: validation.updatedCart } }));
                
                // Show notification for inventory changes
                const hasOutOfStock = validation.items.some(item => item.issue === 'Out of Stock');
                const hasLowStock = validation.items.some(item => item.issue === 'Exceeds Available Stock');
                
                if (hasOutOfStock) {
                    showNotification('Some items in your cart are now out of stock', 'error');
                } else if (hasLowStock) {
                    showNotification('Some item quantities have been adjusted due to limited stock', 'warning');
                }
            }
        }, 30000); // Check every 30 seconds
    }
});