/**
 * Main Application JavaScript
 * 
 * Handles common functionality, API calls, and UI interactions
 * 
 * @author Dana Baradie
 * @course IT404
 */

// API Base URL
const API_BASE = '/backend/api';

/**
 * Make API request
 */
async function apiRequest(endpoint, options = {}) {
    const url = `${API_BASE}${endpoint}`;
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    const config = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, config);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API request error:', error);
        return {
            success: false,
            message: error.message || 'Network error occurred'
        };
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

/**
 * Create toast container if it doesn't exist
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
    document.body.appendChild(container);
    return container;
}

/**
 * Format date/time
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Format time
 */
function formatTime(timeString) {
    const date = new Date(`2000-01-01T${timeString}`);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Confirm dialog
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Loading state management
 */
function setLoading(element, isLoading) {
    if (isLoading) {
        element.disabled = true;
        element.innerHTML = '<span class="loading-spinner"></span> Loading...';
    } else {
        element.disabled = false;
        // Restore original content if stored
        if (element.dataset.originalContent) {
            element.innerHTML = element.dataset.originalContent;
        }
    }
}

/**
 * Store original button content
 */
function storeButtonContent(button) {
    if (!button.dataset.originalContent) {
        button.dataset.originalContent = button.innerHTML;
    }
}

/**
 * Auto-refresh functionality
 */
class AutoRefresh {
    constructor(callback, interval = 5000) {
        this.callback = callback;
        this.interval = interval;
        this.timer = null;
    }
    
    start() {
        this.stop();
        this.timer = setInterval(this.callback, this.interval);
    }
    
    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize popovers
 */
function initPopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    initPopovers();
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        apiRequest,
        showToast,
        formatDateTime,
        formatTime,
        confirmAction,
        setLoading,
        storeButtonContent,
        AutoRefresh
    };
}

