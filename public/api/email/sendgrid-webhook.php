<?php
/**
 * SendGrid Webhook Handler
 * Receives incoming emails and email events from SendGrid
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../includes/SendGridService.php';

// SendGrid webhook verification
function verifySendGridWebhook($payload, $signature, $timestamp, $publicKey) {
    if (empty($signature) || empty($timestamp)) {
        return false;
    }
    
    $data = $timestamp . $payload;
    $hmac = hash_hmac('sha256', $data, $publicKey, true);
    $expectedSignature = base64_encode($hmac);
    
    return hash_equals($expectedSignature, $signature);
}

// Get raw POST data
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Verify webhook (optional but recommended)
$sendgridPublicKey = getenv('SENDGRID_WEBHOOK_PUBLIC_KEY') ?: (defined('SENDGRID_WEBHOOK_PUBLIC_KEY') ? SENDGRID_WEBHOOK_PUBLIC_KEY : '');
if (!empty($sendgridPublicKey)) {
    // SendGrid uses different header names - check both
    $signature = $headers['X-SendGrid-Signature'] ?? $headers['X-Twilio-Email-Event-Webhook-Signature'] ?? '';
    $timestamp = $headers['X-SendGrid-Timestamp'] ?? $headers['X-Twilio-Email-Event-Webhook-Timestamp'] ?? '';
    
    if (!empty($signature) && !empty($timestamp)) {
        if (!verifySendGridWebhook($payload, $signature, $timestamp, $sendgridPublicKey)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid signature']);
            exit;
        }
    }
}

// Parse webhook data
$events = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

try {
    $sendgrid = new SendGridService();
    
    // Process events
    foreach ($events as $event) {
        $eventType = $event['event'] ?? '';
        
        switch ($eventType) {
            case 'inbound':
                // Incoming email
                $sendgrid->processIncomingEmail([$event]);
                break;
                
            case 'bounce':
            case 'dropped':
            case 'deferred':
            case 'delivered':
            case 'open':
            case 'click':
            case 'spamreport':
            case 'unsubscribe':
                // Email delivery and engagement events - log them
                if (method_exists($sendgrid, 'logEmailEvent')) {
                    $sendgrid->logEmailEvent($event);
                }
                break;
        }
    }
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Webhook processed']);
    
} catch (Exception $e) {
    error_log("SendGrid webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error processing webhook']);
}

