<?php
header('Content-Type: application/json');
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../includes/SendGridService.php';

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
$cc = isset($_POST['cc']) ? array_filter(array_map('trim', explode(',', $_POST['cc']))) : [];
$bcc = isset($_POST['bcc']) ? array_filter(array_map('trim', explode(',', $_POST['bcc']))) : [];

if (empty($to) || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate CC and BCC emails
foreach ($cc as $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CC email address: ' . $email]);
        exit;
    }
}

foreach ($bcc as $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid BCC email address: ' . $email]);
        exit;
    }
}

try {
    $sendgrid = new SendGridService();

    if ($type === 'notification') {
        $result = $sendgrid->sendNotificationEmail($to, $subject, $message);
    } else {
        $result = $sendgrid->sendCustomEmail($to, $subject, $message);
    }
    
    // Add CC and BCC if provided
    if (!empty($cc) || !empty($bcc)) {
        $result = $sendgrid->sendEmail($to, $subject, strip_tags($message), $message, [], $cc, $bcc);
    }

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>