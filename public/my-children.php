<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();
requireRole('parent');

$database = new Database();
$db = $database->getConnection();

// Get parent's children with bus and route info
$query = "SELECT s.*, rs.stop_name, r.route_name, r.start_time, r.end_time,
                 b.bus_number, b.current_latitude, b.current_longitude, 
                 b.last_location_update, u.full_name as driver_name
          FROM students s
          LEFT JOIN route_stops rs ON s.assigned_stop_id = rs.id
          LEFT JOIN routes r ON rs.route_id = r.id
          LEFT JOIN bus_routes br ON r.id = br.route_id AND br.active = 1
          LEFT JOIN buses b ON br.bus_id = b.id
          LEFT JOIN users u ON b.driver_id = u.id
          WHERE s.parent_id = :parent_id
          ORDER BY s.student_name";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $_SESSION['user_id']);
$stmt->execute();
$children = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-child me-2"></i>My Children</h1>
            </div>

            <?php if (empty($children)): ?>
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>No Children Registered</h5>
                    <p class="mb-0">You don't have any children registered yet. Please contact the school administrator.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($children as $child): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light text-primary border-bottom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-graduate me-2"></i>
                                        <?php echo htmlspecialchars($child['student_name']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong><i class="fas fa-graduation-cap me-2 text-primary"></i>Grade:</strong>
                                        <?php echo htmlspecialchars($child['grade'] ?? 'Not specified'); ?>
                                    </div>

                                    <?php if ($child['stop_name']): ?>
                                        <div class="mb-3">
                                            <strong><i class="fas fa-map-marker-alt me-2 text-danger"></i>Bus Stop:</strong>
                                            <?php echo htmlspecialchars($child['stop_name']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($child['route_name']): ?>
                                        <div class="mb-3">
                                            <strong><i class="fas fa-route me-2 text-info"></i>Route:</strong>
                                            <?php echo htmlspecialchars($child['route_name']); ?>
                                        </div>

                                        <div class="mb-3">
                                            <strong><i class="fas fa-clock me-2 text-warning"></i>Schedule:</strong>
                                            <?php echo date('h:i A', strtotime($child['start_time'])); ?> -
                                            <?php echo date('h:i A', strtotime($child['end_time'])); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($child['bus_number']): ?>
                                        <hr>
                                        <h6 class="text-success"><i class="fas fa-bus me-2"></i>Bus Information</h6>

                                        <div class="mb-2">
                                            <strong>Bus Number:</strong>
                                            <span
                                                class="badge bg-success"><?php echo htmlspecialchars($child['bus_number']); ?></span>
                                        </div>

                                        <div class="mb-2">
                                            <strong>Driver:</strong>
                                            <?php echo htmlspecialchars($child['driver_name'] ?? 'Not assigned'); ?>
                                        </div>

                                        <?php if ($child['current_latitude'] && $child['current_longitude']): ?>
                                            <div class="alert alert-success mt-3 mb-0">
                                                <strong><i class="fas fa-check-circle me-2"></i>Bus is being tracked!</strong><br>
                                                <small>Last update:
                                                    <?php echo date('h:i A', strtotime($child['last_location_update'])); ?></small><br>
                                                <a href="/track-bus.php" class="btn btn-sm btn-success mt-2">
                                                    <i class="fas fa-map-marked-alt me-1"></i>Track Bus Now
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-secondary mt-3 mb-0">
                                                <i class="fas fa-info-circle me-2"></i>Bus location not available yet
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning mt-3 mb-0">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            No bus assigned to this route yet
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