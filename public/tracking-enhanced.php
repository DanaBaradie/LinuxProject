<?php
/**
 * Enhanced Real-Time Tracking Page
 * 
 * Professional tracking interface with modern UI
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
$userId = $_SESSION['user_id'];

// Get buses based on role
try {
    if ($role === 'admin') {
        $query = "SELECT b.*, u.full_name as driver_name 
                  FROM buses b 
                  LEFT JOIN users u ON b.driver_id = u.id 
                  WHERE b.status = 'active' 
                  ORDER BY b.bus_number";
        $stmt = $db->prepare($query);
    } elseif ($role === 'driver') {
        $query = "SELECT b.*, u.full_name as driver_name 
                  FROM buses b 
                  LEFT JOIN users u ON b.driver_id = u.id 
                  WHERE b.driver_id = :driver_id AND b.status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':driver_id', $userId);
    } else {
        // Parent
        $query = "SELECT DISTINCT b.*, u.full_name as driver_name 
                  FROM buses b
                  INNER JOIN bus_routes br ON b.id = br.bus_id
                  INNER JOIN routes r ON br.route_id = r.id
                  INNER JOIN route_stops rs ON r.id = rs.route_id
                  INNER JOIN students s ON rs.id = s.assigned_stop_id
                  LEFT JOIN users u ON b.driver_id = u.id
                  WHERE s.parent_id = :parent_id AND b.status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':parent_id', $userId);
    }
    
    $stmt->execute();
    $buses = $stmt->fetchAll();
    
    // Get statistics
    $statsQuery = "SELECT 
                    COUNT(*) as total_buses,
                    SUM(CASE WHEN b.current_latitude IS NOT NULL THEN 1 ELSE 0 END) as tracking_buses,
                    AVG(b.current_latitude) as avg_lat,
                    AVG(b.current_longitude) as avg_lng
                   FROM buses b
                   WHERE b.status = 'active'";
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch();
    
} catch (Exception $e) {
    error_log($e->getMessage());
    $buses = [];
    $stats = ['total_buses' => 0, 'tracking_buses' => 0];
}

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="/css/tracking-enhanced.css">

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="fas fa-map-marked-alt me-2 text-primary"></i>Live Bus Tracking
                    </h1>
                    <p class="text-muted mb-0">Real-time GPS tracking and monitoring</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="realtime-indicator">
                        <span class="pulse-dot"></span>
                        <span>LIVE</span>
                    </span>
                    <button class="btn btn-outline-primary" onclick="trackingSystem.refresh()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="tracking-stats">
                <div class="stat-card">
                    <div class="stat-label">Total Buses</div>
                    <div class="stat-value"><?php echo $stats['total_buses']; ?></div>
                    <div class="text-muted small">
                        <i class="fas fa-bus"></i> Active fleet
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Tracking Now</div>
                    <div class="stat-value text-success"><?php echo $stats['tracking_buses']; ?></div>
                    <div class="text-muted small">
                        <i class="fas fa-satellite"></i> GPS active
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Update Rate</div>
                    <div class="stat-value text-info">3s</div>
                    <div class="text-muted small">
                        <i class="fas fa-clock"></i> Auto-refresh
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Map Status</div>
                    <div class="stat-value text-primary" id="map-status">Ready</div>
                    <div class="text-muted small">
                        <i class="fas fa-map"></i> <span id="map-provider">Google Maps</span>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="text" class="form-control" id="bus-search" placeholder="Search buses..." onkeyup="filterBuses(this.value)">
                <select class="form-select" id="status-filter" onchange="filterByStatus(this.value)">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="btn btn-outline-secondary" onclick="trackingSystem.fitAllBuses()">
                    <i class="fas fa-expand me-1"></i>Fit All
                </button>
                <button class="btn btn-outline-success" onclick="trackingSystem.toggleRoutes()">
                    <i class="fas fa-route me-1"></i>Routes
                </button>
            </div>

            <!-- Main Tracking Interface -->
            <div class="tracking-container">
                <!-- Map Section -->
                <div class="tracking-map-section">
                    <div id="tracking-loading" class="tracking-loading">
                        <div class="spinner"></div>
                    </div>
                    <div id="tracking-map" style="width: 100%; height: 100%;"></div>
                </div>

                <!-- Bus List Sidebar -->
                <div class="bus-list-sidebar">
                    <div class="bus-list-header">
                        <h5><i class="fas fa-list me-2"></i>Active Buses</h5>
                        <small class="opacity-75"><?php echo count($buses); ?> buses</small>
                    </div>
                    <div class="bus-list-body" id="bus-list-container">
                        <div class="text-center text-muted py-5">
                            <div class="spinner-border spinner-border-sm mb-3" role="status"></div>
                            <div>Loading buses...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info Section -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Tracking Information</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Real-time GPS updates every 3 seconds
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Click on bus markers for details
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Automatic route visualization
                                </li>
                                <li>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Speed and heading indicators
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-keyboard me-2"></i>Keyboard Shortcuts</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <kbd>Ctrl + R</kbd> - Refresh data
                                </li>
                                <li class="mb-2">
                                    <kbd>Click Bus</kbd> - Focus on bus
                                </li>
                                <li>
                                    <kbd>Double Click</kbd> - Zoom to bus
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Include Scripts -->
<script src="/js/maps.js"></script>
<script src="/js/tracking-enhanced.js"></script>
<script src="/js/app.js"></script>

<script>
// Filter functions
function filterBuses(searchTerm) {
    const items = document.querySelectorAll('.bus-list-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(searchTerm.toLowerCase())) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function filterByStatus(status) {
    const items = document.querySelectorAll('.bus-list-item');
    items.forEach(item => {
        if (!status) {
            item.style.display = 'block';
        } else {
            // Filter logic based on status
            item.style.display = 'block';
        }
    });
}

// Update map status
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const statusEl = document.getElementById('map-status');
        if (statusEl) {
            statusEl.textContent = 'Active';
            statusEl.className = 'stat-value text-success';
        }
    }, 2000);
});
</script>

<?php require_once '../includes/footer.php'; ?>

