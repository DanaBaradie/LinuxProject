<?php
/**
 * Notifications API - Mark Notification as Read
 * 
 * PUT /api/notifications/mark-read.php
 * Body: { "notification_id": 1 } or { "mark_all": true }
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireAuth();

$userRole = getUserRole();
$userId = getUserId();

// Only parents can mark notifications as read
if ($userRole !== 'parent') {
    sendJsonResponse(false, null, 'Only parents can mark notifications', 403);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (isset($input['mark_all']) && $input['mark_all'] === true) {
        // Mark all notifications as read
        $query = "UPDATE notifications SET is_read = TRUE WHERE parent_id = :parent_id AND is_read = FALSE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':parent_id', $userId);
        $stmt->execute();
        
        sendJsonResponse(true, null, 'All notifications marked as read');
    } elseif (isset($input['notification_id'])) {
        $notificationId = intval($input['notification_id']);
        
        // Verify ownership
        $checkQuery = "SELECT id FROM notifications WHERE id = :id AND parent_id = :parent_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $notificationId);
        $checkStmt->bindParam(':parent_id', $userId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            sendJsonResponse(false, null, 'Notification not found', 404);
        }
        
        // Mark as read
        $query = "UPDATE notifications SET is_read = TRUE WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $notificationId);
        $stmt->execute();
        
        sendJsonResponse(true, null, 'Notification marked as read');
    } else {
        sendJsonResponse(false, null, 'Notification ID or mark_all flag required', 400);
    }
    
} catch (Exception $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error updating notification', 500);
}
?>

