<?php
/**
 * Modern Enhanced Dashboard
 * 
 * Professional dashboard with modern UI and real-time updates
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$role = getUserRole();
$stats = [];

// Check if filtering by today
$filterToday = isset($_GET['filter']) && $_GET['filter'] === 'today';
$dateFilter = $filterToday ? "AND DATE(created_at) = CURDATE()" : "";

try {
    if ($role === 'admin') {
        // Enhanced statistics
        $query = "SELECT COUNT(*) as count FROM buses WHERE status = 'active'";
        $stats['active_buses'] = $db->query($query)->fetch()['count'];
        
        $query = "SELECT COUNT(*) as count FROM users WHERE role = 'driver'";
        $stats['total_drivers'] = $db->query($query)->fetch()['count'];
        
        $query = "SELECT COUNT(*) as count FROM users WHERE role = 'parent'";
        $stats['total_parents'] = $db->query($query)->fetch()['count'];
        
        $query = "SELECT COUNT(*) as count FROM routes WHERE active = 1";
        $stats['active_routes'] = $db->query($query)->fetch()['count'];
        
        $query = "SELECT COUNT(*) as count FROM students";
        $stats['total_students'] = $db->query($query)->fetch()['count'];
        
        // Get buses with GPS status
        $query = "SELECT COUNT(*) as count FROM buses 
                  WHERE status = 'active' 
                    AND current_latitude IS NOT NULL 
                    AND current_longitude IS NOT NULL";
        $stats['tracking_buses'] = $db->query($query)->fetch()['count'];
        
        // Get unread notifications count (with today filter if applicable)
        if ($filterToday) {
            $query = "SELECT COUNT(*) as count FROM notifications 
                      WHERE is_read = FALSE AND DATE(created_at) = CURDATE()";
        } else {
            $query = "SELECT COUNT(*) as count FROM notifications WHERE is_read = FALSE";
        }
        $stats['unread_notifications'] = $db->query($query)->fetch()['count'];
        
        // Today's activity stats
        if ($filterToday) {
            // Today's GPS updates
            $query = "SELECT COUNT(DISTINCT bus_id) as count FROM gps_logs 
                      WHERE DATE(timestamp) = CURDATE()";
            $stats['today_gps_updates'] = $db->query($query)->fetch()['count'];
            
            // Today's notifications sent
            $query = "SELECT COUNT(*) as count FROM notifications 
                      WHERE DATE(created_at) = CURDATE()";
            $stats['today_notifications'] = $db->query($query)->fetch()['count'];
        }
        
        // Recent activity
        $query = "SELECT b.*, u.full_name as driver_name 
                  FROM buses b 
                  LEFT JOIN users u ON b.driver_id = u.id 
                  ORDER BY b.id DESC LIMIT 5";
        $buses = $db->query($query)->fetchAll();
        
    } elseif ($role === 'parent') {
        $query = "SELECT COUNT(*) as count FROM students WHERE parent_id = :parent_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':parent_id', $_SESSION['user_id']);
        $stmt->execute();
        $stats['my_children'] = $stmt->fetch()['count'];
        
        // Get notifications count
        $query = "SELECT COUNT(*) as count FROM notifications 
                  WHERE parent_id = :parent_id AND is_read = FALSE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':parent_id', $_SESSION['user_id']);
        $stmt->execute();
        $stats['unread_notifications'] = $stmt->fetch()['count'];
        
    } elseif ($role === 'driver') {
        $query = "SELECT * FROM buses WHERE driver_id = :driver_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':driver_id', $_SESSION['user_id']);
        $stmt->execute();
        $my_bus = $stmt->fetch();
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="/css/modern-theme.css">

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <!-- Welcome Section -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard
                    </h1>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="/dashboard.php<?php echo $filterToday ? '' : '?filter=today'; ?>" 
                           class="btn btn-sm <?php echo $filterToday ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                           id="todayBtn">
                            <i class="fas fa-calendar me-1"></i>Today
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="exportBtn">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($filterToday): ?>
                <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Today's View:</strong> Showing statistics and activity for <?php echo date('F j, Y'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <!-- Admin Dashboard -->
                <?php if ($filterToday && isset($stats['today_gps_updates'])): ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">
                                        <i class="fas fa-satellite me-2"></i>Today's GPS Updates
                                    </h6>
                                    <h3 class="mb-0"><?php echo $stats['today_gps_updates']; ?></h3>
                                    <small class="text-muted">Buses with location updates today</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-body">
                                    <h6 class="card-title text-info">
                                        <i class="fas fa-bell me-2"></i>Today's Notifications
                                    </h6>
                                    <h3 class="mb-0"><?php echo $stats['today_notifications']; ?></h3>
                                    <small class="text-muted">Notifications sent today</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="grid-responsive mb-4">
                    <a href="/buses.php" class="stats-card-modern text-decoration-none d-block">
                        <div class="stat-icon">
                            <i class="fas fa-bus"></i>
                        </div>
                        <div class="stat-label">Active Buses</div>
                        <div class="stat-value" style="color: #0d6efd;">
                            <?php echo $stats['active_buses']; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-satellite text-success"></i> 
                            <?php echo $stats['tracking_buses'] ?? 0; ?> tracking
                        </div>
                    </a>

                    <a href="/users.php?filter=driver" class="stats-card-modern text-decoration-none d-block">
                        <div class="stat-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-label">Drivers</div>
                        <div class="stat-value" style="color: #198754;">
                            <?php echo $stats['total_drivers']; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-check-circle text-success"></i> All active
                        </div>
                    </a>

                    <a href="/users.php?filter=parent" class="stats-card-modern text-decoration-none d-block">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-label">Parents</div>
                        <div class="stat-value" style="color: #0dcaf0;">
                            <?php echo $stats['total_parents']; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-user-check"></i> Registered
                        </div>
                    </a>

                    <a href="/routes.php" class="stats-card-modern text-decoration-none d-block">
                        <div class="stat-icon">
                            <i class="fas fa-route"></i>
                        </div>
                        <div class="stat-label">Active Routes</div>
                        <div class="stat-value" style="color: #ffc107;">
                            <?php echo $stats['active_routes']; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-map-marked-alt"></i> In operation
                        </div>
                    </a>

                    <a href="/students.php" class="stats-card-modern text-decoration-none d-block">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-label">Students</div>
                        <div class="stat-value" style="color: #6f42c1;">
                            <?php echo $stats['total_students'] ?? 0; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-school"></i> Enrolled
                        </div>
                    </a>

                    <div class="stats-card-modern" onclick="showNotifications()" style="cursor: pointer;">
                        <div class="stat-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="stat-label">Notifications</div>
                        <div class="stat-value" style="color: #dc3545;">
                            <?php echo $stats['unread_notifications'] ?? 0; ?>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-envelope text-danger"></i> Unread
                        </div>
                    </a>
                </div>


            <?php elseif ($role === 'parent'): ?>
                <!-- Parent Dashboard -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-card-modern">
                            <div class="stat-icon">
                                <i class="fas fa-child"></i>
                            </div>
                            <div class="stat-label">My Children</div>
                            <div class="stat-value" style="color: #0d6efd;">
                                <?php echo $stats['my_children']; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card-modern">
                            <div class="stat-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="stat-label">Notifications</div>
                            <div class="stat-value" style="color: #dc3545;">
                                <?php echo $stats['unread_notifications'] ?? 0; ?>
                            </div>
                            <div class="text-muted small mt-2">Unread</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <a href="/track-bus.php" class="stats-card-modern text-decoration-none d-block">
                            <div class="stat-icon">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <div class="stat-label">Track Bus</div>
                            <div class="stat-value" style="color: #28a745;">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                    </div>
                </div>

            <?php elseif ($role === 'driver'): ?>
                <!-- Driver Dashboard -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-bus me-2"></i>My Bus</h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($my_bus)): ?>
                                    <h3 class="text-primary"><?php echo htmlspecialchars($my_bus['bus_number']); ?></h3>
                                    <p class="text-muted">Assigned Bus</p>
                                    <a href="/update-location.php" class="btn btn-primary w-100 mt-3">
                                        <i class="fas fa-location-arrow me-2"></i>Update Location
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No bus assigned yet. Please contact administrator.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
// Initialize components
document.addEventListener('DOMContentLoaded', () => {
    // Add page transition
    document.body.classList.add('page-transition');
    
    // Auto-refresh stats every 30 seconds
    if (typeof refreshStats === 'function') {
        setInterval(refreshStats, 30000);
    }
    
    // Export button functionality
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            exportDashboardData();
        });
    }
});

// Export dashboard data to CSV
function exportDashboardData() {
    const role = '<?php echo $role; ?>';
    const filterToday = <?php echo $filterToday ? 'true' : 'false'; ?>;
    
    // Get current stats from the page
    const stats = {
        date: new Date().toLocaleDateString(),
        filter: filterToday ? 'Today' : 'All Time',
        role: role
    };
    
    <?php if ($role === 'admin'): ?>
    stats.active_buses = <?php echo $stats['active_buses'] ?? 0; ?>;
    stats.total_drivers = <?php echo $stats['total_drivers'] ?? 0; ?>;
    stats.total_parents = <?php echo $stats['total_parents'] ?? 0; ?>;
    stats.active_routes = <?php echo $stats['active_routes'] ?? 0; ?>;
    stats.total_students = <?php echo $stats['total_students'] ?? 0; ?>;
    stats.tracking_buses = <?php echo $stats['tracking_buses'] ?? 0; ?>;
    stats.unread_notifications = <?php echo $stats['unread_notifications'] ?? 0; ?>;
    <?php if ($filterToday && isset($stats['today_gps_updates'])): ?>
    stats.today_gps_updates = <?php echo $stats['today_gps_updates'] ?? 0; ?>;
    stats.today_notifications = <?php echo $stats['today_notifications'] ?? 0; ?>;
    <?php endif; ?>
    <?php elseif ($role === 'parent'): ?>
    stats.my_children = <?php echo $stats['my_children'] ?? 0; ?>;
    stats.unread_notifications = <?php echo $stats['unread_notifications'] ?? 0; ?>;
    <?php endif; ?>
    
    // Create CSV content
    let csvContent = "Dashboard Statistics Export\n";
    csvContent += "Generated: " + stats.date + "\n";
    csvContent += "Filter: " + stats.filter + "\n";
    csvContent += "Role: " + stats.role + "\n\n";
    csvContent += "Metric,Value\n";
    
    for (const [key, value] of Object.entries(stats)) {
        if (key !== 'date' && key !== 'filter' && key !== 'role') {
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            csvContent += label + "," + value + "\n";
        }
    }
    
    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'dashboard_export_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Show notifications
function showNotifications() {
    // For now, show an alert. Later can be replaced with a modal or page
    fetch('/api/notifications/index.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.data.unread_count || 0;
                if (count > 0) {
                    alert(`You have ${count} unread notification(s).\n\nA notifications page will be available soon.`);
                } else {
                    alert('No unread notifications.');
                }
            } else {
                alert('Unable to load notifications.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Unable to load notifications.');
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
