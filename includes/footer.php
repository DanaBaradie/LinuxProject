<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/app.js"></script>
<script src="/js/components.js"></script>
<script>
    // Sidebar Toggle Functionality
    (function () {
        function initSidebar() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            if (!sidebar) return;

            function isMobile() {
                return window.innerWidth <= 768;
            }

            // Initialize sidebar state with localStorage persistence
            function initializeSidebar() {
                const savedState = localStorage.getItem('sidebarState');

                if (isMobile()) {
                    sidebar.classList.remove('collapsed');
                    sidebar.classList.remove('show');
                    if (sidebarOverlay) sidebarOverlay.classList.remove('show');
                } else {
                    sidebar.classList.remove('show');
                    if (savedState === 'collapsed') {
                        sidebar.classList.add('collapsed');
                    } else {
                        sidebar.classList.remove('collapsed');
                    }
                }
            }

            // Toggle sidebar function
            function toggleSidebar() {
                if (isMobile()) {
                    const isShowing = sidebar.classList.contains('show');
                    if (isShowing) {
                        sidebar.classList.remove('show');
                        if (sidebarOverlay) sidebarOverlay.classList.remove('show');
                    } else {
                        sidebar.classList.add('show');
                        if (sidebarOverlay) sidebarOverlay.classList.add('show');
                    }
                } else {
                    sidebar.classList.toggle('collapsed');
                    // Save state
                    if (sidebar.classList.contains('collapsed')) {
                        localStorage.setItem('sidebarState', 'collapsed');
                    } else {
                        localStorage.setItem('sidebarState', 'expanded');
                    }
                }
            }

            // Close sidebar function
            function closeSidebar() {
                if (isMobile()) {
                    sidebar.classList.remove('show');
                    if (sidebarOverlay) sidebarOverlay.classList.remove('show');
                } else {
                    sidebar.classList.add('collapsed');
                    localStorage.setItem('sidebarState', 'collapsed');
                }
            }

            // Initialize on page load
            initializeSidebar();

            // Toggle button click
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            }

            // Overlay click to close
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function () {
                    closeSidebar();
                });
            }

            // Close sidebar when menu item is clicked
            const sidebarContainer = sidebar.querySelector('.sidebar-sticky');
            if (sidebarContainer) {
                sidebarContainer.addEventListener('click', function (e) {
                    const clickedLink = e.target.closest('.nav-link');
                    // Check if it's a link and we are not just expanding a submenu (if any)
                    if (clickedLink && clickedLink.href && clickedLink.getAttribute('href') !== '#') {
                        // On mobile: always close. 
                        // On desktop: The user requested "whenever a thing is chosen... should be closed"
                        // So we force close (collapse) on desktop too and save that state.
                        closeSidebar();
                    }
                });
            }

            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () {
                    // Re-evaluate state based on screen size but respect user preference on desktop
                    if (!isMobile()) {
                        const savedState = localStorage.getItem('sidebarState');
                        if (savedState === 'collapsed') {
                            sidebar.classList.add('collapsed');
                        } else {
                            sidebar.classList.remove('collapsed');
                        }
                        sidebar.classList.remove('show'); // Remove mobile class
                    } else {
                        sidebar.classList.remove('collapsed'); // Remove desktop class
                        sidebar.classList.remove('show'); // Start hidden on mobile resize
                    }
                }, 250);
            });
        }

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSidebar);
        } else {
            initSidebar();
        }
    })();

    // Real-time Notification System
    (function () {
        let lastNotificationCheck = 0;
        let notificationCheckInterval = null;
        let displayedNotificationIds = new Set();

        function getNotificationEndpoint() {
            const role = '<?php echo getUserRole(); ?>';
            if (role === 'driver') {
                return '/api/notifications/driver.php?unread_only=true';
            } else if (role === 'parent') {
                return '/api/notifications/index.php?unread_only=true';
            }
            return null;
        }

        function checkForNotifications() {
            const endpoint = getNotificationEndpoint();
            if (!endpoint) return;

            fetch(endpoint)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.notifications) {
                        const notifications = data.data.notifications;

                        // Filter out already displayed notifications
                        const newNotifications = notifications.filter(notif =>
                            !displayedNotificationIds.has(notif.id) && !notif.is_read
                        );

                        // Display new notifications
                        newNotifications.forEach(notification => {
                            displayNotificationPopup(notification);
                            displayedNotificationIds.add(notification.id);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error checking notifications:', error);
                });
        }

        function displayNotificationPopup(notification) {
            // Create notification modal
            const modal = document.createElement('div');
            modal.className = 'notification-popup-modal';
            modal.setAttribute('data-notification-id', notification.id);

            const typeIcons = {
                'traffic': 'fa-traffic-light',
                'speed_warning': 'fa-exclamation-triangle',
                'nearby': 'fa-map-marker-alt',
                'route_change': 'fa-route',
                'general': 'fa-bell'
            };

            const typeColors = {
                'traffic': 'warning',
                'speed_warning': 'danger',
                'nearby': 'success',
                'route_change': 'info',
                'general': 'primary'
            };

            const icon = typeIcons[notification.notification_type] || 'fa-bell';
            const color = typeColors[notification.notification_type] || 'primary';

            modal.innerHTML = `
            <div class="notification-popup-content">
                <div class="notification-popup-header bg-${color}">
                    <div class="d-flex align-items-center">
                        <i class="fas ${icon} me-2"></i>
                        <strong>${notification.notification_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</strong>
                    </div>
                    <button type="button" class="btn-close btn-close-white" onclick="closeNotificationPopup(this)"></button>
                </div>
                <div class="notification-popup-body">
                    <p class="mb-2">${escapeHtml(notification.message)}</p>
                    ${notification.bus_number ? `<small class="text-muted"><i class="fas fa-bus me-1"></i>Bus: ${escapeHtml(notification.bus_number)}</small>` : ''}
                    <small class="text-muted d-block mt-2"><i class="fas fa-clock me-1"></i>${notification.created_at_formatted || 'Just now'}</small>
                </div>
                <div class="notification-popup-footer">
                    <button type="button" class="btn btn-sm btn-primary" onclick="markNotificationRead(${notification.id}, this)">
                        <i class="fas fa-check me-1"></i>Mark as Read
                    </button>
                </div>
            </div>
        `;

            document.body.appendChild(modal);

            // Auto-close after 10 seconds
            setTimeout(() => {
                if (modal.parentNode) {
                    closeNotificationPopup(modal.querySelector('.btn-close'));
                }
            }, 10000);

            // Mark as read automatically after 5 seconds
            setTimeout(() => {
                markNotificationRead(notification.id, null, true);
            }, 5000);
        }

        function closeNotificationPopup(button) {
            const modal = button.closest('.notification-popup-modal');
            if (modal) {
                modal.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    if (modal.parentNode) {
                        modal.parentNode.removeChild(modal);
                    }
                }, 300);
            }
        }

        function markNotificationRead(notificationId, button, silent = false) {
            fetch('/api/notifications/mark-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (button) {
                            button.closest('.notification-popup-modal').querySelector('.btn-close').click();
                        }
                        if (!silent) {
                            // Update notification count if displayed
                            updateNotificationBadge();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
        }

        function updateNotificationBadge() {
            // Update notification badge in navbar if exists
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const endpoint = getNotificationEndpoint();
                if (endpoint) {
                    fetch(endpoint.replace('unread_only=true', 'unread_only=true'))
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data) {
                                const count = data.data.unread_count || 0;
                                badge.textContent = count;
                                badge.style.display = count > 0 ? 'inline-block' : 'none';
                            }
                        });
                }
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Make functions globally available
        window.closeNotificationPopup = closeNotificationPopup;
        window.markNotificationRead = markNotificationRead;

        // Start polling for notifications
        function startNotificationPolling() {
            // Check immediately
            checkForNotifications();

            // Then check every 5 seconds
            notificationCheckInterval = setInterval(checkForNotifications, 5000);
        }

        // Stop polling when page is hidden
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                if (notificationCheckInterval) {
                    clearInterval(notificationCheckInterval);
                    notificationCheckInterval = null;
                }
            } else {
                if (!notificationCheckInterval) {
                    startNotificationPolling();
                }
            }
        });

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startNotificationPolling);
        } else {
            startNotificationPolling();
        }
    })();
</script>

<style>
    /* Notification Popup Styles */
    .notification-popup-modal {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        min-width: 350px;
        max-width: 450px;
        animation: slideIn 0.3s ease-out;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        border-radius: 8px;
        overflow: hidden;
    }

    @keyframes slideIn {
        from {
            transform: translateX(400px);
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
            transform: translateX(400px);
            opacity: 0;
        }
    }

    .notification-popup-content {
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }

    .notification-popup-header {
        padding: 12px 16px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-popup-body {
        padding: 16px;
    }

    .notification-popup-footer {
        padding: 12px 16px;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        text-align: right;
    }

    @media (max-width: 768px) {
        .notification-popup-modal {
            right: 10px;
            left: 10px;
            min-width: auto;
            max-width: none;
        }
    }
</style>
</body>

</html>