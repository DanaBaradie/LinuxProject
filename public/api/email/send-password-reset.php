<?php
header('Content-Type: application/json');
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../includes/MailgunService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = sanitizeInput($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if user exists
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No account found with this email']);
        exit;
    }

    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store token in database
    $query = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id' => $user['id'],
        ':token' => $token,
        ':expires_at' => $expiresAt
    ]);

    // Send email
    $mailgun = new MailgunService();
    $result = $mailgun->sendPasswordResetEmail($email, $user['full_name'], $token);

    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'Password reset email sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>