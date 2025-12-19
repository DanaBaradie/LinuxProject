<?php
/**
 * Buses API - Create Bus
 * 
 * POST /api/buses/create.php
 * Body: { "bus_number": "BUS-001", "license_plate": "ABC-123", "capacity": 50, "driver_id": 2 }
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireRoleMiddleware('admin');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['bus_number']) || !isset($input['capacity'])) {
    sendJsonResponse(false, null, 'Bus number and capacity are required', 400);
}

$busNumber = sanitizeInput($input['bus_number']);
$licensePlate = sanitizeInput($input['license_plate'] ?? '');
$capacity = intval($input['capacity']);
$driverId = isset($input['driver_id']) ? intval($input['driver_id']) : null;
$status = sanitizeInput($input['status'] ?? 'active');

if (empty($busNumber) || $capacity <= 0) {
    sendJsonResponse(false, null, 'Invalid bus number or capacity', 400);
}

if (!in_array($status, ['active', 'inactive', 'maintenance'])) {
    $status = 'active';
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if bus number already exists
    $checkQuery = "SELECT id FROM buses WHERE bus_number = :bus_number";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':bus_number', $busNumber);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        sendJsonResponse(false, null, 'Bus number already exists', 409);
    }
    
    // Validate driver if provided
    if ($driverId) {
        $driverQuery = "SELECT id, role FROM users WHERE id = :id AND role = 'driver'";
        $driverStmt = $db->prepare($driverQuery);
        $driverStmt->bindParam(':id', $driverId);
        $driverStmt->execute();
        
        if ($driverStmt->rowCount() === 0) {
            sendJsonResponse(false, null, 'Invalid driver ID', 400);
        }
    }
    
    // Insert bus
    $query = "INSERT INTO buses (bus_number, license_plate, capacity, driver_id, status) 
              VALUES (:bus_number, :license_plate, :capacity, :driver_id, :status)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_number', $busNumber);
    $stmt->bindParam(':license_plate', $licensePlate);
    $stmt->bindParam(':capacity', $capacity);
    $stmt->bindParam(':driver_id', $driverId);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    
    $busId = $db->lastInsertId();
    
    // Get created bus
    $getQuery = "SELECT b.*, u.full_name as driver_name 
                 FROM buses b 
                 LEFT JOIN users u ON b.driver_id = u.id 
                 WHERE b.id = :id";
    $getStmt = $db->prepare($getQuery);
    $getStmt->bindParam(':id', $busId);
    $getStmt->execute();
    $bus = $getStmt->fetch();
    
    sendJsonResponse(true, ['bus' => $bus], 'Bus created successfully', 201);
    
} catch (PDOException $e) {
    error_log("Create bus error: " . $e->getMessage());
    if ($e->getCode() == 23000) {
        sendJsonResponse(false, null, 'Bus number already exists', 409);
    }
    sendJsonResponse(false, null, 'Error creating bus', 500);
} catch (Exception $e) {
    error_log("Create bus error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error creating bus', 500);
}
?>

