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
        
        // Get unread notifications count
        $query = "SELECT COUNT(*) as count FROM notifications WHERE is_read = FALSE";
        $stats['unread_notifications'] = $db->query($query)->fetch()['count'];
        
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
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-calendar me-1"></i>Today
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($role === 'admin'): ?>
                <!-- Admin Dashboard -->
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

                <!-- Quick Actions -->
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card dashboard-section-card quick-actions-card">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h4>
                                <p class="text-muted mb-0 small">Access frequently used features</p>
                            </div>
                            <div class="card-body p-5">
                                <div class="row g-4">
                                    <div class="col-xl-3 col-lg-6 col-md-6">
                                        <a href="/tracking-enhanced.php" class="quick-action-btn btn-primary-modern">
                                            <div class="action-icon">
                                                <i class="fas fa-map-marked-alt"></i>
                                            </div>
                                            <div class="action-text">
                                                <h6 class="mb-2">Live Tracking</h6>
                                                <small class="d-block">Track buses in real-time</small>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-md-6">
                                        <a href="/buses.php" class="quick-action-btn btn-success-modern">
                                            <div class="action-icon">
                                                <i class="fas fa-bus"></i>
                                            </div>
                                            <div class="action-text">
                                                <h6 class="mb-2">Manage Buses</h6>
                                                <small class="d-block">View and edit fleet</small>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-md-6">
                                        <a href="/routes.php" class="quick-action-btn btn-info-modern">
                                            <div class="action-icon">
                                                <i class="fas fa-route"></i>
                                            </div>
                                            <div class="action-text">
                                                <h6 class="mb-2">Manage Routes</h6>
                                                <small class="d-block">Configure bus routes</small>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-md-6">
                                        <a href="/students.php" class="quick-action-btn btn-warning-modern">
                                            <div class="action-icon">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                            <div class="action-text">
                                                <h6 class="mb-2">Manage Students</h6>
                                                <small class="d-block">Student information</small>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Buses -->
                <div class="row mb-5 recent-buses-row">
                    <div class="col-12">
                        <div class="card dashboard-section-card recent-buses-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1"><i class="fas fa-bus me-2 text-primary"></i>Recent Buses</h4>
                                    <p class="text-muted mb-0 small">Latest bus activity and status</p>
                                </div>
                                <a href="/buses.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-arrow-right me-1"></i>View All
                                </a>
                            </div>
                            <div class="card-body p-5">
                                <?php if (!empty($buses)): ?>
                                    <div class="table-responsive table-responsive-wide">
                                        <table class="table table-hover table-modern table-wide">
                                            <thead>
                                                <tr>
                                                    <th class="ps-4" style="min-width: 180px;">Bus Number</th>
                                                    <th style="min-width: 200px;">Driver</th>
                                                    <th style="min-width: 120px;">Status</th>
                                                    <th style="min-width: 130px;">GPS Status</th>
                                                    <th class="text-end pe-4" style="min-width: 140px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($buses as $bus): ?>
                                                    <tr>
                                                        <td class="ps-4">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-bus text-primary me-3" style="font-size: 1.2rem;"></i>
                                                                <strong style="font-size: 1.05rem;"><?php echo htmlspecialchars($bus['bus_number']); ?></strong>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-user-tie text-muted me-3" style="font-size: 1.1rem;"></i>
                                                                <span style="font-size: 1rem;"><?php echo htmlspecialchars($bus['driver_name'] ?? 'Unassigned'); ?></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-status bg-<?php echo $bus['status'] === 'active' ? 'success' : 'secondary'; ?>" style="padding: 0.6rem 1rem; font-size: 0.9rem;">
                                                                <i class="fas fa-circle me-2" style="font-size: 0.6rem;"></i>
                                                                <?php echo ucfirst($bus['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($bus['current_latitude'] && $bus['current_longitude']): ?>
                                                                <span class="badge badge-status bg-success" style="padding: 0.6rem 1rem; font-size: 0.9rem;">
                                                                    <i class="fas fa-satellite-dish me-2"></i>Active
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge badge-status bg-secondary" style="padding: 0.6rem 1rem; font-size: 0.9rem;">
                                                                    <i class="fas fa-wifi-slash me-2"></i>Offline
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <a href="/tracking-enhanced.php?bus=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-primary" style="padding: 0.5rem 1rem;">
                                                                <i class="fas fa-eye me-2"></i>View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-bus"></i>
                                        </div>
                                        <p>No buses added yet</p>
                                        <a href="/buses.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add Your First Bus
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                <!-- Quick Stats -->
                <div class="row quick-stats-row">
                    <div class="col-12">
                        <div class="card dashboard-section-card quick-stats-card">
                            <div class="card-header">
                                <h4 class="mb-1"><i class="fas fa-chart-line me-2 text-info"></i>Quick Stats</h4>
                                <p class="text-muted mb-0 small">System performance metrics</p>
                            </div>
                            <div class="card-body p-5">
                                <div class="row g-5">
                                    <div class="col-xl-4 col-lg-4 col-md-12">
                                        <div class="stat-item-full">
                                            <div class="d-flex justify-content-between align-items-start mb-4">
                                                <div class="d-flex align-items-start flex-grow-1 me-4">
                                                    <div class="stat-icon-wrapper bg-primary me-4">
                                                        <i class="fas fa-bus text-white"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="stat-label-full mb-2">Bus Utilization</div>
                                                        <div class="stat-desc">Fleet usage rate across all active buses in the system</div>
                                                    </div>
                                                </div>
                                                <span class="stat-value-text text-primary fw-bold">75%</span>
                                            </div>
                                            <div class="progress progress-modern-large">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-4 col-lg-4 col-md-12">
                                        <div class="stat-item-full">
                                            <div class="d-flex justify-content-between align-items-start mb-4">
                                                <div class="d-flex align-items-start flex-grow-1 me-4">
                                                    <div class="stat-icon-wrapper bg-success me-4">
                                                        <i class="fas fa-route text-white"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="stat-label-full mb-2">Route Coverage</div>
                                                        <div class="stat-desc">Percentage of active routes currently in operation</div>
                                                    </div>
                                                </div>
                                                <span class="stat-value-text text-success fw-bold">90%</span>
                                            </div>
                                            <div class="progress progress-modern-large">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 90%" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-4 col-lg-4 col-md-12">
                                        <div class="stat-item-full">
                                            <div class="d-flex justify-content-between align-items-start mb-4">
                                                <div class="d-flex align-items-start flex-grow-1 me-4">
                                                    <div class="stat-icon-wrapper bg-info me-4">
                                                        <i class="fas fa-satellite-dish text-white"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="stat-label-full mb-2">GPS Accuracy</div>
                                                        <div class="stat-desc">Real-time tracking precision and system reliability</div>
                                                    </div>
                                                </div>
                                                <span class="stat-value-text text-info fw-bold">98%</span>
                                            </div>
                                            <div class="progress progress-modern-large">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: 98%" aria-valuenow="98" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
});

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
