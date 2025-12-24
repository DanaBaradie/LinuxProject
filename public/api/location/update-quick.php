<?php
header('Content-Type: application/json');
require_once '../../../config/config.php';
require_once '../../../config/database.php';

requireLogin();
requireRole('driver');

$database = new Database();
$db = $database->getConnection();

$driver_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['latitude']) || !isset($input['longitude'])) {
    echo json_encode(['success' => false, 'message' => 'Latitude and longitude are required']);
    exit;
}

$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);

// Validate coordinates
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}

try {
    // Get driver's bus
    $query = "SELECT id FROM buses WHERE driver_id = :driver_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':driver_id', $driver_id);
    $stmt->execute();
    $bus = $stmt->fetch();

    if (!$bus) {
        echo json_encode(['success' => false, 'message' => 'No bus assigned to this driver']);
        exit;
    }

    // Update bus location
    $query = "UPDATE buses 
              SET current_latitude = :latitude, 
                  current_longitude = :longitude,
                  last_location_update = NOW()
              WHERE id = :bus_id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':latitude' => $latitude,
        ':longitude' => $longitude,
        ':bus_id' => $bus['id']
    ]);

    // Log GPS update
    $query = "INSERT INTO gps_logs (bus_id, latitude, longitude, speed, timestamp) 
              VALUES (:bus_id, :latitude, :longitude, 0, NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':bus_id' => $bus['id'],
        ':latitude' => $latitude,
        ':longitude' => $longitude
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Location updated successfully',
        'latitude' => $latitude,
        'longitude' => $longitude,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>