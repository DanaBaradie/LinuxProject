
<?php
// Application Configuration
session_start();

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

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (getUserRole() !== $role) {
        header('Location: /dashboard.php');
        exit();
    }
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
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
