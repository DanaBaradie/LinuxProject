<?php
/**
 * Mailgun Webhook Endpoint
 * Receives incoming emails from Mailgun
 */

require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../includes/MailgunService.php';

// Get webhook data
$timestamp = $_POST['timestamp'] ?? '';
$token = $_POST['token'] ?? '';
$signature = $_POST['signature'] ?? '';

// Verify webhook signature
$mailgun = new MailgunService();
if (!$mailgun->verifyWebhookSignature($timestamp, $token, $signature)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Process incoming email
$emailData = [
    'sender' => $_POST['sender'] ?? '',
    'from' => $_POST['from'] ?? '',
    'recipient' => $_POST['recipient'] ?? '',
    'subject' => $_POST['subject'] ?? '',
    'body-plain' => $_POST['body-plain'] ?? '',
    'body-html' => $_POST['body-html'] ?? '',
    'Message-Id' => $_POST['Message-Id'] ?? ''
];

$result = $mailgun->processIncomingEmail($emailData);

if ($result['success']) {
    http_response_code(200);
    echo json_encode(['message' => 'Email received']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process email']);
}
?>