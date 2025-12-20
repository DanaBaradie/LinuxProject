<?php
/**
 * GPS API - Get Live Bus Locations
 * 
 * GET /api/gps/live.php
 * Optional: ?bus_id=1 (for specific bus)
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
    $busId = isset($_GET['bus_id']) ? intval($_GET['bus_id']) : null;
    
    // Build query based on role and filters
    if ($userRole === 'admin') {
        if ($busId) {
            $query = "SELECT b.id, b.bus_number, b.current_latitude, b.current_longitude, 
                             b.last_location_update, b.status,
                             u.full_name as driver_name,
                             (SELECT speed FROM gps_logs WHERE bus_id = b.id ORDER BY timestamp DESC LIMIT 1) as speed,
                             (SELECT heading FROM gps_logs WHERE bus_id = b.id ORDER BY timestamp DESC LIMIT 1) as heading
                      FROM buses b
                      LEFT JOIN users u ON b.driver_id = u.id
                      WHERE b.id = :bus_id AND b.status = 'active'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':bus_id', $busId);
        } else {
            $query = "SELECT b.id, b.bus_number, b.current_latitude, b.current_longitude, 
                             b.last_location_update, b.status,
                             u.full_name as driver_name,
                             (SELECT speed FROM gps_logs WHERE bus_id = b.id ORDER BY timestamp DESC LIMIT 1) as speed,
                             (SELECT heading FROM gps_logs WHERE bus_id = b.id ORDER BY timestamp DESC LIMIT 1) as heading
                      FROM buses b
                      LEFT JOIN users u ON b.driver_id = u.id
                      WHERE b.status = 'active' 
                        AND b.current_latitude IS NOT NULL 
                        AND b.current_longitude IS NOT NULL
                      ORDER BY b.bus_number";
            $stmt = $db->prepare($query);
        }
    } elseif ($userRole === 'driver') {
        $query = "SELECT b.id, b.bus_number, b.current_latitude, b.current_longitude, 
                         b.last_location_update, b.status,
                         (SELECT speed FROM gps_logs WHERE bus_id = b.id ORDER BY timestamp DESC LIMIT 1) as speed,
                         (SELECT heading FROM gps_logs WHERE bus_id = b.id ORDER BY timestamp DESC LIMIT 1) as heading
                  FROM buses b
                  WHERE b.driver_id = :driver_id 
                    AND b.status = 'active'
                    AND b.current_latitude IS NOT NULL 
                    AND b.current_longitude IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':driver_id', $userId);
    } else {
        // Parent: get buses for their children
        $query = "SELECT DISTINCT b.id, b.bus_number, b.current_latitude, b.current_longitude, 
                         b.last_location_update, b.status,
                         (SELECT speed FROM gps_logs WHERE bus_id = b.id ORDER BY timestamp DESC LIMIT 1) as speed,
                         (SELECT heading FROM gps_logs WHERE bus_id = b.id ORDER BY timestamp DESC LIMIT 1) as heading
                  FROM buses b
                  INNER JOIN bus_routes br ON b.id = br.bus_id
                  INNER JOIN routes r ON br.route_id = r.id
                  INNER JOIN route_stops rs ON r.id = rs.route_id
                  INNER JOIN students s ON rs.id = s.assigned_stop_id
                  WHERE s.parent_id = :parent_id 
                    AND b.status = 'active'
                    AND b.current_latitude IS NOT NULL 
                    AND b.current_longitude IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':parent_id', $userId);
    }
    
    $stmt->execute();
    $buses = $stmt->fetchAll();
    
    // Format response
    foreach ($buses as &$bus) {
        $bus['last_update'] = $bus['last_location_update'] ? 
            formatDateTime($bus['last_location_update']) : null;
        $bus['location'] = [
            'latitude' => floatval($bus['current_latitude']),
            'longitude' => floatval($bus['current_longitude'])
        ];
        if ($bus['speed'] !== null) {
            $bus['speed'] = floatval($bus['speed']);
        }
        if ($bus['heading'] !== null) {
            $bus['heading'] = floatval($bus['heading']);
        }
    }
    
    sendJsonResponse(true, ['buses' => $buses], 'Live locations retrieved successfully');
    
} catch (Exception $e) {
    error_log("Get live locations error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error retrieving live locations', 500);
}
?>

