<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();
requireRole('driver');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Get driver's assigned bus
$query = "SELECT * FROM buses WHERE driver_id = :driver_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':driver_id', $_SESSION['user_id']);
$stmt->execute();
$my_bus = $stmt->fetch();

// Handle location update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_location') {
    if ($my_bus) {
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        
        try {
            $query = "UPDATE buses 
                      SET current_latitude = :lat, 
                          current_longitude = :lng, 
                          last_location_update = NOW() 
                      WHERE id = :bus_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':lat', $latitude);
            $stmt->bindParam(':lng', $longitude);
            $stmt->bindParam(':bus_id', $my_bus['id']);
            $stmt->execute();
            
            $success = 'Location updated successfully!';
            
            // Refresh bus data
            $stmt = $db->prepare("SELECT * FROM buses WHERE driver_id = :driver_id");
            $stmt->bindParam(':driver_id', $_SESSION['user_id']);
            $stmt->execute();
            $my_bus = $stmt->fetch();
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-bus me-2"></i>My Bus</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($my_bus): ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-bus me-2"></i>Bus Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Bus Number:</strong>
                                    <h4 class="text-primary"><?php echo htmlspecialchars($my_bus['bus_number']); ?></h4>
                                </div>
                                
                                <div class="mb-3">
                                    <strong><i class="fas fa-users me-2"></i>Capacity:</strong>
                                    <?php echo $my_bus['capacity']; ?> students
                                </div>
                                
                                <div class="mb-3">
                                    <strong><i class="fas fa-id-card me-2"></i>License Plate:</strong>
                                    <?php echo htmlspecialchars($my_bus['license_plate'] ?? 'N/A'); ?>
                                </div>
                                
                                <div class="mb-3">
                                    <strong><i class="fas fa-circle me-2 text-success"></i>Status:</strong>
                                    <span class="badge bg-<?php echo $my_bus['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($my_bus['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong><i class="fas fa-clock me-2"></i>Last Location Update:</strong><br>
                                    <?php if ($my_bus['last_location_update']): ?>
                                        <?php echo date('M d, Y h:i A', strtotime($my_bus['last_location_update'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never updated</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($my_bus['current_latitude'] && $my_bus['current_longitude']): ?>
                                    <div class="alert alert-info">
                                        <strong><i class="fas fa-map-marker-alt me-2"></i>Current GPS:</strong><br>
                                        Lat: <?php echo $my_bus['current_latitude']; ?><br>
                                        Lng: <?php echo $my_bus['current_longitude']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-location-arrow me-2"></i>Update Location
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Use the button below to automatically detect and update your current GPS location.
                                </div>

                                <button class="btn btn-success btn-lg w-100 mb-3" onclick="updateMyLocation()" id="updateBtn">
                                    <i class="fas fa-crosshairs me-2"></i>Get My Current Location
                                </button>

                                <div id="locationStatus" class="alert alert-secondary" style="display: none;">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Getting your location...
                                </div>

                                <form method="POST" id="locationForm" style="display: none;">
                                    <input type="hidden" name="action" value="update_location">
                                    <input type="hidden" name="latitude" id="latitude">
                                    <input type="hidden" name="longitude" id="longitude">
                                </form>

                                <hr>

                                <h6>Manual Entry (Optional)</h6>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_location">
                                    <div class="mb-3">
                                        <label class="form-label">Latitude</label>
                                        <input type="number" step="0.000001" class="form-control" name="latitude" 
                                               placeholder="33.8886" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Longitude</label>
                                        <input type="number" step="0.000001" class="form-control" name="longitude" 
                                               placeholder="35.4955" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i>Update Manually
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>No Bus Assigned</h5>
                    <p class="mb-0">You don't have a bus assigned yet. Please contact the administrator.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
function updateMyLocation() {
    const btn = document.getElementById('updateBtn');
    const status = document.getElementById('locationStatus');
    
    if (navigator.geolocation) {
        btn.disabled = true;
        status.style.display = 'block';
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('latitude').value = position.coords.latitude;
                document.getElementById('longitude').value = position.coords.longitude;
                document.getElementById('locationForm').submit();
            },
            function(error) {
                status.className = 'alert alert-danger';
                status.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error: ' + error.message;
                btn.disabled = false;
            }
        );
    } else {
        alert('Geolocation is not supported by your browser');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
