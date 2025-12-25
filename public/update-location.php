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

            $success = 'Location updated successfully! Parents can now track your bus.';

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
                <h1 class="h2"><i class="fas fa-location-arrow me-2"></i>Update My Location</h1>
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
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-bus fa-3x mb-3"></i>
                                <h3><?php echo htmlspecialchars($my_bus['bus_number']); ?></h3>
                                <p class="mb-0">Your Assigned Bus</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-3x mb-3"></i>
                                <h3>
                                    <?php if ($my_bus['last_location_update']): ?>
                                        <?php echo date('h:i A', strtotime($my_bus['last_location_update'])); ?>
                                    <?php else: ?>
                                        Never
                                    <?php endif; ?>
                                </h3>
                                <p class="mb-0">Last Update</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm bg-info text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                                <h3>
                                    <?php if ($my_bus['current_latitude']): ?>
                                        Active
                                    <?php else: ?>
                                        Not Set
                                    <?php endif; ?>
                                </h3>
                                <p class="mb-0">GPS Status</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light text-success border-bottom">
                        <h5 class="mb-0"><i class="fas fa-crosshairs me-2"></i>Quick Update</h5>
                    </div>
                    <div class="card-body text-center p-5">
                        <?php if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-lock me-2"></i>
                                <strong>Secure Connection:</strong> GPS location detection is available on HTTPS.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>HTTP Connection Detected:</strong> Most browsers block GPS on HTTP for security. 
                                <br><strong>Solution:</strong> Use the Manual Entry form below, or access this site via HTTPS.
                            </div>
                        <?php endif; ?>

                        <button class="btn btn-success btn-lg px-5 py-3" onclick="updateMyLocation()" id="updateBtn">
                            <i class="fas fa-crosshairs fa-2x mb-2"></i><br>
                            Get My Current Location
                        </button>

                        <div id="locationStatus" class="alert alert-secondary mt-3" style="display: none;">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            Getting your location...
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Tip:</strong> If GPS doesn't work, you can:
                                <ul class="mt-2 mb-0">
                                    <li>Use the Manual Entry form below</li>
                                    <li>Get coordinates from Google Maps (right-click on location → coordinates)</li>
                                    <li>Use your phone's GPS app to get coordinates</li>
                                </ul>
                            </small>
                        </div>

                        <form method="POST" id="locationForm" style="display: none;">
                            <input type="hidden" name="action" value="update_location">
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light text-primary border-bottom">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Manual Location Entry</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-primary">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>How to Get Your Coordinates:</strong>
                            <ol class="mb-0 mt-2">
                                <li><strong>Google Maps:</strong> Right-click on your location → Click the coordinates that appear → Copy them</li>
                                <li><strong>Phone GPS App:</strong> Open any GPS/maps app and note the coordinates</li>
                                <li><strong>Quick Select:</strong> Use the preset locations below for common areas</li>
                            </ol>
                        </div>

                        <?php if ($my_bus['current_latitude'] && $my_bus['current_longitude']): ?>
                            <div class="alert alert-info">
                                <strong><i class="fas fa-map-marker-alt me-2"></i>Current Location:</strong><br>
                                Latitude: <?php echo $my_bus['current_latitude']; ?><br>
                                Longitude: <?php echo $my_bus['current_longitude']; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="form-container">
                            <input type="hidden" name="action" value="update_location">

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Latitude <span class="required">*</span>
                                    </label>
                                    <input type="number" step="0.000001" class="form-control" name="latitude"
                                        placeholder="33.8886" required
                                        value="<?php echo $my_bus['current_latitude'] ?? ''; ?>">
                                    <small class="form-text">Example: 33.8886 (Beirut)</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Longitude <span class="required">*</span>
                                    </label>
                                    <input type="number" step="0.000001" class="form-control" name="longitude"
                                        placeholder="35.4955" required
                                        value="<?php echo $my_bus['current_longitude'] ?? ''; ?>">
                                    <small class="form-text">Example: 35.4955 (Beirut)</small>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-form btn-form-primary">
                                    <i class="fas fa-save me-2"></i>Update My Location Now
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="alert alert-success">
                            <h6 class="mb-3"><i class="fas fa-lightbulb me-2 text-warning"></i>Quick Select - Common Locations</h6>
                            <p class="mb-2 small">Click a location below to auto-fill the coordinates:</p>
                            <div class="row g-2">
                                <div class="col-md-4 col-6">
                                    <button class="btn btn-outline-primary w-100" onclick="setLocation(33.8886, 35.4955)">
                                        <i class="fas fa-map-marker-alt me-1"></i>Beirut Downtown
                                    </button>
                                </div>
                                <div class="col-md-4 col-6">
                                    <button class="btn btn-outline-primary w-100" onclick="setLocation(33.9010, 35.5300)">
                                        <i class="fas fa-map-marker-alt me-1"></i>Achrafieh
                                    </button>
                                </div>
                                <div class="col-md-4 col-6">
                                    <button class="btn btn-outline-primary w-100" onclick="setLocation(33.8547, 35.8623)">
                                        <i class="fas fa-map-marker-alt me-1"></i>Zahle
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-question-circle me-2"></i>
                            <strong>Need Help?</strong> 
                            <a href="https://support.google.com/maps/answer/18539" target="_blank" class="alert-link">
                                Learn how to get coordinates from Google Maps
                            </a>
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
    function setLocation(lat, lng) {
        document.querySelector('input[name="latitude"]').value = lat;
        document.querySelector('input[name="longitude"]').value = lng;
    }

    function updateMyLocation() {
        const btn = document.getElementById('updateBtn');
        const status = document.getElementById('locationStatus');
        const originalHTML = btn.innerHTML;

        // Check if geolocation is supported
        if (!navigator.geolocation) {
            status.className = 'alert alert-danger';
            status.style.display = 'block';
            status.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Geolocation is not supported by your browser. Please use Manual Entry below.';
            return;
        }

        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Getting Location...';
        status.style.display = 'block';
        status.className = 'alert alert-info';
        status.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Getting your location...';

        // Set timeout
        const timeoutId = setTimeout(() => {
            status.className = 'alert alert-warning';
            status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Location request timed out. If you are using HTTP, browser might block GPS. Please use Manual Entry below.';
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }, 15000);

        // Get current position
        navigator.geolocation.getCurrentPosition(
            function (position) {
                clearTimeout(timeoutId);
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Update status
                status.className = 'alert alert-info';
                status.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Updating location on server...';

                // Send to API
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
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        status.className = 'alert alert-success';
                        status.innerHTML = '<i class="fas fa-check-circle me-2"></i>Location updated successfully! Parents can now track your bus.';
                        // Reload page after 2 seconds to show updated location
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        status.className = 'alert alert-danger';
                        status.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Failed to update: ' + (data.message || 'Unknown error');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    status.className = 'alert alert-danger';
                    status.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error updating location: ' + error.message + '. Please try Manual Entry below.';
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                });
            },
            function (error) {
                clearTimeout(timeoutId);
                status.className = 'alert alert-danger';
                let errorMessage = '';
                let showManualEntry = true;
                
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = '<strong>Location Permission Denied</strong><br>';
                        if (window.location.protocol !== 'https:') {
                            errorMessage += 'Your browser blocks GPS on HTTP connections for security. ';
                        } else {
                            errorMessage += 'Your browser blocked location access. ';
                        }
                        errorMessage += '<br><br><strong>Solutions:</strong><br>';
                        errorMessage += '1. <strong>Use Manual Entry</strong> below (recommended for HTTP)<br>';
                        errorMessage += '2. Enable location permission in browser settings<br>';
                        errorMessage += '3. Access this site via HTTPS if available<br>';
                        errorMessage += '<br><small>You can get coordinates from Google Maps by right-clicking on your location.</small>';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = '<strong>Location Unavailable</strong><br>';
                        errorMessage += 'GPS signal is not available. Please use Manual Entry below.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = '<strong>Request Timed Out</strong><br>';
                        errorMessage += 'Location request took too long. Please use Manual Entry below.';
                        break;
                    default:
                        errorMessage = '<strong>Error Getting Location</strong><br>' + error.message;
                }

                if (window.location.protocol !== 'https:') {
                    errorMessage = '<div class="mb-2"><i class="fas fa-exclamation-triangle text-warning me-2"></i><strong>HTTP Connection Detected</strong></div>' + errorMessage;
                }

                status.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + errorMessage;
                
                // Scroll to manual entry section
                if (showManualEntry) {
                    setTimeout(() => {
                        const manualSection = document.querySelector('.card:last-of-type');
                        if (manualSection) {
                            manualSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            manualSection.style.border = '2px solid #0d6efd';
                            manualSection.style.boxShadow = '0 0 10px rgba(13, 110, 253, 0.3)';
                            setTimeout(() => {
                                manualSection.style.border = '';
                                manualSection.style.boxShadow = '';
                            }, 3000);
                        }
                    }, 500);
                }
                
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
</script>

<?php require_once '../includes/footer.php'; ?>