<?php
/**
 * Notifications API - Get Driver Notifications
 * 
 * GET /api/notifications/driver.php
 * Gets notifications for the logged-in driver based on their assigned bus
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/middleware.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireAuth();
requireRoleMiddleware(['driver']);

$userId = getUserId();
$unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get driver's bus
    $busQuery = "SELECT id FROM buses WHERE driver_id = :driver_id";
    $busStmt = $db->prepare($busQuery);
    $busStmt->bindParam(':driver_id', $userId);
    $busStmt->execute();
    $bus = $busStmt->fetch();
    
    if (!$bus) {
        sendJsonResponse(true, [
            'notifications' => [],
            'unread_count' => 0,
            'total' => 0
        ], 'No bus assigned');
    }
    
    $busId = $bus['id'];
    
    // Get notifications for this bus (sent by parents or admins)
    $query = "SELECT n.id, n.bus_id, n.message, n.notification_type, n.is_read, n.created_at,
                     b.bus_number, u.full_name as sender_name, u.role as sender_role
              FROM notifications n
              LEFT JOIN buses b ON n.bus_id = b.id
              LEFT JOIN users u ON n.parent_id = u.id
              WHERE n.bus_id = :bus_id";
    
    if ($unreadOnly) {
        $query .= " AND n.is_read = FALSE";
    }
    
    $query .= " ORDER BY n.created_at DESC LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_id', $busId);
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    
    // Format dates
    foreach ($notifications as &$notification) {
        $notification['created_at_formatted'] = formatDateTime($notification['created_at']);
    }
    
    // Get unread count
    $countQuery = "SELECT COUNT(*) as unread_count 
                   FROM notifications 
                   WHERE bus_id = :bus_id AND is_read = FALSE";
    $countStmt = $db->prepare($countQuery);
    $countStmt->bindParam(':bus_id', $busId);
    $countStmt->execute();
    $unreadCount = $countStmt->fetch()['unread_count'];
    
    sendJsonResponse(true, [
        'notifications' => $notifications,
        'unread_count' => intval($unreadCount),
        'total' => count($notifications)
    ], 'Notifications retrieved successfully');
    
} catch (Exception $e) {
    error_log("Get driver notifications error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error retrieving notifications', 500);
}
?>

