<?php
/**
 * Backend API Router
 * 
 * Routes API requests to appropriate endpoints
 * 
 * @author Dana Baradie
 * @course IT404
 */

// Get request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string
$requestUri = strtok($requestUri, '?');

// Remove base path
$basePath = '/backend/api';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Remove leading/trailing slashes
$requestUri = trim($requestUri, '/');

// Split into parts
$parts = explode('/', $requestUri);

// Route to appropriate endpoint
if (empty($parts[0])) {
    // Root API endpoint - return API info
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'School Bus Tracking System API',
        'version' => '1.0',
        'endpoints' => [
            'auth' => '/backend/api/auth/',
            'buses' => '/backend/api/buses/',
            'routes' => '/backend/api/routes/',
            'gps' => '/backend/api/gps/',
            'notifications' => '/backend/api/notifications/'
        ]
    ]);
    exit();
}

// Route based on first part
$resource = $parts[0];
$action = $parts[1] ?? 'index';

// Map resources to files
$resourceMap = [
    'auth' => 'auth',
    'buses' => 'buses',
    'routes' => 'routes',
    'gps' => 'gps',
    'notifications' => 'notifications'
];

if (isset($resourceMap[$resource])) {
    $resourcePath = $resourceMap[$resource];
    
    // Determine file path
    $filePath = __DIR__ . '/api/' . $resourcePath;
    
    if ($action === 'index' || empty($action)) {
        $filePath .= '/index.php';
    } else {
        $filePath .= '/' . $action . '.php';
    }
    
    // Check if file exists
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found'
        ]);
    }
} else {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Resource not found'
    ]);
}
?>

