<?php
/**
 * Notifications API - Mark Notification as Read
 * 
 * POST /api/notifications/mark-read.php
 * Body: { "notification_id": 1 }
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/middleware.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireAuth();

$userId = getUserId();
$userRole = getUserRole();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['notification_id'])) {
    sendJsonResponse(false, null, 'Notification ID is required', 400);
}

$notificationId = intval($input['notification_id']);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify notification exists and belongs to user
    if ($userRole === 'parent') {
        $verifyQuery = "SELECT id FROM notifications WHERE id = :id AND parent_id = :user_id";
    } elseif ($userRole === 'driver') {
        // For drivers, check if notification is for their bus
        $verifyQuery = "SELECT n.id FROM notifications n
                       INNER JOIN buses b ON n.bus_id = b.id
                       WHERE n.id = :id AND b.driver_id = :user_id";
    } else {
        // Admin can mark any notification as read
        $verifyQuery = "SELECT id FROM notifications WHERE id = :id";
    }
    
    $verifyStmt = $db->prepare($verifyQuery);
    $verifyStmt->bindParam(':id', $notificationId);
    if ($userRole !== 'admin') {
        $verifyStmt->bindParam(':user_id', $userId);
    }
    $verifyStmt->execute();
    
    if ($verifyStmt->rowCount() === 0) {
        sendJsonResponse(false, null, 'Notification not found or access denied', 404);
    }
    
    // Mark as read
    $updateQuery = "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':id', $notificationId);
    $updateStmt->execute();
    
    sendJsonResponse(true, null, 'Notification marked as read', 200);
    
} catch (Exception $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error marking notification as read', 500);
}
?>

