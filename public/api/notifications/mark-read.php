<?php
header('Content-Type: application/json');
require_once '../../../config/config.php';
require_once '../../../config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit;
}

$notification_id = $input['notification_id'];

try {
    // Verify ownership and mark as read
    // Allow if it belongs to user OR if user is admin (optional, but sticking to owner for now)
    $query = "UPDATE notifications 
              SET is_read = TRUE 
              WHERE id = :id AND parent_id = :user_id";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':id' => $notification_id,
        ':user_id' => $user_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Marked as read']);
    } else {
        // Could be already read, or doesn't belong to user
        echo json_encode(['success' => false, 'message' => 'Notification not found or already read']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>