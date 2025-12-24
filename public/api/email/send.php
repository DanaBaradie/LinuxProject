<?php
header('Content-Type: application/json');
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../includes/MailgunService.php';

requireLogin();
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$to = sanitizeInput($_POST['to'] ?? '');
$subject = sanitizeInput($_POST['subject'] ?? '');
$message = $_POST['message'] ?? '';
$type = sanitizeInput($_POST['type'] ?? 'custom');

if (empty($to) || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    $mailgun = new MailgunService();

    if ($type === 'notification') {
        $result = $mailgun->sendNotificationEmail($to, $subject, $message);
    } else {
        $result = $mailgun->sendCustomEmail($to, $subject, $message);
    }

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>