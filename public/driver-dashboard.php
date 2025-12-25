<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();
requireRole('driver');

$database = new Database();
$db = $database->getConnection();

$driver_id = $_SESSION['user_id'];

// Get driver's assigned bus
$query = "SELECT * FROM buses WHERE driver_id = :driver_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':driver_id', $driver_id);
$stmt->execute();
$bus = $stmt->fetch();

// Get route information if bus is assigned
$routes = [];
$students = [];
if ($bus) {
    // Get routes
    $query = "SELECT r.*, COUNT(rs.id) as stop_count
              FROM routes r
              INNER JOIN bus_routes br ON r.id = br.route_id
              LEFT JOIN route_stops rs ON r.id = rs.route_id
              WHERE br.bus_id = :bus_id
              GROUP BY r.id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_id', $bus['id']);
    $stmt->execute();
    $routes = $stmt->fetchAll();

    // Get students on this bus
    $query = "SELECT s.*, rs.stop_name, u.full_name as parent_name, u.phone as parent_phone
              FROM students s
              INNER JOIN route_stops rs ON s.assigned_stop_id = rs.id
              INNER JOIN routes r ON rs.route_id = r.id
              INNER JOIN bus_routes br ON r.id = br.route_id
              INNER JOIN users u ON s.parent_id = u.id
              WHERE br.bus_id = :bus_id
              ORDER BY rs.stop_order, s.student_name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_id', $bus['id']);
    $stmt->execute();
    $students = $stmt->fetchAll();
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>Driver Dashboard</h1>
                <div>
                    <?php if ($bus): ?>
                        <button class="btn btn-success me-2" onclick="updateGPSLocation()">
                            <i class="fas fa-map-marker-alt me-2"></i>Update GPS Location
                        </button>
                    <?php endif; ?>
                    <a href="/profile.php" class="btn btn-outline-primary">
                        <i class="fas fa-user me-2"></i>My Profile
                    </a>
                </div>
            </div>

            <div id="gps-alert" class="alert alert-success alert-dismissible fade" role="alert" style="display:none;">
                <i class="fas fa-check-circle me-2"></i><span id="gps-message"></span>
                <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'"></button>
            </div>

            <?php if (!$bus): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>No bus assigned yet.</strong> Please contact the administrator to assign you to a bus.
                </div>
            <?php else: ?>
                <!-- Bus Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light text-primary border-bottom">
                                <h5 class="mb-0"><i class="fas fa-bus me-2"></i>My Bus</h5>
                            </div>
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo htmlspecialchars($bus['bus_number']); ?></h3>

                                <p class="mb-2">
                                    <i class="fas fa-id-card text-secondary me-2"></i>
                                    <strong>License Plate:</strong>
                                    <?php echo htmlspecialchars($bus['license_plate'] ?? 'N/A'); ?>
                                </p>

                                <p class="mb-2">
                                    <i class="fas fa-users text-info me-2"></i>
                                    <strong>Capacity:</strong> <?php echo $bus['capacity']; ?> students
                                </p>

                                <p class="mb-2">
                                    <i
                                        class="fas fa-circle text-<?php echo $bus['status'] === 'active' ? 'success' : ($bus['status'] === 'maintenance' ? 'warning' : 'secondary'); ?> me-2"></i>
                                    <strong>Status:</strong> <?php echo ucfirst($bus['status']); ?>
                                </p>

                                <?php if ($bus['last_location_update']): ?>
                                    <p class="mb-2">
                                        <i class="fas fa-clock text-warning me-2"></i>
                                        <strong>Last GPS Update:</strong><br>
                                        <small><?php echo date('M d, Y H:i:s', strtotime($bus['last_location_update'])); ?></small>
                                    </p>
                                <?php endif; ?>

                                <div class="d-grid gap-2 mt-3">
                                    <a href="/my-bus.php" class="btn btn-outline-primary">
                                        <i class="fas fa-info-circle me-2"></i>View Full Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light text-success border-bottom">
                                <h5 class="mb-0"><i class="fas fa-route me-2"></i>Routes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($routes)): ?>
                                    <p class="text-muted">No routes assigned yet</p>
                                <?php else: ?>
                                    <?php $routeCount = count($routes); ?>
                                    <?php foreach ($routes as $index => $route): ?>
                                        <div class="mb-3">
                                            <h6><i
                                                    class="fas fa-map-signs text-warning me-2"></i><?php echo htmlspecialchars($route['route_name']); ?>
                                            </h6>
                                            <p class="mb-1 small">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo htmlspecialchars($route['start_time']); ?> -
                                                <?php echo htmlspecialchars($route['end_time']); ?>
                                            </p>
                                            <p class="mb-1 small">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo $route['stop_count']; ?> stops
                                            </p>
                                            <span class="badge bg-<?php echo $route['active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $route['active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </div>
                                        <?php if ($index < $routeCount - 1): ?>
                                            <hr><?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students List -->
                <h4 class="mb-3"><i class="fas fa-user-graduate me-2"></i>Students on My Bus
                    (<?php echo count($students); ?>)</h4>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <p class="text-muted text-center py-3">No students assigned yet</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student</th>
                                            <th class="d-none d-md-table-cell">Grade</th>
                                            <th>Stop</th>
                                            <th class="d-none d-md-table-cell">Parent</th>
                                            <th>Contact</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-user-graduate text-primary me-2"></i>
                                                    <strong><?php echo htmlspecialchars($student['student_name']); ?></strong>
                                                    <!-- Show minimal parent info on mobile -->
                                                    <div class="d-md-none small text-muted mt-1">
                                                        <i
                                                            class="fas fa-user me-1"></i><?php echo htmlspecialchars($student['parent_name']); ?>
                                                    </div>
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    <?php echo htmlspecialchars($student['grade'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                                    <?php echo htmlspecialchars($student['stop_name']); ?>
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    <?php echo htmlspecialchars($student['parent_name']); ?></td>
                                                <td>
                                                    <?php if ($student['parent_phone']): ?>
                                                        <a href="tel:<?php echo htmlspecialchars($student['parent_phone']); ?>"
                                                            class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-phone"></i><span
                                                                class="d-none d-lg-inline ms-1"><?php echo htmlspecialchars($student['parent_phone']); ?></span>
                                                            <span class="d-lg-none">Call</span>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
    function updateGPSLocation() {
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;

        // Prevent double clicking
        if (btn.disabled) return;

        // Check if geolocation is supported
        if (!navigator.geolocation) {
            showGPSAlert('Geolocation is not supported by your browser. Please use "Update Location" page for manual entry.', 'danger');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Getting Location...';

        // Set a timeout of 15 seconds
        const timeoutId = setTimeout(() => {
            showGPSAlert('Location request timed out. If you are using HTTP, browser might block GPS. Please use "Update Location" page to update manually.', 'warning');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }, 15000);

        navigator.geolocation.getCurrentPosition(
            function (position) {
                clearTimeout(timeoutId);
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Update button to show uploading
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating Location...';

                // Send to server
                fetch('/api/location/update-quick.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        latitude: lat,
                        longitude: lng
                    })
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showGPSAlert('GPS location updated successfully! Refreshing page...', 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showGPSAlert('Failed to update location: ' + (data.message || 'Unknown error'), 'danger');
                            btn.disabled = false;
                            btn.innerHTML = originalHTML;
                        }
                    })
                    .catch(error => {
                        console.error('GPS update error:', error);
                        showGPSAlert('Error updating location: ' + error.message + '. Please try again or use "Update Location" page.', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    });
            },
            function (error) {
                clearTimeout(timeoutId);
                let message = '';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        message = '<strong>Location Permission Denied</strong><br>';
                        if (window.location.protocol !== 'https:') {
                            message += 'Your browser blocks GPS on HTTP connections. ';
                        } else {
                            message += 'Your browser blocked location access. ';
                        }
                        message += '<br><strong>Solution:</strong> Go to <a href="/update-location.php" class="alert-link">Update Location</a> page and use Manual Entry.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = '<strong>Location Unavailable</strong><br>GPS signal not available. Use <a href="/update-location.php" class="alert-link">Update Location</a> page for manual entry.';
                        break;
                    case error.TIMEOUT:
                        message = '<strong>Request Timed Out</strong><br>Please use <a href="/update-location.php" class="alert-link">Update Location</a> page for manual entry.';
                        break;
                    default:
                        message = '<strong>Error:</strong> ' + error.message + '<br>Use <a href="/update-location.php" class="alert-link">Update Location</a> page for manual entry.';
                }

                // If on HTTP, add warning
                if (window.location.protocol !== 'https:') {
                    message = '<div class="mb-2"><i class="fas fa-exclamation-triangle text-warning me-2"></i><strong>HTTP Connection</strong> - GPS blocked for security</div>' + message;
                }

                showGPSAlert(message, 'danger');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    }

    function showGPSAlert(message, type) {
        const alert = document.getElementById('gps-alert');
        const messageSpan = document.getElementById('gps-message');

        alert.className = `alert alert-${type} alert-dismissible fade show`;
        messageSpan.innerHTML = message; // Changed to innerHTML to support links
        alert.style.display = 'block';

        // Scroll to alert
        alert.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Auto-hide after longer time for important messages
        const hideDelay = type === 'danger' ? 8000 : 5000;
        setTimeout(() => {
            alert.style.display = 'none';
        }, hideDelay);
    }
</script>

<?php require_once '../includes/footer.php'; ?>