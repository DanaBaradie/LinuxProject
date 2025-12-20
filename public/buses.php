<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $bus_number = sanitizeInput($_POST['bus_number']);
        $license_plate = sanitizeInput($_POST['license_plate']);
        $capacity = intval($_POST['capacity']);
        $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
        
        try {
            $query = "INSERT INTO buses (bus_number, license_plate, capacity, driver_id, status) 
                      VALUES (:bus_number, :license_plate, :capacity, :driver_id, 'active')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':bus_number', $bus_number);
            $stmt->bindParam(':license_plate', $license_plate);
            $stmt->bindParam(':capacity', $capacity);
            $stmt->bindParam(':driver_id', $driver_id);
            $stmt->execute();
            
            $success = 'Bus added successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $bus_id = intval($_POST['bus_id']);
        
        try {
            $query = "DELETE FROM buses WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $bus_id);
            $stmt->execute();
            
            $success = 'Bus deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($action === 'update_status') {
        $bus_id = intval($_POST['bus_id']);
        $status = sanitizeInput($_POST['status']);
        
        try {
            $query = "UPDATE buses SET status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $bus_id);
            $stmt->execute();
            
            $success = 'Bus status updated!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get all buses
$query = "SELECT b.*, u.full_name as driver_name 
          FROM buses b 
          LEFT JOIN users u ON b.driver_id = u.id 
          ORDER BY b.bus_number";
$buses = $db->query($query)->fetchAll();

// Get available drivers (not assigned to any bus)
$query = "SELECT u.id, u.full_name 
          FROM users u 
          WHERE u.role = 'driver' 
          AND u.id NOT IN (SELECT driver_id FROM buses WHERE driver_id IS NOT NULL)";
$available_drivers = $db->query($query)->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-bus me-2"></i>Manage Buses</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBusModal">
                    <i class="fas fa-plus me-2"></i>Add New Bus
                </button>
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

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($buses)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bus fa-4x text-muted mb-3"></i>
                            <h4>No Buses Added Yet</h4>
                            <p class="text-muted">Click "Add New Bus" to get started</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Bus Number</th>
                                        <th>License Plate</th>
                                        <th>Capacity</th>
                                        <th>Driver</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($buses as $bus): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($bus['license_plate'] ?? 'N/A'); ?></td>
                                            <td><?php echo $bus['capacity']; ?> students</td>
                                            <td>
                                                <?php if ($bus['driver_name']): ?>
                                                    <i class="fas fa-user-check text-success me-1"></i>
                                                    <?php echo htmlspecialchars($bus['driver_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="fas fa-user-times me-1"></i>Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                                        <option value="active" <?php echo $bus['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $bus['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                        <option value="maintenance" <?php echo $bus['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td>
                                                <a href="/edit-bus.php?id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-info me-1">
							<i class="fas fa-edit"></i>
						</a>
						<form method="POST" class="d-inline" onsubmit="return confirm('Delete this bus?')">

                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Bus Modal -->
<div class="modal fade" id="addBusModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bus me-2"></i>Add New Bus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-hashtag me-1"></i>Bus Number <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" name="bus_number" required 
                               placeholder="e.g., BUS-001">
                        <small class="form-text">Unique identifier for this bus</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-id-card me-1"></i>License Plate
                            </label>
                            <input type="text" class="form-control" name="license_plate" 
                                   placeholder="e.g., ABC-1234">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-users me-1"></i>Capacity <span class="required">*</span>
                            </label>
                            <input type="number" class="form-control" name="capacity" required 
                                   min="1" max="100" placeholder="Number of students">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-tie me-1"></i>Assign Driver
                        </label>
                        <select class="form-select" name="driver_id">
                            <option value="">-- No Driver Yet --</option>
                            <?php foreach ($available_drivers as $driver): ?>
                                <option value="<?php echo $driver['id']; ?>">
                                    <?php echo htmlspecialchars($driver['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">Only unassigned drivers are shown</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-form btn-form-primary">
                        <i class="fas fa-plus me-2"></i>Add Bus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
