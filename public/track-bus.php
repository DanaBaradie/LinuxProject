<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();
requireRole('parent');

$database = new Database();
$db = $database->getConnection();

// Get buses assigned to parent's children
$query = "SELECT DISTINCT b.*, u.full_name as driver_name, r.route_name
          FROM students s
          LEFT JOIN route_stops rs ON s.assigned_stop_id = rs.id
          LEFT JOIN routes r ON rs.route_id = r.id
          LEFT JOIN bus_routes br ON r.id = br.route_id AND br.active = 1
          LEFT JOIN buses b ON br.bus_id = b.id
          LEFT JOIN users u ON b.driver_id = u.id
          WHERE s.parent_id = :parent_id AND b.id IS NOT NULL
          ORDER BY b.bus_number";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $_SESSION['user_id']);
$stmt->execute();
$buses = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-map-marked-alt me-2"></i>Track School Bus</h1>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
            </div>

            <?php if (empty($buses)): ?>
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>No Buses to Track</h5>
                    <p class="mb-0">Your children don't have buses assigned yet, or the buses are not active.</p>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Tracking <strong><?php echo count($buses); ?></strong> bus(es) for your children
                </div>

                <div class="row">
                    <?php foreach ($buses as $bus): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light text-success border-bottom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bus me-2"></i>
                                        Bus <?php echo htmlspecialchars($bus['bus_number']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong><i class="fas fa-route me-2 text-info"></i>Route:</strong>
                                        <?php echo htmlspecialchars($bus['route_name'] ?? 'N/A'); ?>
                                    </div>

                                    <div class="mb-3">
                                        <strong><i class="fas fa-user-tie me-2 text-primary"></i>Driver:</strong>
                                        <?php echo htmlspecialchars($bus['driver_name'] ?? 'Not assigned'); ?>
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
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-map-marker-alt me-2"></i>Current GPS Location:</h6>
                                            <strong>Latitude:</strong> <?php echo $bus['current_latitude']; ?><br>
                                            <strong>Longitude:</strong> <?php echo $bus['current_longitude']; ?>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <a href="https://www.google.com/maps?q=<?php echo $bus['current_latitude']; ?>,<?php echo $bus['current_longitude']; ?>"
                                                target="_blank" class="btn btn-primary">
                                                <i class="fas fa-map me-2"></i>View on Google Maps
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-secondary">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Bus location not available yet. The driver hasn't updated their location.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>