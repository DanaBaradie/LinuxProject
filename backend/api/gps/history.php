<?php
/**
 * GPS API - Get GPS History
 * 
 * GET /api/gps/history.php?bus_id=1&limit=100
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireAuth();

if (!isset($_GET['bus_id'])) {
    sendJsonResponse(false, null, 'Bus ID is required', 400);
}

$busId = intval($_GET['bus_id']);
$limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 1000) : 100;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $userRole = getUserRole();
    $userId = getUserId();
    
    // Verify access to this bus
    if ($userRole === 'driver') {
        $checkQuery = "SELECT id FROM buses WHERE id = :bus_id AND driver_id = :driver_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':bus_id', $busId);
        $checkStmt->bindParam(':driver_id', $userId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            sendJsonResponse(false, null, 'Access denied', 403);
        }
    } elseif ($userRole === 'parent') {
        $checkQuery = "SELECT DISTINCT b.id
                      FROM buses b
                      INNER JOIN bus_routes br ON b.id = br.bus_id
                      INNER JOIN routes r ON br.route_id = r.id
                      INNER JOIN route_stops rs ON r.id = rs.route_id
                      INNER JOIN students s ON rs.id = s.assigned_stop_id
                      WHERE b.id = :bus_id AND s.parent_id = :parent_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':bus_id', $busId);
        $checkStmt->bindParam(':parent_id', $userId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            sendJsonResponse(false, null, 'Access denied', 403);
        }
    }
    
    // Get GPS history
    $query = "SELECT id, latitude, longitude, speed, heading, timestamp
              FROM gps_logs
              WHERE bus_id = :bus_id
              ORDER BY timestamp DESC
              LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_id', $busId);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $logs = $stmt->fetchAll();
    
    // Format response
    foreach ($logs as &$log) {
        $log['latitude'] = floatval($log['latitude']);
        $log['longitude'] = floatval($log['longitude']);
        $log['speed'] = floatval($log['speed']);
        $log['heading'] = $log['heading'] ? floatval($log['heading']) : null;
        $log['timestamp_formatted'] = formatDateTime($log['timestamp']);
    }
    
    sendJsonResponse(true, [
        'bus_id' => $busId,
        'logs' => $logs,
        'count' => count($logs)
    ], 'GPS history retrieved successfully');
    
} catch (Exception $e) {
    error_log("Get GPS history error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error retrieving GPS history', 500);
}
?>

