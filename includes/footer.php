<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/app.js"></script>
<script src="/js/components.js"></script>
<script>
// Sidebar Toggle Functionality
(function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    
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
                sidebarOverlay.classList.remove('show');
            } else {
                sidebar.classList.add('show');
                sidebarOverlay.classList.add('show');
            }
        } else {
            sidebar.classList.toggle('collapsed');
        }
    }
    
    // Close sidebar function
    function closeSidebar() {
        if (isMobile()) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
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
    
    // Close sidebar when menu item is clicked
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            // Small delay to allow navigation to start
            setTimeout(function() {
                closeSidebar();
            }, 100);
        });
    });
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const nowMobile = window.innerWidth <= 768;
            if (nowMobile !== isMobile) {
                // Reload page on significant size change to reinitialize
                // Or handle state transition
                if (nowMobile) {
                    sidebar.classList.remove('collapsed');
                    sidebar.classList.add('collapsed');
                } else {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                }
            }
        }, 250);
    });
})();
</script>
</body>
</html>
