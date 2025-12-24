<?php
/**
 * Main Dashboard Controller
 * 
 * Redirects users to their specific dashboard based on role
 */

require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$role = getUserRole();

switch ($role) {
    case 'admin':
        // Admin uses the modern dashboard
        require_once 'dashboard-modern.php';
        break;

    case 'parent':
        header('Location: /parent-dashboard.php');
        exit();

    case 'driver':
        header('Location: /driver-dashboard.php');
        exit();

    default:
        // Fallback for unknown roles
        header('Location: /login.php');
        exit();
}
?>