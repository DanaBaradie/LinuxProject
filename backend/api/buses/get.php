<?php
/**
 * Buses API - Get Single Bus
 * 
 * GET /api/buses/get.php?id=1
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

if (!isset($_GET['id'])) {
    sendJsonResponse(false, null, 'Bus ID is required', 400);
}

$busId = intval($_GET['id']);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $userRole = getUserRole();
    $userId = getUserId();
    
    // Build query based on role
    if ($userRole === 'admin') {
        $query = "SELECT b.*, u.id as driver_id, u.full_name as driver_name, u.email as driver_email
                  FROM buses b
                  LEFT JOIN users u ON b.driver_id = u.id
                  WHERE b.id = :id";
    } elseif ($userRole === 'driver') {
        $query = "SELECT b.* FROM buses b WHERE b.id = :id AND b.driver_id = :driver_id";
    } else {
        // Parent: verify they have access to this bus
        $query = "SELECT DISTINCT b.*
                  FROM buses b
                  INNER JOIN bus_routes br ON b.id = br.bus_id
                  INNER JOIN routes r ON br.route_id = r.id
                  INNER JOIN route_stops rs ON r.id = rs.route_id
                  INNER JOIN students s ON rs.id = s.assigned_stop_id
                  WHERE b.id = :id AND s.parent_id = :parent_id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $busId);
    
    if ($userRole === 'driver') {
        $stmt->bindParam(':driver_id', $userId);
    } elseif ($userRole === 'parent') {
        $stmt->bindParam(':parent_id', $userId);
    }
    
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, null, 'Bus not found', 404);
    }
    
    $bus = $stmt->fetch();
    
    // Format dates
    if (isset($bus['last_location_update'])) {
        $bus['last_location_update_formatted'] = $bus['last_location_update'] ? 
            formatDateTime($bus['last_location_update']) : null;
    }
    
    sendJsonResponse(true, ['bus' => $bus], 'Bus retrieved successfully');
    
} catch (Exception $e) {
    error_log("Get bus error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error retrieving bus', 500);
}
?>

