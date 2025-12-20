<?php
/**
 * Notifications API - Get Notifications
 * 
 * GET /api/notifications/index.php
 * Optional: ?unread_only=true
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

$userRole = getUserRole();
$userId = getUserId();

// Only parents can view notifications
if ($userRole !== 'parent') {
    sendJsonResponse(false, null, 'Only parents can view notifications', 403);
}

$unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT n.id, n.bus_id, n.message, n.notification_type, n.is_read, n.created_at,
                     b.bus_number
              FROM notifications n
              LEFT JOIN buses b ON n.bus_id = b.id
              WHERE n.parent_id = :parent_id";
    
    if ($unreadOnly) {
        $query .= " AND n.is_read = FALSE";
    }
    
    $query .= " ORDER BY n.created_at DESC LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':parent_id', $userId);
    $stmt->execute();
    
    $notifications = $stmt->fetchAll();
    
    // Format dates
    foreach ($notifications as &$notification) {
        $notification['created_at_formatted'] = formatDateTime($notification['created_at']);
    }
    
    // Get unread count
    $countQuery = "SELECT COUNT(*) as unread_count 
                   FROM notifications 
                   WHERE parent_id = :parent_id AND is_read = FALSE";
    $countStmt = $db->prepare($countQuery);
    $countStmt->bindParam(':parent_id', $userId);
    $countStmt->execute();
    $unreadCount = $countStmt->fetch()['unread_count'];
    
    sendJsonResponse(true, [
        'notifications' => $notifications,
        'unread_count' => intval($unreadCount),
        'total' => count($notifications)
    ], 'Notifications retrieved successfully');
    
} catch (Exception $e) {
    error_log("Get notifications error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error retrieving notifications', 500);
}
?>

