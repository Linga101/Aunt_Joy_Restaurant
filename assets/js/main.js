/**
 * Main JavaScript File
 * Global functions and utilities
 */

// API Base URL
const API_BASE = '/aunt_joy/controllers/';

/**
 * Resolve endpoint relative to controllers folder
 * @param {string} endpoint
 * @returns {string}
 */
function resolveEndpoint(endpoint = '') {
    if (!endpoint) return API_BASE;
    if (endpoint.startsWith('http') || endpoint.startsWith('/')) {
        return endpoint;
    }
    return `${API_BASE}${endpoint.replace(/^\//, '')}`;
}

/**
 * Generic JSON API helper
 * @param {string} endpoint Relative path or absolute URL
 * @param {string} method HTTP method
 * @param {object|null} payload Data to send (converted to JSON)
 * @returns {Promise<object>} Parsed JSON payload
 */
async function apiCall(endpoint, method = 'GET', payload = null) {
    const url = resolveEndpoint(endpoint);
    const options = {
        method,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    };

    if (payload !== null) {
        if (payload instanceof FormData) {
            options.body = payload;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(payload);
        }
    }

    const response = await fetch(url, options);
    const contentType = response.headers.get('content-type') || '';
    let data;

    if (contentType.includes('application/json')) {
        data = await response.json();
    } else {
        const text = await response.text();
        data = { success: response.ok, data: text, message: text };
    }

    // Don't throw on success=false, return the data so caller can handle it
    if (!response.ok && !data.success) {
        const message = data?.message || `Request failed (${response.status})`;
        throw new Error(message);
    }

    return data;
}

/**
 * Show notification toast
 * @param {string} message - Message to display
 * @param {string} type - Type: success, error, warning, info
 */
function showNotification(message, type = 'info') {
    const colors = {
        success: 'linear-gradient(135deg, #4ade80, #22c55e)',
        error: 'linear-gradient(135deg, #ef4444, #dc2626)',
        warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
        info: 'linear-gradient(135deg, #ff8c42, #ff6b35)'
    };

    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${colors[type]};
        padding: 1rem 1.5rem;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        max-width: 400px;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Format currency
 * @param {number} amount - Amount to format
 * @return {string} Formatted currency
 */
function formatCurrency(amount) {
    return 'MK ' + parseFloat(amount).toFixed(2);
}

/**
 * Format date
 * @param {string} dateString - Date string
 * @return {string} Formatted date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Validate email format
 * @param {string} email - Email address
 * @return {boolean} Is valid
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate phone number (Malawi format)
 * @param {string} phone - Phone number
 * @return {boolean} Is valid
 */
function validatePhone(phone) {
    const re = /^\+?265\s?\d{3}\s?\d{3}\s?\d{3}$/;
    return re.test(phone);
}

/**
 * Show loading state on button
 * @param {HTMLElement} button - Button element
 */
function showLoading(button) {
    if (!button) return;
    button.disabled = true;
    if (!button.dataset.originalHtml) {
        button.dataset.originalHtml = button.innerHTML;
    }
    button.innerHTML = 'Loading...';
}

/**
 * Hide loading state on button
 * @param {HTMLElement} button - Button element
 */
function hideLoading(button) {
    if (!button) return;
    button.disabled = false;
    if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
        delete button.dataset.originalHtml;
    } else {
        button.innerHTML = 'Submit';
    }
}

/**
 * Confirm action
 * @param {string} message - Confirmation message
 * @return {boolean} User confirmed
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * Sanitize HTML to prevent XSS
 * @param {string} html - HTML string
 * @return {string} Sanitized HTML
 */
function sanitizeHTML(html) {
    const temp = document.createElement('div');
    temp.textContent = html;
    return temp.innerHTML;
}

/**
 * Debounce function
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in ms
 * @return {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Show alert message
 * @param {string} message - Alert message
 * @param {string} type - Type: success, error, warning, info
 */
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <span>${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    const container = document.getElementById('alertContainer');
    if (container) {
        container.innerHTML = '';
        container.appendChild(alertDiv);
        
        setTimeout(() => alertDiv.remove(), 5000);
    }
}

/**
 * Get query parameter from URL
 * @param {string} name - Parameter name
 * @return {string|null} Parameter value
 */
function getQueryParam(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

/**
 * Scroll to element smoothly
 * @param {string} elementId - Element ID
 */
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/**
 * Copy text to clipboard
 * @param {string} text - Text to copy
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard!', 'success');
    }).catch(() => {
        showNotification('Failed to copy', 'error');
    });
}

/**
 * Check if user is on mobile device
 * @return {boolean} Is mobile
 */
function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

/**
 * Format file size
 * @param {number} bytes - File size in bytes
 * @return {string} Formatted size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Validate form inputs
 * @param {HTMLFormElement} form - Form element
 * @return {boolean} Is valid
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

/**
 * Toggle password visibility
 * @param {string} inputId - Password input ID
 */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggle = event.target;
    
    if (input.type === 'password') {
        input.type = 'text';
        toggle.textContent = '👁️‍🗨️';
    } else {
        input.type = 'password';
        toggle.textContent = '👁️';
    }
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.dataset.tooltip;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) tooltip.remove();
        });
    });
}

/**
 * Auto-hide alerts after delay
 */
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

/**
 * Apply UI theme
 * @param {'light'|'dark'} theme
 */
function applyTheme(theme) {
    const normalized = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', normalized);
    try {
        localStorage.setItem('auntJoyTheme', normalized);
    } catch (error) {
        console.warn('Unable to persist theme', error);
    }
    const toggle = document.getElementById('themeToggle');
    if (toggle) {
        toggle.dataset.theme = normalized;
        toggle.innerHTML = normalized === 'dark' ? '<span class="theme-icon">☀️</span>' : '<span class="theme-icon">🌙</span>';
        toggle.setAttribute('aria-label', normalized === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
    }
}

/**
 * Initialize theme toggle button
 */
function initThemeToggle() {
    const toggle = document.getElementById('themeToggle');
    if (!toggle) return;
    const storedTheme = localStorage.getItem('auntJoyTheme');
    const initialTheme = storedTheme || document.documentElement.getAttribute('data-theme') || 'light';
    applyTheme(initialTheme);
    toggle.addEventListener('click', () => {
        const nextTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme);
    });
}

/**
 * Guard cart navigation for unauthenticated users
 */
function initNavGuards() {
    const guardedElements = Array.from(document.querySelectorAll('[data-requires-auth]'))
        .filter(el => el.dataset.requiresAuth === 'true');

    if (!guardedElements.length) {
        return;
    }

    guardedElements.forEach(element => {
        element.addEventListener('click', event => {
            event.preventDefault();
            const message = element.dataset.authMessage || 'Please log in to continue.';
            const redirectTarget = element.dataset.redirect || '/aunt_joy/views/auth/login.php';
            showNotification(message, 'info');
            setTimeout(() => {
                window.location.href = redirectTarget;
            }, 900);
        });
    });
}

/**
 * Initialize horizontal slider on landing page
 */
function initMenuSlider() {
    const slider = document.querySelector('.menu-slider');
    if (!slider) return;

    const track = slider.querySelector('.menu-slider-track');
    const windowEl = slider.querySelector('.menu-slider-window');
    const cards = slider.querySelectorAll('.menu-preview-card');
    const prevBtn = slider.querySelector('[data-direction="prev"]');
    const nextBtn = slider.querySelector('[data-direction="next"]');

    const getStep = () => {
        if (!cards.length) return windowEl.clientWidth;
        return cards[0].offsetWidth + 24;
    };

    const scrollByAmount = (amount) => {
        track.scrollBy({ left: amount, behavior: 'smooth' });
    };

    prevBtn?.addEventListener('click', () => scrollByAmount(-getStep()));
    nextBtn?.addEventListener('click', () => scrollByAmount(getStep()));

    let autoSlideInterval = null;
    const startAutoSlide = () => {
        if (autoSlideInterval) return;
        autoSlideInterval = setInterval(() => {
            const nearEnd = track.scrollLeft + windowEl.clientWidth >= track.scrollWidth - 5;
            if (nearEnd) {
                track.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                scrollByAmount(getStep());
            }
        }, 4500);
    };

    const stopAutoSlide = () => {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
            autoSlideInterval = null;
        }
    };

    if (slider.dataset.autoplay === 'true') {
        startAutoSlide();
        slider.addEventListener('mouseenter', stopAutoSlide);
        slider.addEventListener('mouseleave', startAutoSlide);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Aunt Joy\'s Restaurant - System Initialized');
    
    // Initialize tooltips
    initTooltips();
    
    // Auto-hide alerts
    autoHideAlerts();
    
    // Theme + guards
    initThemeToggle();
    initNavGuards();
    initMenuSlider();
    
    // Add loading class to body when navigating
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function() {
            if (this.target !== '_blank') {
                document.body.classList.add('loading');
            }
        });
    });
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    .error {
        border-color: #ef4444 !important;
    }
    
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .alert-success {
        background: rgba(74, 222, 128, 0.1);
        border: 1px solid #4ade80;
        color: #4ade80;
    }
    
    .alert-error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid #ef4444;
        color: #ef4444;
    }
    
    .alert-warning {
        background: rgba(251, 191, 36, 0.1);
        border: 1px solid #fbbf24;
        color: #fbbf24;
    }
    
    .alert-info {
        background: rgba(255, 140, 66, 0.1);
        border: 1px solid #ff8c42;
        color: #ff8c42;
    }
    
    .alert-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: inherit;
        opacity: 0.7;
    }
    
    .alert-close:hover {
        opacity: 1;
    }
    
    .tooltip {
        position: fixed;
        background: #333;
        color: white;
        padding: 0.5rem 0.8rem;
        border-radius: 5px;
        font-size: 0.85rem;
        z-index: 10000;
        pointer-events: none;
    }
`;
document.head.appendChild(style);