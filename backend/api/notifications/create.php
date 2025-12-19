<?php
/**
 * Notifications API - Create Notification
 * 
 * POST /api/notifications/create.php
 * Body: { "parent_id": 1, "bus_id": 1, "message": "Bus is nearby", "type": "nearby" }
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireRoleMiddleware(['admin', 'driver']);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['parent_id']) || !isset($input['message']) || !isset($input['type'])) {
    sendJsonResponse(false, null, 'Parent ID, message, and type are required', 400);
}

$parentId = intval($input['parent_id']);
$busId = isset($input['bus_id']) ? intval($input['bus_id']) : null;
$message = sanitizeInput($input['message']);
$type = sanitizeInput($input['type']);

// Validate notification type
$validTypes = ['traffic', 'speed_warning', 'nearby', 'route_change', 'general'];
if (!in_array($type, $validTypes)) {
    sendJsonResponse(false, null, 'Invalid notification type', 400);
}

if (empty($message)) {
    sendJsonResponse(false, null, 'Message cannot be empty', 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify parent exists
    $parentQuery = "SELECT id, role FROM users WHERE id = :id AND role = 'parent'";
    $parentStmt = $db->prepare($parentQuery);
    $parentStmt->bindParam(':id', $parentId);
    $parentStmt->execute();
    
    if ($parentStmt->rowCount() === 0) {
        sendJsonResponse(false, null, 'Parent not found', 404);
    }
    
    // Verify bus if provided
    if ($busId) {
        $busQuery = "SELECT id FROM buses WHERE id = :id";
        $busStmt = $db->prepare($busQuery);
        $busStmt->bindParam(':id', $busId);
        $busStmt->execute();
        
        if ($busStmt->rowCount() === 0) {
            sendJsonResponse(false, null, 'Bus not found', 404);
        }
    }
    
    // Insert notification
    $query = "INSERT INTO notifications (parent_id, bus_id, message, notification_type) 
              VALUES (:parent_id, :bus_id, :message, :type)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':parent_id', $parentId);
    $stmt->bindParam(':bus_id', $busId);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':type', $type);
    $stmt->execute();
    
    $notificationId = $db->lastInsertId();
    
    // Get created notification
    $getQuery = "SELECT n.*, b.bus_number 
                 FROM notifications n 
                 LEFT JOIN buses b ON n.bus_id = b.id 
                 WHERE n.id = :id";
    $getStmt = $db->prepare($getQuery);
    $getStmt->bindParam(':id', $notificationId);
    $getStmt->execute();
    $notification = $getStmt->fetch();
    
    sendJsonResponse(true, ['notification' => $notification], 'Notification created successfully', 201);
    
} catch (Exception $e) {
    error_log("Create notification error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error creating notification', 500);
}
?>

