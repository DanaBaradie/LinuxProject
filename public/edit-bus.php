<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';
$bus_id = intval($_GET['id'] ?? 0);

// Get bus details
$query = "SELECT * FROM buses WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $bus_id);
$stmt->execute();
$bus = $stmt->fetch();

if (!$bus) {
    header('Location: /buses.php');
    exit();
}

// Get all drivers
$query = "SELECT u.id, u.full_name, b.bus_number 
          FROM users u 
          LEFT JOIN buses b ON u.id = b.driver_id 
          WHERE u.role = 'driver'";
$drivers = $db->query($query)->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_number = sanitizeInput($_POST['bus_number']);
    $license_plate = sanitizeInput($_POST['license_plate']);
    $capacity = intval($_POST['capacity']);
    $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
    $status = sanitizeInput($_POST['status']);
    
    try {
        $query = "UPDATE buses 
                  SET bus_number = :bus_number,
                      license_plate = :license_plate,
                      capacity = :capacity,
                      driver_id = :driver_id,
                      status = :status
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':bus_number', $bus_number);
        $stmt->bindParam(':license_plate', $license_plate);
        $stmt->bindParam(':capacity', $capacity);
        $stmt->bindParam(':driver_id', $driver_id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $bus_id);
        $stmt->execute();
        
        $success = 'Bus updated successfully!';
        
        // Refresh bus data
        $stmt = $db->prepare("SELECT * FROM buses WHERE id = :id");
        $stmt->bindParam(':id', $bus_id);
        $stmt->execute();
        $bus = $stmt->fetch();
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-edit me-2"></i>Edit Bus</h1>
                <a href="/buses.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Buses
                </a>
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

            <div class="row">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-bus me-2"></i>Bus Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Bus Number *</strong></label>
                                    <input type="text" class="form-control" name="bus_number" required 
                                           value="<?php echo htmlspecialchars($bus['bus_number']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><strong>License Plate</strong></label>
                                    <input type="text" class="form-control" name="license_plate" 
                                           value="<?php echo htmlspecialchars($bus['license_plate'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><strong>Capacity *</strong></label>
                                    <input type="number" class="form-control" name="capacity" required min="1" max="100"
                                           value="<?php echo $bus['capacity']; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><strong>Assign Driver</strong></label>
                                    <select class="form-select" name="driver_id">
                                        <option value="">-- No Driver --</option>
                                        <?php foreach ($drivers as $driver): ?>
                                            <option value="<?php echo $driver['id']; ?>" 
                                                <?php echo ($bus['driver_id'] == $driver['id']) ? 'selected' : ''; ?>
                                                <?php if ($driver['bus_number'] && $driver['id'] != $bus['driver_id']): ?>
                                                    disabled
                                                <?php endif; ?>>
                                                <?php echo htmlspecialchars($driver['full_name']); ?>
                                                <?php if ($driver['bus_number'] && $driver['id'] != $bus['driver_id']): ?>
                                                    (Assigned to <?php echo $driver['bus_number']; ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Drivers already assigned to other buses are disabled</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><strong>Status *</strong></label>
                                    <select class="form-select" name="status" required>
                                        <option value="active" <?php echo $bus['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $bus['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="maintenance" <?php echo $bus['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Current Status</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Bus:</strong> <?php echo htmlspecialchars($bus['bus_number']); ?></p>
                            <p><strong>Capacity:</strong> <?php echo $bus['capacity']; ?> students</p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php echo $bus['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($bus['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <?php if ($bus['current_latitude'] && $bus['current_longitude']): ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>GPS Location</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Latitude:</strong><br><?php echo $bus['current_latitude']; ?></p>
                                <p class="mb-2"><strong>Longitude:</strong><br><?php echo $bus['current_longitude']; ?></p>
                                <p class="mb-0"><strong>Last Update:</strong><br>
                                    <small><?php echo date('M d, Y h:i A', strtotime($bus['last_location_update'])); ?></small>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
