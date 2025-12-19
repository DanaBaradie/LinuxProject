<?php
/**
 * Buses API - Delete Bus
 * 
 * DELETE /api/buses/delete.php?id=1
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireRoleMiddleware('admin');

if (!isset($_GET['id'])) {
    sendJsonResponse(false, null, 'Bus ID is required', 400);
}

$busId = intval($_GET['id']);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if bus exists
    $checkQuery = "SELECT id, bus_number FROM buses WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $busId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        sendJsonResponse(false, null, 'Bus not found', 404);
    }
    
    // Delete bus (cascade will handle related records)
    $query = "DELETE FROM buses WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $busId);
    $stmt->execute();
    
    sendJsonResponse(true, null, 'Bus deleted successfully');
    
} catch (Exception $e) {
    error_log("Delete bus error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error deleting bus', 500);
}
?>

