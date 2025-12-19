<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

requireLogin();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT b.id, b.bus_number, b.current_latitude, b.current_longitude, 
                     b.status, b.last_location_update, u.full_name as driver_name
              FROM buses b
              LEFT JOIN users u ON b.driver_id = u.id
              WHERE b.status = 'active'";
    
    $buses = $db->query($query)->fetchAll();
    
    // Format dates
    foreach ($buses as &$bus) {
        $bus['last_update'] = $bus['last_location_update'] ? 
            date('M d, Y h:i A', strtotime($bus['last_location_update'])) : null;
    }
    
    echo json_encode([
        'success' => true,
        'buses' => $buses
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading buses'
    ]);
}
?>
