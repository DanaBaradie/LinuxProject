<div class="sidebar-overlay" id="sidebarOverlay"></div>
<nav class="d-md-block bg-light sidebar" id="sidebar">
    <div class="sidebar-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </li>
            
            <?php if (getUserRole() === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'buses.php' ? 'active' : ''; ?>" href="/buses.php">
                        <i class="fas fa-bus"></i>Manage Buses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'routes.php' ? 'active' : ''; ?>" href="/routes.php">
                        <i class="fas fa-route"></i>Manage Routes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="/users.php">
                        <i class="fas fa-users"></i>Manage Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>" href="/students.php">
                        <i class="fas fa-user-graduate"></i>Manage Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tracking.php' ? 'active' : ''; ?>" href="/tracking.php">
                        <i class="fas fa-map-marked-alt"></i>Live Tracking
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" href="/notifications.php">
                        <i class="fas fa-bell"></i>Send Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" href="/messages.php">
                        <i class="fas fa-envelope"></i>Email Messages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'test-sendgrid.php' ? 'active' : ''; ?>" href="/test-sendgrid.php">
                        <i class="fas fa-vial"></i>Test SendGrid
                    </a>
                </li>
            <?php elseif (getUserRole() === 'parent'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my-children.php' ? 'active' : ''; ?>" href="/my-children.php">
                        <i class="fas fa-child"></i>My Children
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'track-bus.php' ? 'active' : ''; ?>" href="/track-bus.php">
                        <i class="fas fa-map-marker-alt"></i>Track Bus
                    </a>
                </li>
            <?php elseif (getUserRole() === 'driver'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my-bus.php' ? 'active' : ''; ?>" href="/my-bus.php">
                        <i class="fas fa-bus"></i>My Bus
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'update-location.php' ? 'active' : ''; ?>" href="/update-location.php">
                        <i class="fas fa-location-arrow"></i>Update Location
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" href="/notifications.php">
                        <i class="fas fa-bell"></i>Send Notifications
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
