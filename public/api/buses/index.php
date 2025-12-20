<?php
/**
 * Buses API - List All Buses
 * 
 * GET /api/buses/index.php
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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $userRole = getUserRole();
    $userId = getUserId();
    
    // Build query based on role
    if ($userRole === 'admin') {
        $query = "SELECT b.id, b.bus_number, b.license_plate, b.capacity, 
                         b.current_latitude, b.current_longitude, 
                         b.last_location_update, b.status, b.created_at,
                         u.id as driver_id, u.full_name as driver_name, u.email as driver_email
                  FROM buses b
                  LEFT JOIN users u ON b.driver_id = u.id
                  ORDER BY b.bus_number";
        $stmt = $db->prepare($query);
    } elseif ($userRole === 'driver') {
        $query = "SELECT b.id, b.bus_number, b.license_plate, b.capacity, 
                         b.current_latitude, b.current_longitude, 
                         b.last_location_update, b.status, b.created_at
                  FROM buses b
                  WHERE b.driver_id = :driver_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':driver_id', $userId);
    } else {
        // Parent: get buses for their children's routes
        $query = "SELECT DISTINCT b.id, b.bus_number, b.license_plate, b.capacity,
                         b.current_latitude, b.current_longitude, 
                         b.last_location_update, b.status
                  FROM buses b
                  INNER JOIN bus_routes br ON b.id = br.bus_id
                  INNER JOIN routes r ON br.route_id = r.id
                  INNER JOIN route_stops rs ON r.id = rs.route_id
                  INNER JOIN students s ON rs.id = s.assigned_stop_id
                  WHERE s.parent_id = :parent_id AND b.status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':parent_id', $userId);
    }
    
    $stmt->execute();
    $buses = $stmt->fetchAll();
    
    // Format dates
    foreach ($buses as &$bus) {
        if (isset($bus['last_location_update'])) {
            $bus['last_location_update_formatted'] = $bus['last_location_update'] ? 
                formatDateTime($bus['last_location_update']) : null;
        }
        if (isset($bus['created_at'])) {
            $bus['created_at_formatted'] = formatDateTime($bus['created_at']);
        }
    }
    
    sendJsonResponse(true, ['buses' => $buses], 'Buses retrieved successfully');
    
} catch (Exception $e) {
    error_log("Get buses error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error retrieving buses', 500);
}
?>

