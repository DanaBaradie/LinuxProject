<?php
/**
 * Insecure GPS API - Update Bus Location (For Shortcuts/Testing)
 * 
 * POST /api/gps/update-insecure.php
 * Body: { "secret": "BusDriver123", "bus_id": 1, "latitude": 33.8886, "longitude": 35.4955 }
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/middleware.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate Secret Key
if (!isset($input['secret']) || $input['secret'] !== 'BusDriver123') {
    sendJsonResponse(false, null, 'Invalid secret key', 403);
}

// Validate required fields
if (!isset($input['bus_id']) || !isset($input['latitude']) || !isset($input['longitude'])) {
    sendJsonResponse(false, null, 'Bus ID, latitude, and longitude are required', 400);
}

$busId = intval($input['bus_id']);
$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);
$speed = isset($input['speed']) ? floatval($input['speed']) : 0.0;
$heading = isset($input['heading']) ? floatval($input['heading']) : null;

try {
    $database = new Database();
    $db = $database->getConnection();

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

        sendJsonResponse(true, [
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ], 'Location updated');

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("GPS update error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error updating location', 500);
}
