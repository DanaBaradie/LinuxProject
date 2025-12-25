<?php
/**
 * Application Configuration
 * 
 * Main configuration file with environment-based settings
 * 
 * @author Dana Baradie
 * @course IT404
 */

// Load environment configuration
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/validator.php';
require_once __DIR__ . '/security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting based on environment
if (Env::isProduction()) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', Env::isDebug() ? 1 : 0);
    ini_set('log_errors', 1);
}

// Set error handler
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    Logger::error("PHP Error: $message", ['file' => $file, 'line' => $line]);
    return true;
});

// Set exception handler
set_exception_handler(function($exception) {
    Logger::exception($exception);
    if (!Env::isProduction()) {
        echo "<pre>" . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "</pre>";
    }
});

// Set security headers
Security::setHeaders();

// Site settings from environment
define('SITE_NAME', Env::get('SITE_NAME', 'School Bus Tracking System'));
define('SITE_URL', Env::get('APP_URL', 'http://localhost'));

// Security settings from environment
define('SESSION_TIMEOUT', Env::get('SESSION_TIMEOUT', 3600));
define('PASSWORD_MIN_LENGTH', Env::get('PASSWORD_MIN_LENGTH', 8));

// Google Maps API Key from environment
define('GOOGLE_MAPS_API_KEY', Env::get('GOOGLE_MAPS_API_KEY', ''));

// Timezone from environment
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Beirut'));

// Helper functions
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getUserRole()
{
    return $_SESSION['user_role'] ?? null;
}

function getUserId()
{
    return $_SESSION['user_id'] ?? null;
}

function getUserName()
{
    return $_SESSION['user_name'] ?? null;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

function requireRole($role)
{
    requireLogin();
    if (getUserRole() !== $role) {
        header('Location: /dashboard.php');
        exit();
    }
}

function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatTime($time)
{
    return date('h:i A', strtotime($time));
}

function formatDateTime($datetime)
{
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Generate CSRF Token
 * @return string
 */
function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 * @param string $token
 * @return bool
 */
function verifyCsrfToken($token)
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF Input Field
 */
function csrfField()
{
    $token = generateCsrfToken();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Send JSON response
 * 
 * @param bool $success Success status
 * @param mixed $data Response data
 * @param string $message Response message
 * @param int $statusCode HTTP status code
 */
function sendJsonResponse($success, $data = null, $message = '', $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');

    $response = [
        'success' => $success,
        'message' => $message
    ];

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
        header('Location: /login.php?timeout=1');
        exit();
    }
}
$_SESSION['last_activity'] = time();
?>