<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filter
$filter_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';

// Build query based on role
if ($user_role === 'parent') {
    $query = "SELECT * FROM notifications WHERE parent_id = :user_id";
    $count_query = "SELECT COUNT(*) as total FROM notifications WHERE parent_id = :user_id";
} else {
    // For admin/driver, show all notifications or create a different view
    $query = "SELECT * FROM notifications WHERE 1=1";
    $count_query = "SELECT COUNT(*) as total FROM notifications WHERE 1=1";
}

// Add type filter
if ($filter_type) {
    $query .= " AND notification_type = :type";
    $count_query .= " AND notification_type = :type";
}

$query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

// Get total count
$stmt = $db->prepare($count_query);
if ($user_role === 'parent') {
    $stmt->bindParam(':user_id', $user_id);
}
if ($filter_type) {
    $stmt->bindParam(':type', $filter_type);
}
$stmt->execute();
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get notifications
$stmt = $db->prepare($query);
if ($user_role === 'parent') {
    $stmt->bindParam(':user_id', $user_id);
}
if ($filter_type) {
    $stmt->bindParam(':type', $filter_type);
}
$stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();

// Mark as read if parent
if ($user_role === 'parent' && !empty($notifications)) {
    $ids = array_column($notifications, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "UPDATE notifications SET is_read = TRUE WHERE id IN ($placeholders)";
    $stmt = $db->prepare($query);
    $stmt->execute($ids);
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-bell me-2"></i>Notification History</h1>
                <a href="<?php echo $user_role === 'parent' ? '/parent-dashboard.php' : ($user_role === 'driver' ? '/driver-dashboard.php' : '/dashboard.php'); ?>"
                    class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Filter -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Filter by Type</label>
                            <select name="type" class="form-select" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <option value="traffic" <?php echo $filter_type === 'traffic' ? 'selected' : ''; ?>>
                                    Traffic</option>
                                <option value="speed_warning" <?php echo $filter_type === 'speed_warning' ? 'selected' : ''; ?>>Speed Warning</option>
                                <option value="nearby" <?php echo $filter_type === 'nearby' ? 'selected' : ''; ?>>Nearby
                                </option>
                                <option value="route_change" <?php echo $filter_type === 'route_change' ? 'selected' : ''; ?>>Route Change</option>
                                <option value="general" <?php echo $filter_type === 'general' ? 'selected' : ''; ?>>
                                    General</option>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <?php if ($filter_type): ?>
                                <a href="/notification-history.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Clear Filter
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                            <h4>No Notifications</h4>
                            <p class="text-muted">You don't have any notifications yet</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-2">
                                                <?php
                                                $icons = [
                                                    'traffic' => ['icon' => 'fa-traffic-light', 'color' => 'warning'],
                                                    'speed_warning' => ['icon' => 'fa-exclamation-triangle', 'color' => 'danger'],
                                                    'nearby' => ['icon' => 'fa-map-marker-alt', 'color' => 'success'],
                                                    'route_change' => ['icon' => 'fa-route', 'color' => 'info'],
                                                    'general' => ['icon' => 'fa-info-circle', 'color' => 'primary']
                                                ];
                                                $iconData = $icons[$notif['notification_type']] ?? ['icon' => 'fa-bell', 'color' => 'secondary'];
                                                ?>
                                                <i
                                                    class="fas <?php echo $iconData['icon']; ?> text-<?php echo $iconData['color']; ?> me-2"></i>
                                                <span class="badge bg-<?php echo $iconData['color']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $notif['notification_type'])); ?>
                                                </span>
                                            </h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('F d, Y \a\t H:i', strtotime($notif['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="ms-3">
                                            <?php if ($notif['is_read']): ?>
                                                <span class="badge bg-secondary">Read</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">New</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Notification pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?page=<?php echo $page - 1; ?><?php echo $filter_type ? '&type=' . $filter_type : ''; ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $i; ?><?php echo $filter_type ? '&type=' . $filter_type : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?page=<?php echo $page + 1; ?><?php echo $filter_type ? '&type=' . $filter_type : ''; ?>">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>

                            <p class="text-center text-muted">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total); ?> of
                                <?php echo $total; ?> notifications
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>