<?php
/**
 * Authentication Middleware
 * 
 * Handles authentication and authorization for API endpoints
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Middleware to require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        sendJsonResponse(false, null, 'Authentication required', 401);
    }
}

/**
 * Middleware to require specific role
 * 
 * @param string|array $roles Required role(s)
 */
function requireRoleMiddleware($roles) {
    requireAuth();
    
    $userRole = getUserRole();
    
    if (is_array($roles)) {
        if (!in_array($userRole, $roles)) {
            sendJsonResponse(false, null, 'Insufficient permissions', 403);
        }
    } else {
        if ($userRole !== $roles) {
            sendJsonResponse(false, null, 'Insufficient permissions', 403);
        }
    }
}

/**
 * Get authenticated user data
 * 
 * @return array User data
 */
function getAuthUser() {
    requireAuth();
    
    return [
        'id' => getUserId(),
        'email' => $_SESSION['user_email'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'role' => getUserRole()
    ];
}
?>

