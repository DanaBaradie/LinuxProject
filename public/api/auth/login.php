<?php
/**
 * Authentication API - Login Endpoint
 * 
 * POST /api/auth/login.php
 * Body: { "email": "user@example.com", "password": "password123" }
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['email']) || !isset($input['password'])) {
    sendJsonResponse(false, null, 'Email and password are required', 400);
}

$email = sanitizeInput($input['email']);
$password = $input['password'];

if (empty($email) || empty($password)) {
    sendJsonResponse(false, null, 'Email and password cannot be empty', 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id, email, password, full_name, role FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, null, 'Invalid email or password', 401);
    }

    $user = $stmt->fetch();

    if (!password_verify($password, $user['password'])) {
        sendJsonResponse(false, null, 'Invalid email or password', 401);
    }

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    // Return user data (without password)
    unset($user['password']);
    
    sendJsonResponse(true, [
        'user' => $user,
        'session_id' => session_id()
    ], 'Login successful');

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    sendJsonResponse(false, null, 'An error occurred. Please try again.', 500);
}
?>

