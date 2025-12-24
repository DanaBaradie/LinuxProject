<?php
header('Content-Type: application/json');
require_once '../../../config/config.php';
require_once '../../../config/database.php';

requireLogin();
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$userId = intval($_GET['id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    // Get driver information
    $query = "SELECT * FROM users WHERE id = :id AND role = 'driver'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Driver not found']);
        exit;
    }

    // Get assigned bus
    $query = "SELECT * FROM buses WHERE driver_id = :driver_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':driver_id', $userId);
    $stmt->execute();
    $bus = $stmt->fetch();

    // Get assigned routes (if bus is assigned)
    $routes = [];
    if ($bus) {
        $query = "SELECT r.* FROM routes r
                  INNER JOIN bus_routes br ON r.id = br.route_id
                  WHERE br.bus_id = :bus_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':bus_id', $bus['id']);
        $stmt->execute();
        $routes = $stmt->fetchAll();
    }

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone']
        ],
        'bus' => $bus ? [
            'id' => $bus['id'],
            'bus_number' => $bus['bus_number'],
            'license_plate' => $bus['license_plate'],
            'capacity' => $bus['capacity'],
            'status' => $bus['status']
        ] : null,
        'routes' => $routes
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>