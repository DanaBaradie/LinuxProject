<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();
requireRole('parent');

$database = new Database();
$db = $database->getConnection();

$parent_id = $_SESSION['user_id'];

// Get parent's children with bus and stop information
$query = "SELECT s.*, 
          rs.stop_name, rs.latitude as stop_lat, rs.longitude as stop_lng,
          r.route_name, r.start_time, r.end_time,
          b.bus_number, b.current_latitude, b.current_longitude, b.status as bus_status,
          u.full_name as driver_name, u.phone as driver_phone
          FROM students s
          LEFT JOIN route_stops rs ON s.assigned_stop_id = rs.id
          LEFT JOIN routes r ON rs.route_id = r.id
          LEFT JOIN bus_routes br ON r.id = br.route_id
          LEFT JOIN buses b ON br.bus_id = b.id
          LEFT JOIN users u ON b.driver_id = u.id
          WHERE s.parent_id = :parent_id
          ORDER BY s.student_name";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $parent_id);
$stmt->execute();
$children = $stmt->fetchAll();

// Get recent notifications
$query = "SELECT * FROM notifications 
          WHERE parent_id = :parent_id 
          ORDER BY created_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $parent_id);
$stmt->execute();
$notifications = $stmt->fetchAll();

// Get unread notification count
$query = "SELECT COUNT(*) as unread_count FROM notifications 
          WHERE parent_id = :parent_id AND is_read = FALSE";
$stmt = $db->prepare($query);
$stmt->bindParam(':parent_id', $parent_id);
$stmt->execute();
$unread_count = $stmt->fetch()['unread_count'];

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-home me-2"></i>Parent Dashboard</h1>
                <div>
                    <a href="/my-children.php" class="btn btn-primary me-2">
                        <i class="fas fa-user-graduate me-2"></i>My Children
                    </a>
                    <a href="/notifications.php" class="btn btn-outline-info">
                        <i class="fas fa-bell me-2"></i>Notifications
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <?php if (empty($children)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>No children registered yet.</strong> Please contact the administrator to add your children to
                    the system.
                </div>
            <?php else: ?>
                <!-- Children Overview -->
                <h4 class="mb-3"><i class="fas fa-users me-2"></i>My Children</h4>
                <div class="row mb-4">
                    <?php foreach ($children as $child): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-graduate me-2"></i>
                                        <?php echo htmlspecialchars($child['student_name']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2">
                                        <i class="fas fa-graduation-cap text-primary me-2"></i>
                                        <strong>Grade:</strong> <?php echo htmlspecialchars($child['grade'] ?? 'N/A'); ?>
                                    </p>

                                    <?php if ($child['bus_number']): ?>
                                        <p class="mb-2">
                                            <i class="fas fa-bus text-success me-2"></i>
                                            <strong>Bus:</strong> <?php echo htmlspecialchars($child['bus_number']); ?>
                                        </p>

                                        <?php if ($child['driver_name']): ?>
                                            <p class="mb-2">
                                                <i class="fas fa-user-tie text-info me-2"></i>
                                                <strong>Driver:</strong> <?php echo htmlspecialchars($child['driver_name']); ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ($child['stop_name']): ?>
                                            <p class="mb-2">
                                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                                <strong>Stop:</strong> <?php echo htmlspecialchars($child['stop_name']); ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ($child['route_name']): ?>
                                            <p class="mb-2">
                                                <i class="fas fa-route text-warning me-2"></i>
                                                <strong>Route:</strong> <?php echo htmlspecialchars($child['route_name']); ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="mt-3">
                                            <span
                                                class="badge bg-<?php echo $child['bus_status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($child['bus_status'] ?? 'N/A'); ?>
                                            </span>
                                        </div>

                                        <div class="d-grid gap-2 mt-3">
                                            <a href="/track-bus.php?bus=<?php echo $child['bus_number']; ?>"
                                                class="btn btn-success btn-sm">
                                                <i class="fas fa-map-marked-alt me-2"></i>Track Bus
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning mb-0">
                                            <small><i class="fas fa-exclamation-triangle me-1"></i>Not assigned to a bus yet</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Today's Schedule -->
            <?php
            $has_schedule = false;
            foreach ($children as $child) {
                if ($child['start_time'] || $child['end_time']) {
                    $has_schedule = true;
                    break;
                }
            }
            ?>

            <?php if ($has_schedule): ?>
                <h4 class="mb-3"><i class="fas fa-clock me-2"></i>Today's Schedule</h4>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Bus</th>
                                        <th>Route</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($children as $child): ?>
                                        <?php if ($child['start_time'] || $child['end_time']): ?>
                                            <tr>
                                                <td><i
                                                        class="fas fa-user-graduate text-primary me-2"></i><?php echo htmlspecialchars($child['student_name']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($child['bus_number'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($child['route_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($child['start_time'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($child['end_time'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Notifications -->
            <h4 class="mb-3"><i class="fas fa-bell me-2"></i>Recent Notifications</h4>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted text-center py-3">No notifications yet</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="list-group-item <?php echo !$notif['is_read'] ? 'bg-light' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <?php
                                            $icons = [
                                                'traffic' => 'fa-traffic-light text-warning',
                                                'speed_warning' => 'fa-exclamation-triangle text-danger',
                                                'nearby' => 'fa-map-marker-alt text-success',
                                                'route_change' => 'fa-route text-info',
                                                'general' => 'fa-info-circle text-primary'
                                            ];
                                            $icon = $icons[$notif['notification_type']] ?? 'fa-bell text-secondary';
                                            ?>
                                            <i class="fas <?php echo $icon; ?> me-2"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $notif['notification_type'])); ?>
                                        </h6>
                                        <small
                                            class="text-muted"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="/notifications.php" class="btn btn-outline-primary">
                                View All Notifications <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>