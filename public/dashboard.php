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
                    <div class="stats-card-modern">
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
                    </div>

                    <div class="stats-card-modern">
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
                    </div>

                    <div class="stats-card-modern">
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
                    </div>

                    <div class="stats-card-modern">
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
                    </div>

                    <div class="stats-card-modern">
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
                    </div>

                    <div class="stats-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-label">System Health</div>
                        <div class="stat-value" style="color: #28a745;">
                            98%
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fas fa-heartbeat text-success"></i> Optimal
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <a href="/tracking-enhanced.php" class="btn btn-primary w-100">
                                            <i class="fas fa-map-marked-alt me-2"></i>Live Tracking
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/buses.php" class="btn btn-success w-100">
                                            <i class="fas fa-bus me-2"></i>Manage Buses
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/routes.php" class="btn btn-info w-100">
                                            <i class="fas fa-route me-2"></i>Manage Routes
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="/students.php" class="btn btn-warning w-100">
                                            <i class="fas fa-user-graduate me-2"></i>Manage Students
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Buses -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-bus me-2"></i>Recent Buses</h5>
                                <a href="/buses.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($buses)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Bus Number</th>
                                                    <th>Driver</th>
                                                    <th>Status</th>
                                                    <th>GPS</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($buses as $bus): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($bus['driver_name'] ?? 'Unassigned'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $bus['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                                <?php echo ucfirst($bus['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($bus['current_latitude'] && $bus['current_longitude']): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check-circle me-1"></i>Active
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">
                                                                    <i class="fas fa-times-circle me-1"></i>Offline
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="/tracking-enhanced.php?bus=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
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
                    
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Bus Utilization</span>
                                        <span>75%</span>
                                    </div>
                                    <div class="progress-modern">
                                        <div class="progress-bar" style="width: 75%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Route Coverage</span>
                                        <span>90%</span>
                                    </div>
                                    <div class="progress-modern">
                                        <div class="progress-bar bg-success" style="width: 90%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>GPS Accuracy</span>
                                        <span>98%</span>
                                    </div>
                                    <div class="progress-modern">
                                        <div class="progress-bar bg-info" style="width: 98%"></div>
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
</script>

<?php require_once '../includes/footer.php'; ?>
