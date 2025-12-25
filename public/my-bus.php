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
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
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
                            <div class="card-header bg-light text-primary border-bottom">
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
                                    <span
                                        class="badge bg-<?php echo $my_bus['status'] === 'active' ? 'success' : 'secondary'; ?>">
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
                            <div class="card-header bg-light text-success border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-location-arrow me-2"></i>Update Location
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Use the button below to automatically detect and update your current GPS location.
                                </div>

                                <button class="btn btn-success btn-lg w-100 mb-3" onclick="updateMyLocation()"
                                    id="updateBtn">
                                    <i class="fas fa-crosshairs me-2"></i>Get My Current Location (iPhone GPS)
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
        const originalHTML = btn.innerHTML;

        console.log('updateMyLocation called');
        console.log('Protocol:', window.location.protocol);
        console.log('Geolocation available:', typeof navigator.geolocation !== 'undefined');

        // Check if geolocation is supported
        if (!navigator.geolocation) {
            status.className = 'alert alert-danger';
            status.style.display = 'block';
            status.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i><strong>Geolocation not supported</strong><br>Your browser does not support GPS location. Please use Manual Entry below.';
            return;
        }

        // Check protocol and warn but don't block (some setups might work)
        const isHTTPS = window.location.protocol === 'https:' || 
                       window.location.hostname === 'localhost' || 
                       window.location.hostname === '127.0.0.1' ||
                       window.location.hostname.includes('192.168.');
        
        if (!isHTTPS) {
            status.className = 'alert alert-warning';
            status.style.display = 'block';
            status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>HTTP Connection Detected</strong><br>iPhone may block GPS on HTTP. If permission prompt doesn\'t appear, try:<br>1. Access via HTTPS<br>2. Use Manual Entry below<br><br><button class="btn btn-sm btn-primary" onclick="tryLocationAnyway()">Try Anyway</button>';
            return;
        }

        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Getting Your iPhone Location...';
        status.style.display = 'block';
        status.className = 'alert alert-info';
        status.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div><strong>Requesting GPS access...</strong><br><small>Please allow location access when prompted by your iPhone. If no prompt appears, check Safari settings.</small>';

        // Check permission state first
        if (navigator.permissions && navigator.permissions.query) {
            navigator.permissions.query({name: 'geolocation'}).then(function(result) {
                console.log('Permission state:', result.state);
                if (result.state === 'denied') {
                    status.className = 'alert alert-warning';
                    status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Location Permission Previously Denied</strong><br>Please enable it in:<br><strong>iPhone Settings → Safari → Location Services</strong><br>Then refresh this page.';
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    return;
                }
                // Continue with location request
                requestLocation();
            }).catch(function() {
                // Permissions API not supported, continue anyway
                requestLocation();
            });
        } else {
            // Permissions API not available, proceed directly
            requestLocation();
        }

        function requestLocation() {
            console.log('Requesting location...');
            
            // Set timeout (longer for iPhone)
            const timeoutId = setTimeout(() => {
                status.className = 'alert alert-warning';
                status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Location request timed out</strong><br>Your iPhone may need more time to get GPS signal. Please try again or use Manual Entry below.';
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }, 20000); // 20 seconds for iPhone

            // Get current position with iPhone-optimized settings
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    console.log('Location received:', position);
                    clearTimeout(timeoutId);
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy; // Accuracy in meters

                    // Update status
                    status.className = 'alert alert-info';
                    status.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div><strong>Location received from iPhone!</strong><br><small>Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6) + ' (Accuracy: ±' + Math.round(accuracy) + 'm)</small><br>Updating on server...';

                    // Send to API
                    sendLocationToServer(lat, lng, btn, status, originalHTML);
                },
                function (error) {
                    console.error('Geolocation error:', error);
                    clearTimeout(timeoutId);
                    status.className = 'alert alert-danger';
                    let errorMessage = '';
                    
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = '<strong>Location Permission Denied</strong><br>';
                            if (window.location.protocol !== 'https:') {
                                errorMessage += 'iPhone requires HTTPS for GPS access. Your browser blocked location access on HTTP.<br><br>';
                                errorMessage += '<strong>Solutions:</strong><br>';
                                errorMessage += '1. Access this site via HTTPS (secure connection)<br>';
                                errorMessage += '2. Use Manual Entry below with coordinates from Maps app<br>';
                            } else {
                                errorMessage += 'You denied location permission or it was previously denied.<br><br>';
                                errorMessage += '<strong>To enable:</strong><br>';
                                errorMessage += '1. Go to <strong>iPhone Settings → Safari → Location Services</strong><br>';
                                errorMessage += '2. Make sure Location Services is ON<br>';
                                errorMessage += '3. Find this website and set it to "Ask" or "Allow"<br>';
                                errorMessage += '4. Refresh this page and try again<br><br>';
                                errorMessage += 'Or use Manual Entry below.';
                            }
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = '<strong>GPS Signal Unavailable</strong><br>';
                            errorMessage += 'Your iPhone cannot get GPS signal. This may happen if:<br>';
                            errorMessage += '• You are indoors or in a building<br>';
                            errorMessage += '• GPS is disabled in Settings<br>';
                            errorMessage += '• Poor signal reception<br><br>';
                            errorMessage += 'Please try again or use Manual Entry below.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = '<strong>GPS Request Timed Out</strong><br>';
                            errorMessage += 'Your iPhone took too long to get GPS coordinates. This may happen if:<br>';
                            errorMessage += '• GPS signal is weak<br>';
                            errorMessage += '• You are in a building or underground<br><br>';
                            errorMessage += 'Please try again or use Manual Entry below.';
                            break;
                        default:
                            errorMessage = '<strong>Error Getting Location</strong><br>' + error.message;
                    }

                    status.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + errorMessage;
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                },
                {
                    enableHighAccuracy: true,  // Use GPS, not just network location
                    timeout: 20000,            // 20 seconds timeout for iPhone
                    maximumAge: 0              // Don't use cached location
                }
            );
        }
    }

    function tryLocationAnyway() {
        const status = document.getElementById('locationStatus');
        status.style.display = 'none';
        updateMyLocation();
    }

    function sendLocationToServer(lat, lng, btn, status, originalHTML) {
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
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                status.className = 'alert alert-success';
                status.innerHTML = '<i class="fas fa-check-circle me-2"></i><strong>Location Updated Successfully!</strong><br>Your iPhone location has been set as the driver location.<br><small>Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6) + '</small><br><br>Parents can now track your bus in real-time.';
                // Reload page after 2 seconds to show updated location
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                status.className = 'alert alert-danger';
                status.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i><strong>Failed to update location</strong><br>' + (data.message || 'Unknown error occurred. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            status.className = 'alert alert-danger';
            status.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i><strong>Error updating location</strong><br>' + error.message + '<br><small>Please check your internet connection and try again.</small>';
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
    }
</script>

<?php require_once '../includes/footer.php'; ?>