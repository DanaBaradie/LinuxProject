<?php
/**
 * Routes API - List All Routes
 * 
 * GET /api/routes
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireAuth();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';
    
    $query = "SELECT r.*, 
                     COUNT(DISTINCT rs.id) as stop_count,
                     COUNT(DISTINCT br.bus_id) as bus_count
              FROM routes r
              LEFT JOIN route_stops rs ON r.id = rs.route_id
              LEFT JOIN bus_routes br ON r.id = br.route_id";
    
    if ($activeOnly) {
        $query .= " WHERE r.active = TRUE";
    }
    
    $query .= " GROUP BY r.id ORDER BY r.route_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $routes = $stmt->fetchAll();
    
    // Format times
    foreach ($routes as &$route) {
        $route['start_time_formatted'] = formatTime($route['start_time']);
        $route['end_time_formatted'] = formatTime($route['end_time']);
        $route['created_at_formatted'] = formatDateTime($route['created_at']);
    }
    
    sendJsonResponse(true, ['routes' => $routes], 'Routes retrieved successfully');
    
} catch (Exception $e) {
    error_log("Get routes error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error retrieving routes', 500);
}
?>

