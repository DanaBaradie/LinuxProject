<?php
/**
 * Parent Bus Tracking Page
 * 
 * Real-time tracking of child's bus with notifications
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();
requireRole('parent');

$database = new Database();
$db = $database->getConnection();
$userId = $_SESSION['user_id'];

// Get parent's children and their buses
try {
    $query = "SELECT DISTINCT b.id, b.bus_number, s.student_name, rs.stop_name
              FROM buses b
              INNER JOIN bus_routes br ON b.id = br.bus_id
              INNER JOIN routes r ON br.route_id = r.id
              INNER JOIN route_stops rs ON r.id = rs.route_id
              INNER JOIN students s ON rs.id = s.assigned_stop_id
              WHERE s.parent_id = :parent_id AND b.status = 'active'
              ORDER BY b.bus_number";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':parent_id', $userId);
    $stmt->execute();
    $buses = $stmt->fetchAll();
} catch (Exception $e) {
    $buses = [];
    error_log("Error loading buses: " . $e->getMessage());
}

require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-map-marker-alt me-2"></i>Track Bus</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshMap()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>

            <?php if (empty($buses)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>No Buses Assigned:</strong> Your children don't have buses assigned yet. Please contact the administrator.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Live Tracking:</strong> Map updates automatically every 5 seconds. You'll receive notifications when the bus is nearby.
                </div>

                <!-- Bus Selection -->
                <?php if (count($buses) > 1): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <label class="form-label"><strong>Select Bus to Track:</strong></label>
                        <select class="form-select" id="busSelector" onchange="selectBus()">
                            <option value="">All Buses</option>
                            <?php foreach ($buses as $bus): ?>
                                <option value="<?php echo $bus['id']; ?>">
                                    Bus <?php echo htmlspecialchars($bus['bus_number']); ?> 
                                    (<?php echo htmlspecialchars($bus['student_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Map Container -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div id="busMap" class="map-container"></div>
                    </div>
                </div>

                <!-- Bus Information -->
                <div class="row">
                    <?php foreach ($buses as $bus): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bus me-2"></i>
                                        Bus <?php echo htmlspecialchars($bus['bus_number']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Student:</strong> <?php echo htmlspecialchars($bus['student_name']); ?></p>
                                    <p><strong>Stop:</strong> <?php echo htmlspecialchars($bus['stop_name']); ?></p>
                                    <div id="busInfo-<?php echo $bus['id']; ?>">
                                        <p class="text-muted"><small>Loading bus information...</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Include Maps JavaScript -->
<script src="/frontend/js/maps.js"></script>
<script src="/frontend/js/app.js"></script>

<script>
// Initialize map manager
let mapManager;
let selectedBusId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const apiKey = '<?php echo GOOGLE_MAPS_API_KEY; ?>';
    mapManager = new BusMapManager('busMap', {
        apiKey: apiKey,
        updateInterval: 5000,
        autoUpdate: true
    });
    
    // Load bus information
    loadBusInfo();
    setInterval(loadBusInfo, 5000);
});

function selectBus() {
    const selector = document.getElementById('busSelector');
    selectedBusId = selector.value || null;
    
    if (selectedBusId) {
        mapManager.busId = selectedBusId;
    } else {
        mapManager.busId = null;
    }
    
    mapManager.loadBuses();
}

function refreshMap() {
    if (mapManager) {
        mapManager.loadBuses();
        loadBusInfo();
        showToast('Map refreshed', 'success');
    }
}

async function loadBusInfo() {
    const buses = <?php echo json_encode(array_column($buses, 'id')); ?>;
    
    buses.forEach(async (busId) => {
        try {
            const response = await fetch(`/backend/api/gps/live.php?bus_id=${busId}`);
            const data = await response.json();
            
            if (data.success && data.data.buses && data.data.buses.length > 0) {
                const bus = data.data.buses[0];
                displayBusInfo(busId, bus);
            }
        } catch (error) {
            console.error('Error loading bus info:', error);
        }
    });
}

function displayBusInfo(busId, bus) {
    const container = document.getElementById(`busInfo-${busId}`);
    if (!container) return;
    
    const hasLocation = bus.location && bus.location.latitude && bus.location.longitude;
    const status = hasLocation 
        ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Tracking</span>'
        : '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>No GPS</span>';
    
    let html = `
        <p><strong>Status:</strong> ${status}</p>
    `;
    
    if (hasLocation) {
        html += `
            <p><strong>Location:</strong><br>
            <small>Lat: ${bus.location.latitude.toFixed(6)}<br>
            Lng: ${bus.location.longitude.toFixed(6)}</small></p>
        `;
        
        if (bus.speed) {
            html += `<p><strong>Speed:</strong> ${bus.speed.toFixed(1)} km/h</p>`;
        }
        
        if (bus.last_update) {
            html += `<p><strong>Last Update:</strong><br><small>${bus.last_update}</small></p>`;
        }
    } else {
        html += '<p class="text-muted"><small>Waiting for GPS update...</small></p>';
    }
    
    container.innerHTML = html;
}
</script>

<?php require_once '../../includes/footer.php'; ?>

