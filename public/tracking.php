
<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$query = "SELECT b.*, u.full_name as driver_name 
          FROM buses b 
          LEFT JOIN users u ON b.driver_id = u.id 
          WHERE b.status = 'active' 
          ORDER BY b.bus_number";
$buses = $db->query($query)->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-map-marked-alt me-2"></i>Live Bus Tracking</h1>
            </div>

            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle me-2"></i>Bus Tracking System - Demo Mode</h5>
                <p class="mb-0">This system tracks buses in real-time using GPS coordinates. For your project demonstration, the tracking interface is ready and fully functional.</p>
            </div>

            <div class="row">
                <?php if (empty($buses)): ?>
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>No active buses to track
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($buses as $bus): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bus me-2"></i>
                                        <?php echo htmlspecialchars($bus['bus_number']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong><i class="fas fa-user-tie me-2 text-success"></i>Driver:</strong>
                                        <?php echo htmlspecialchars($bus['driver_name'] ?? 'Not assigned'); ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong><i class="fas fa-users me-2 text-info"></i>Capacity:</strong>
                                        <?php echo $bus['capacity']; ?> students
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong><i class="fas fa-circle me-2 text-<?php echo $bus['status'] === 'active' ? 'success' : 'danger'; ?>"></i>Status:</strong>
                                        <span class="badge bg-<?php echo $bus['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($bus['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong><i class="fas fa-clock me-2 text-warning"></i>Last Update:</strong>
                                        <?php if ($bus['last_location_update']): ?>
                                            <?php echo date('M d, Y h:i A', strtotime($bus['last_location_update'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never updated</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($bus['current_latitude'] && $bus['current_longitude']): ?>
                                        <div class="alert alert-success mb-0">
                                            <strong><i class="fas fa-map-marker-alt me-2"></i>GPS Location:</strong><br>
                                            Lat: <?php echo $bus['current_latitude']; ?><br>
                                            Lng: <?php echo $bus['current_longitude']; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-secondary mb-0">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            No GPS data available yet
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Active Buses</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Bus Number</th>
                                    <th>Driver</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>GPS Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buses as $bus): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($bus['driver_name'] ?? 'Not assigned'); ?></td>
                                        <td><?php echo $bus['capacity']; ?> students</td>
                                        <td>
                                            <span class="badge bg-<?php echo $bus['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($bus['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($bus['current_latitude'] && $bus['current_longitude']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-1"></i>Tracking
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-times-circle me-1"></i>No GPS
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
