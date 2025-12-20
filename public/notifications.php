<?php
/**
 * Send Notifications Page
 * 
 * Allows admins and drivers to send notifications to parents
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$userRole = getUserRole();

// Only admin and driver can send notifications
if (!in_array($userRole, ['admin', 'driver'])) {
    header('Location: /dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parentId = intval($_POST['parent_id'] ?? 0);
    $busId = !empty($_POST['bus_id']) ? intval($_POST['bus_id']) : null;
    $message = sanitizeInput($_POST['message'] ?? '');
    $type = sanitizeInput($_POST['notification_type'] ?? '');
    
    // Validate notification type
    $validTypes = ['traffic', 'speed_warning', 'nearby', 'route_change', 'general'];
    
    if (empty($parentId) || empty($message) || empty($type)) {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($type, $validTypes)) {
        $error = 'Invalid notification type.';
    } elseif (strlen($message) > 500) {
        $error = 'Message cannot exceed 500 characters.';
    } else {
        try {
            // Verify parent exists
            $parentQuery = "SELECT id FROM users WHERE id = :id AND role = 'parent'";
            $parentStmt = $db->prepare($parentQuery);
            $parentStmt->bindParam(':id', $parentId);
            $parentStmt->execute();
            
            if ($parentStmt->rowCount() === 0) {
                $error = 'Parent not found.';
            } else {
                // Verify bus if provided
                if ($busId) {
                    $busQuery = "SELECT id FROM buses WHERE id = :id";
                    $busStmt = $db->prepare($busQuery);
                    $busStmt->bindParam(':id', $busId);
                    $busStmt->execute();
                    
                    if ($busStmt->rowCount() === 0) {
                        $error = 'Bus not found.';
                    }
                }
                
                if (empty($error)) {
                    // Insert notification
                    $query = "INSERT INTO notifications (parent_id, bus_id, message, notification_type) 
                              VALUES (:parent_id, :bus_id, :message, :type)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':parent_id', $parentId);
                    $stmt->bindParam(':bus_id', $busId);
                    $stmt->bindParam(':message', $message);
                    $stmt->bindParam(':type', $type);
                    $stmt->execute();
                    
                    $success = 'Notification sent successfully!';
                    // Clear form
                    $_POST = [];
                }
            }
        } catch (Exception $e) {
            error_log("Create notification error: " . $e->getMessage());
            $error = 'Error sending notification: ' . $e->getMessage();
        }
    }
}

// Get all parents
$query = "SELECT id, full_name, email, phone FROM users WHERE role = 'parent' ORDER BY full_name";
$parents = $db->query($query)->fetchAll();

// Get all buses
$query = "SELECT id, bus_number, license_plate FROM buses WHERE status = 'active' ORDER BY bus_number";
$buses = $db->query($query)->fetchAll();

// Get recent notifications
$query = "SELECT n.*, u.full_name as parent_name, b.bus_number 
          FROM notifications n 
          LEFT JOIN users u ON n.parent_id = u.id 
          LEFT JOIN buses b ON n.bus_id = b.id 
          ORDER BY n.created_at DESC LIMIT 20";
$recentNotifications = $db->query($query)->fetchAll();

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="/css/forms.css">

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="fas fa-bell me-2 text-primary"></i>Send Notifications
                    </h1>
                    <p class="text-muted mb-0">Send notifications to parents about bus updates and alerts</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Send Notification Form -->
                <div class="col-lg-8 mb-4">
                    <div class="card form-container">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 text-white" style="color: white !important;"><i class="fas fa-paper-plane me-2"></i>Send New Notification</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" id="notificationForm">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="parent_id" class="form-label">
                                            <i class="fas fa-user me-2 text-primary"></i>Parent <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-control-modern" id="parent_id" name="parent_id" required>
                                            <option value="">Select a parent...</option>
                                            <?php foreach ($parents as $parent): ?>
                                                <option value="<?php echo $parent['id']; ?>" <?php echo (isset($_POST['parent_id']) && $_POST['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($parent['full_name']); ?> (<?php echo htmlspecialchars($parent['email']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="bus_id" class="form-label">
                                            <i class="fas fa-bus me-2 text-primary"></i>Bus (Optional)
                                        </label>
                                        <select class="form-select form-control-modern" id="bus_id" name="bus_id">
                                            <option value="">Select a bus (optional)...</option>
                                            <?php foreach ($buses as $bus): ?>
                                                <option value="<?php echo $bus['id']; ?>" <?php echo (isset($_POST['bus_id']) && $_POST['bus_id'] == $bus['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($bus['bus_number']); ?> - <?php echo htmlspecialchars($bus['license_plate']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="notification_type" class="form-label">
                                            <i class="fas fa-tag me-2 text-primary"></i>Notification Type <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-control-modern" id="notification_type" name="notification_type" required>
                                            <option value="">Select type...</option>
                                            <option value="general" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'general') ? 'selected' : ''; ?>>General</option>
                                            <option value="traffic" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'traffic') ? 'selected' : ''; ?>>Traffic Delay</option>
                                            <option value="nearby" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'nearby') ? 'selected' : ''; ?>>Bus Nearby</option>
                                            <option value="route_change" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'route_change') ? 'selected' : ''; ?>>Route Change</option>
                                            <option value="speed_warning" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'speed_warning') ? 'selected' : ''; ?>>Speed Warning</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <label for="message" class="form-label">
                                            <i class="fas fa-comment me-2 text-primary"></i>Message <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control form-control-modern" id="message" name="message" rows="5" required placeholder="Enter notification message..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                        <div class="form-text">Maximum 500 characters</div>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary-modern btn-lg w-100">
                                            <i class="fas fa-paper-plane me-2"></i>Send Notification
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Recent Notifications -->
                <div class="col-lg-4">
                    <div class="card form-container">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0 text-white" style="color: white !important;"><i class="fas fa-history me-2"></i>Recent Notifications</h5>
                        </div>
                        <div class="card-body p-3">
                            <?php if (!empty($recentNotifications)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentNotifications as $notif): ?>
                                        <div class="list-group-item px-0 py-3 border-bottom">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <span class="badge bg-<?php 
                                                        echo $notif['notification_type'] == 'traffic' ? 'warning' : 
                                                            ($notif['notification_type'] == 'nearby' ? 'success' : 
                                                            ($notif['notification_type'] == 'route_change' ? 'info' : 
                                                            ($notif['notification_type'] == 'speed_warning' ? 'danger' : 'primary'))); 
                                                    ?> mb-2">
                                                        <?php echo ucfirst(str_replace('_', ' ', $notif['notification_type'])); ?>
                                                    </span>
                                                    <div class="small text-muted">
                                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($notif['parent_name']); ?>
                                                        <?php if ($notif['bus_number']): ?>
                                                            <br><i class="fas fa-bus me-1"></i><?php echo htmlspecialchars($notif['bus_number']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo date('M j, H:i', strtotime($notif['created_at'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-0 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">No notifications sent yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.getElementById('notificationForm').addEventListener('submit', function(e) {
    const message = document.getElementById('message').value;
    if (message.length > 500) {
        e.preventDefault();
        alert('Message cannot exceed 500 characters.');
        return false;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

