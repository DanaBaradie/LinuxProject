<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/app.js"></script>
<script src="/js/components.js"></script>
<script>
// Sidebar Toggle Functionality
(function() {
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (!sidebar) return;
        
        function isMobile() {
            return window.innerWidth <= 768;
        }
        
        // Initialize sidebar state - collapsed on mobile, open on desktop
        function initializeSidebar() {
            if (isMobile()) {
                sidebar.classList.remove('collapsed');
                sidebar.classList.remove('show');
                // Sidebar starts hidden on mobile
            } else {
                sidebar.classList.remove('collapsed');
                sidebar.classList.remove('show');
                // Sidebar starts visible on desktop
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
            }
        }
        
        // Close sidebar function
        function closeSidebar() {
            if (isMobile()) {
                sidebar.classList.remove('show');
                if (sidebarOverlay) sidebarOverlay.classList.remove('show');
            } else {
                sidebar.classList.add('collapsed');
            }
        }
        
        // Initialize on page load
        initializeSidebar();
        
        // Toggle button click
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleSidebar();
            });
        }
        
        // Overlay click to close
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                closeSidebar();
            });
        }
        
        // Close sidebar when menu item is clicked - use event delegation for dynamic content
        const sidebarContainer = sidebar.querySelector('.sidebar-sticky');
        if (sidebarContainer) {
            sidebarContainer.addEventListener('click', function(e) {
                const clickedLink = e.target.closest('.nav-link');
                if (clickedLink && clickedLink.href) {
                    // Close sidebar immediately when any menu link is clicked
                    closeSidebar();
                }
            });
        }
        
        // Also attach directly to existing links as backup
        const sidebarLinks = sidebar.querySelectorAll('.nav-link');
        sidebarLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                // Close sidebar immediately
                closeSidebar();
            });
        });
        
        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                const nowMobile = window.innerWidth <= 768;
                if (nowMobile !== isMobile()) {
                    if (nowMobile) {
                        sidebar.classList.remove('collapsed');
                        sidebar.classList.remove('show');
                    } else {
                        sidebar.classList.remove('show');
                        sidebar.classList.remove('collapsed');
                        if (sidebarOverlay) sidebarOverlay.classList.remove('show');
                    }
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
</script>
</body>
</html>
