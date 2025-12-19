<?php
/**
 * Application Configuration
 * 
 * Central configuration file for the School Bus Tracking System
 * 
 * @author Dana Baradie
 * @course IT404
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Site settings
define('SITE_NAME', 'School Bus Tracking System');
define('SITE_URL', 'http://165.22.21.116'); // Your server IP

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

// Google Maps API Key (get from https://console.cloud.google.com/)
define('GOOGLE_MAPS_API_KEY', 'YOUR_API_KEY_HERE');

// Timezone
date_default_timezone_set('Asia/Beirut');

// CORS headers for API
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Helper function to check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user role
 * 
 * @return string|null
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current user ID
 * 
 * @return int|null
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit();
    }
}

/**
 * Require specific role
 * 
 * @param string|array $roles Required role(s)
 */
function requireRole($roles) {
    requireLogin();
    $userRole = getUserRole();
    
    if (is_array($roles)) {
        if (!in_array($userRole, $roles)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Insufficient permissions'
            ]);
            exit();
        }
    } else {
        if ($userRole !== $roles) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Insufficient permissions'
            ]);
            exit();
        }
    }
}

/**
 * Sanitize input data
 * 
 * @param mixed $data Input data
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Format time
 * 
 * @param string $time Time string
 * @return string Formatted time
 */
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

/**
 * Format datetime
 * 
 * @param string $datetime Datetime string
 * @return string Formatted datetime
 */
function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Send JSON response
 * 
 * @param bool $success Success status
 * @param mixed $data Response data
 * @param string $message Response message
 * @param int $statusCode HTTP status code
 */
function sendJsonResponse($success, $data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    $response = ['success' => $success];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
}

// Check session timeout
if (isLoggedIn() && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_destroy();
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            sendJsonResponse(false, null, 'Session expired', 401);
        } else {
            header('Location: /login.php?timeout=1');
            exit();
        }
    }
}
$_SESSION['last_activity'] = time();
?>

