<?php
/**
 * Authentication API - Check Session Endpoint
 * 
 * GET /api/auth/check
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

if (!isLoggedIn()) {
    sendJsonResponse(false, null, 'Not authenticated', 401);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id, email, full_name, role FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        session_destroy();
        sendJsonResponse(false, null, 'User not found', 404);
    }

    $user = $stmt->fetch();
    
    sendJsonResponse(true, [
        'user' => $user,
        'session_id' => session_id()
    ], 'Session valid');

} catch (Exception $e) {
    error_log("Session check error: " . $e->getMessage());
    sendJsonResponse(false, null, 'An error occurred', 500);
}
?>

