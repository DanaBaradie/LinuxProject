<?php
/**
 * Authentication API - Logout Endpoint
 * 
 * POST /api/auth/logout.php
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../../config/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

// Destroy session
session_destroy();

sendJsonResponse(true, null, 'Logged out successfully');
?>

