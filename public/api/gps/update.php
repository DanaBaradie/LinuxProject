<?php
/**
 * GPS API - Update Bus Location
 * 
 * POST /api/gps/update.php
 * Body: { "bus_id": 1, "latitude": 33.8886, "longitude": 35.4955, "speed": 45.5, "heading": 90 }
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['bus_id']) || !isset($input['latitude']) || !isset($input['longitude'])) {
    sendJsonResponse(false, null, 'Bus ID, latitude, and longitude are required', 400);
}

$busId = intval($input['bus_id']);
$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);
$speed = isset($input['speed']) ? floatval($input['speed']) : 0.0;
$heading = isset($input['heading']) ? floatval($input['heading']) : null;

// Validate coordinates
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    sendJsonResponse(false, null, 'Invalid coordinates', 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $userRole = getUserRole();
    $userId = getUserId();
    
    // Verify bus access
    if ($userRole === 'driver') {
        // Driver can only update their own bus
        $checkQuery = "SELECT id FROM buses WHERE id = :bus_id AND driver_id = :driver_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':bus_id', $busId);
        $checkStmt->bindParam(':driver_id', $userId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            sendJsonResponse(false, null, 'You can only update your assigned bus', 403);
        }
    } elseif ($userRole !== 'admin') {
        sendJsonResponse(false, null, 'Insufficient permissions', 403);
    }
    
    // Check if bus exists
    $busQuery = "SELECT id FROM buses WHERE id = :bus_id";
    $busStmt = $db->prepare($busQuery);
    $busStmt->bindParam(':bus_id', $busId);
    $busStmt->execute();
    
    if ($busStmt->rowCount() === 0) {
        sendJsonResponse(false, null, 'Bus not found', 404);
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Update bus current location
        $updateQuery = "UPDATE buses 
                       SET current_latitude = :latitude, 
                           current_longitude = :longitude, 
                           last_location_update = NOW() 
                       WHERE id = :bus_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':latitude', $latitude);
        $updateStmt->bindParam(':longitude', $longitude);
        $updateStmt->bindParam(':bus_id', $busId);
        $updateStmt->execute();
        
        // Log GPS data
        $logQuery = "INSERT INTO gps_logs (bus_id, latitude, longitude, speed, heading) 
                     VALUES (:bus_id, :latitude, :longitude, :speed, :heading)";
        $logStmt = $db->prepare($logQuery);
        $logStmt->bindParam(':bus_id', $busId);
        $logStmt->bindParam(':latitude', $latitude);
        $logStmt->bindParam(':longitude', $longitude);
        $logStmt->bindParam(':speed', $speed);
        $logStmt->bindParam(':heading', $heading);
        $logStmt->execute();
        
        $db->commit();
        
        // Get updated bus data
        $getQuery = "SELECT b.*, u.full_name as driver_name 
                     FROM buses b 
                     LEFT JOIN users u ON b.driver_id = u.id 
                     WHERE b.id = :bus_id";
        $getStmt = $db->prepare($getQuery);
        $getStmt->bindParam(':bus_id', $busId);
        $getStmt->execute();
        $bus = $getStmt->fetch();
        
        sendJsonResponse(true, [
            'bus' => $bus,
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'speed' => $speed,
                'heading' => $heading,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ], 'Location updated successfully');
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("GPS update error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error updating location', 500);
}
?>

