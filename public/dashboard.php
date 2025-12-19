<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$role = getUserRole();
$stats = [];

try {
    if ($role === 'admin') {
        // Get statistics
        $query = "SELECT COUNT(*) as count FROM buses WHERE status = 'active'";
        $stats['active_buses'] = $db->query($query)->fetch()['count'];

        $query = "SELECT COUNT(*) as count FROM users WHERE role = 'driver'";
        $stats['total_drivers'] = $db->query($query)->fetch()['count'];

        $query = "SELECT COUNT(*) as count FROM users WHERE role = 'parent'";
        $stats['total_parents'] = $db->query($query)->fetch()['count'];

        $query = "SELECT COUNT(*) as count FROM routes WHERE active = 1";
        $stats['active_routes'] = $db->query($query)->fetch()['count'];

        // Get recent buses
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

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
            </div>

            <?php if ($role === 'admin'): ?>
                <!-- Admin Dashboard -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Active Buses</h6>
                                        <h2 class="mb-0"><?php echo $stats['active_buses']; ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-bus fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Drivers</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_drivers']; ?></h2>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-user-tie fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Parents</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_parents']; ?></h2>
                                    </div>
                                    <div class="text-info">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Active Routes</h6>
                                        <h2 class="mb-0"><?php echo $stats['active_routes']; ?></h2>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-route fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-bus me-2"></i>Recent Buses</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($buses)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Bus Number</th>
                                            <th>Driver</th>
                                            <th>Capacity</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($buses as $bus): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($bus['driver_name'] ?? 'Unassigned'); ?></td>
                                                <td><?php echo $bus['capacity']; ?> students</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $bus['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($bus['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No buses added yet. <a href="/buses.php">Add your first bus</a></p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($role === 'parent'): ?>
                <!-- Parent Dashboard -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>Welcome Parent!</h5>
                    <p class="mb-0">You have <?php echo $stats['my_children']; ?> child(ren) registered. Track their bus location in real-time.</p>
                </div>

            <?php elseif ($role === 'driver'): ?>
                <!-- Driver Dashboard -->
                <div class="alert alert-success">
                    <h5><i class="fas fa-bus me-2"></i>Welcome Driver!</h5>
                    <?php if (isset($my_bus)): ?>
                        <p class="mb-0">Your assigned bus: <strong><?php echo htmlspecialchars($my_bus['bus_number']); ?></strong></p>
                    <?php else: ?>
                        <p class="mb-0">No bus assigned yet. Please contact administrator.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
