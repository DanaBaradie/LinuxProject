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
        $route_name = sanitizeInput($_POST['route_name']);
        $description = sanitizeInput($_POST['description']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        
        try {
            $query = "INSERT INTO routes (route_name, description, start_time, end_time, active) 
                      VALUES (:route_name, :description, :start_time, :end_time, 1)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':route_name', $route_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':start_time', $start_time);
            $stmt->bindParam(':end_time', $end_time);
            $stmt->execute();
            
            $success = 'Route added successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $route_id = intval($_POST['route_id']);
        
        try {
            $query = "DELETE FROM routes WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $route_id);
            $stmt->execute();
            
            $success = 'Route deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($action === 'toggle_active') {
        $route_id = intval($_POST['route_id']);
        $active = intval($_POST['active']);
        
        try {
            $query = "UPDATE routes SET active = :active WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':active', $active);
            $stmt->bindParam(':id', $route_id);
            $stmt->execute();
            
            $success = 'Route status updated!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get all routes
$query = "SELECT * FROM routes ORDER BY route_name";
$routes = $db->query($query)->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-route me-2"></i>Manage Routes</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                    <i class="fas fa-plus me-2"></i>Add New Route
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
                    <?php if (empty($routes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-route fa-4x text-muted mb-3"></i>
                            <h4>No Routes Added Yet</h4>
                            <p class="text-muted">Click "Add New Route" to get started</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Route Name</th>
                                        <th>Description</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($routes as $route): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($route['route_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($route['description'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('h:i A', strtotime($route['start_time'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($route['end_time'])); ?></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                                    <input type="hidden" name="active" value="<?php echo $route['active'] ? 0 : 1; ?>">
                                                    <button type="submit" class="btn btn-sm btn-<?php echo $route['active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $route['active'] ? 'Active' : 'Inactive'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this route?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
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

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-route me-2"></i>Add New Route</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-route me-1"></i>Route Name <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" name="route_name" required 
                               placeholder="e.g., Morning Route A">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-align-left me-1"></i>Description
                        </label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Brief description of the route"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-clock me-1"></i>Start Time <span class="required">*</span>
                            </label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-clock me-1"></i>End Time <span class="required">*</span>
                            </label>
                            <input type="time" class="form-control" name="end_time" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Route
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
