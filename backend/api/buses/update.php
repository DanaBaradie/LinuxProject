<?php
/**
 * Buses API - Update Bus
 * 
 * PUT /api/buses/update.php
 * Body: { "id": 1, "bus_number": "BUS-001", "capacity": 55, "driver_id": 2, "status": "active" }
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireRoleMiddleware('admin');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    sendJsonResponse(false, null, 'Bus ID is required', 400);
}

$busId = intval($input['id']);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if bus exists
    $checkQuery = "SELECT id FROM buses WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $busId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        sendJsonResponse(false, null, 'Bus not found', 404);
    }
    
    // Build update query dynamically
    $updates = [];
    $params = [':id' => $busId];
    
    if (isset($input['bus_number'])) {
        $updates[] = "bus_number = :bus_number";
        $params[':bus_number'] = sanitizeInput($input['bus_number']);
    }
    
    if (isset($input['license_plate'])) {
        $updates[] = "license_plate = :license_plate";
        $params[':license_plate'] = sanitizeInput($input['license_plate']);
    }
    
    if (isset($input['capacity'])) {
        $updates[] = "capacity = :capacity";
        $params[':capacity'] = intval($input['capacity']);
    }
    
    if (isset($input['driver_id'])) {
        $updates[] = "driver_id = :driver_id";
        $params[':driver_id'] = $input['driver_id'] ? intval($input['driver_id']) : null;
    }
    
    if (isset($input['status'])) {
        $status = sanitizeInput($input['status']);
        if (in_array($status, ['active', 'inactive', 'maintenance'])) {
            $updates[] = "status = :status";
            $params[':status'] = $status;
        }
    }
    
    if (empty($updates)) {
        sendJsonResponse(false, null, 'No fields to update', 400);
    }
    
    // Check for duplicate bus number if updating
    if (isset($input['bus_number'])) {
        $duplicateQuery = "SELECT id FROM buses WHERE bus_number = :bus_number AND id != :id";
        $duplicateStmt = $db->prepare($duplicateQuery);
        $duplicateStmt->bindParam(':bus_number', $params[':bus_number']);
        $duplicateStmt->bindParam(':id', $busId);
        $duplicateStmt->execute();
        
        if ($duplicateStmt->rowCount() > 0) {
            sendJsonResponse(false, null, 'Bus number already exists', 409);
        }
    }
    
    // Update bus
    $query = "UPDATE buses SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    // Get updated bus
    $getQuery = "SELECT b.*, u.full_name as driver_name 
                 FROM buses b 
                 LEFT JOIN users u ON b.driver_id = u.id 
                 WHERE b.id = :id";
    $getStmt = $db->prepare($getQuery);
    $getStmt->bindParam(':id', $busId);
    $getStmt->execute();
    $bus = $getStmt->fetch();
    
    sendJsonResponse(true, ['bus' => $bus], 'Bus updated successfully');
    
} catch (PDOException $e) {
    error_log("Update bus error: " . $e->getMessage());
    if ($e->getCode() == 23000) {
        sendJsonResponse(false, null, 'Bus number already exists', 409);
    }
    sendJsonResponse(false, null, 'Error updating bus', 500);
} catch (Exception $e) {
    error_log("Update bus error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error updating bus', 500);
}
?>

