<?php
/**
 * SendGrid Email Service
 * Handles sending and receiving emails via SendGrid API
 * 
 * @author Dana Baradie
 * @course IT404
 */

class SendGridService
{
    private $apiKey;
    private $fromEmail;
    private $fromName;
    private $replyToEmail;
    private $webhookUrl;

    public function __construct()
    {
        // Load configuration from environment or config
        $this->apiKey = getenv('SENDGRID_API_KEY') ?: (defined('SENDGRID_API_KEY') ? SENDGRID_API_KEY : '');
        $this->fromEmail = getenv('SENDGRID_FROM_EMAIL') ?: (defined('SENDGRID_FROM_EMAIL') ? SENDGRID_FROM_EMAIL : 'noreply@yourdomain.com');
        $this->fromName = getenv('SENDGRID_FROM_NAME') ?: (defined('SENDGRID_FROM_NAME') ? SENDGRID_FROM_NAME : 'Bus Tracking System');
        $this->replyToEmail = getenv('SENDGRID_REPLY_TO') ?: $this->fromEmail;
        $this->webhookUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/api/email/sendgrid-webhook.php';
    }

    /**
     * Send a simple text email
     */
    public function sendSimpleEmail($to, $subject, $text)
    {
        return $this->sendEmail($to, $subject, $text, null);
    }

    /**
     * Send an HTML email
     */
    public function sendHtmlEmail($to, $subject, $html, $text = null)
    {
        return $this->sendEmail($to, $subject, $text, $html);
    }

    /**
     * Send email via SendGrid API
     */
    public function sendEmail($to, $subject, $text = null, $html = null, $attachments = [], $cc = [], $bcc = [])
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'SendGrid API key not configured'
            ];
        }

        try {
            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($subject);
            $email->addTo($to);
            
            if ($html) {
                $email->addContent("text/html", $html);
            }
            if ($text) {
                $email->addContent("text/plain", $text);
            }
            
            // Add CC recipients
            foreach ($cc as $ccEmail) {
                $email->addCc($ccEmail);
            }
            
            // Add BCC recipients
            foreach ($bcc as $bccEmail) {
                $email->addBcc($bccEmail);
            }
            
            // Set reply-to
            $email->setReplyTo($this->replyToEmail);
            
            // Add attachments
            foreach ($attachments as $attachment) {
                $email->addAttachment($attachment);
            }

            $sendgrid = new \SendGrid($this->apiKey);
            $response = $sendgrid->send($email);

            $statusCode = $response->statusCode();
            $responseBody = $response->body();
            $responseHeaders = $response->headers();

            if ($statusCode >= 200 && $statusCode < 300) {
                $messageId = null;
                if (isset($responseHeaders['X-Message-Id'])) {
                    $messageId = $responseHeaders['X-Message-Id'];
                }
                
                $this->logEmail($to, $subject, 'sent', $messageId);
                
                return [
                    'success' => true,
                    'message' => 'Email sent successfully',
                    'message_id' => $messageId,
                    'status_code' => $statusCode
                ];
            } else {
                $this->logEmail($to, $subject, 'failed', null, $responseBody);
                return [
                    'success' => false,
                    'message' => 'Failed to send email',
                    'error' => $responseBody,
                    'status_code' => $statusCode
                ];
            }
        } catch (Exception $e) {
            $this->logEmail($to, $subject, 'failed', null, $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error sending email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send email to multiple recipients
     */
    public function sendBulkEmail($recipients, $subject, $text = null, $html = null)
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'SendGrid API key not configured'
            ];
        }

        try {
            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($subject);
            
            // Add all recipients
            foreach ($recipients as $recipient) {
                if (is_array($recipient)) {
                    $email->addTo($recipient['email'], $recipient['name'] ?? '');
                } else {
                    $email->addTo($recipient);
                }
            }
            
            if ($html) {
                $email->addContent("text/html", $html);
            }
            if ($text) {
                $email->addContent("text/plain", $text);
            }
            
            $email->setReplyTo($this->replyToEmail);

            $sendgrid = new \SendGrid($this->apiKey);
            $response = $sendgrid->send($email);

            $statusCode = $response->statusCode();
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logEmail('multiple', $subject, 'sent', null);
                return [
                    'success' => true,
                    'message' => 'Bulk email sent successfully',
                    'recipients' => count($recipients),
                    'status_code' => $statusCode
                ];
            } else {
                $this->logEmail('multiple', $subject, 'failed', null, $response->body());
                return [
                    'success' => false,
                    'message' => 'Failed to send bulk email',
                    'error' => $response->body()
                ];
            }
        } catch (Exception $e) {
            $this->logEmail('multiple', $subject, 'failed', null, $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error sending bulk email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail($userEmail, $userName, $password = null)
    {
        require_once __DIR__ . '/EmailTemplates.php';
        $templates = new EmailTemplates();

        $html = $templates->getWelcomeTemplate($userName, $userEmail, $password);
        $text = "Welcome to Bus Tracking System, {$userName}!\n\n";
        $text .= "Your account has been created successfully.\n";
        $text .= "Email: {$userEmail}\n";
        if ($password) {
            $text .= "Temporary Password: {$password}\n";
            $text .= "Please change your password after first login.\n";
        }

        return $this->sendEmail($userEmail, 'Welcome to Bus Tracking System', $text, $html);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($userEmail, $userName, $resetToken)
    {
        require_once __DIR__ . '/EmailTemplates.php';
        $templates = new EmailTemplates();

        $appUrl = getenv('APP_URL') ?: (defined('SITE_URL') ? SITE_URL : 'http://localhost');
        $resetUrl = $appUrl . "/reset-password.php?token={$resetToken}";
        $html = $templates->getPasswordResetTemplate($userName, $resetUrl);
        $text = "Hello {$userName},\n\n";
        $text .= "You requested a password reset for your Bus Tracking System account.\n\n";
        $text .= "Click the link below to reset your password:\n";
        $text .= "{$resetUrl}\n\n";
        $text .= "This link will expire in 1 hour.\n";
        $text .= "If you didn't request this, please ignore this email.\n";

        return $this->sendEmail($userEmail, 'Password Reset Request', $text, $html);
    }

    /**
     * Send notification email
     */
    public function sendNotificationEmail($to, $subject, $message, $type = 'info')
    {
        require_once __DIR__ . '/EmailTemplates.php';
        $templates = new EmailTemplates();

        $html = $templates->getNotificationTemplate($subject, $message, $type);
        return $this->sendEmail($to, $subject, $message, $html);
    }

    /**
     * Send custom email from admin
     */
    public function sendCustomEmail($to, $subject, $message)
    {
        require_once __DIR__ . '/EmailTemplates.php';
        $templates = new EmailTemplates();

        $html = $templates->getCustomMessageTemplate($subject, $message);
        return $this->sendEmail($to, $subject, strip_tags($message), $html);
    }

    /**
     * Process incoming email from SendGrid webhook
     */
    public function processIncomingEmail($webhookData)
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $db = $database->getConnection();

            // SendGrid webhook sends events as an array
            foreach ($webhookData as $event) {
                if (isset($event['event']) && $event['event'] === 'inbound') {
                    // This is an incoming email
                    $from = $event['from'] ?? '';
                    $to = $event['to'] ?? '';
                    $subject = $event['subject'] ?? 'No Subject';
                    $text = $event['text'] ?? '';
                    $html = $event['html'] ?? '';
                    $timestamp = isset($event['timestamp']) ? date('Y-m-d H:i:s', $event['timestamp']) : date('Y-m-d H:i:s');

                    // Save to database
                    $query = "INSERT INTO email_messages 
                             (sender_email, sender_name, recipient_email, subject, message_body, message_html, status, direction, created_at) 
                             VALUES (:from_email, :from_name, :to_email, :subject, :text, :html, 'received', 'inbound', :timestamp)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':from_email' => $from,
                        ':from_name' => $from, // SendGrid doesn't always separate name
                        ':to_email' => $to,
                        ':subject' => $subject,
                        ':text' => $text,
                        ':html' => $html,
                        ':timestamp' => $timestamp
                    ]);

                    // Create notification for admin
                    $this->createEmailNotification($from, $subject, $text);
                }
            }

            return ['success' => true, 'message' => 'Incoming email processed'];
        } catch (Exception $e) {
            error_log("SendGrid webhook error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create notification for received email
     */
    private function createEmailNotification($from, $subject, $message)
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $db = $database->getConnection();

            // Get admin users
            $query = "SELECT id FROM users WHERE role = 'admin'";
            $stmt = $db->query($query);
            $admins = $stmt->fetchAll();

            foreach ($admins as $admin) {
                $notifQuery = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                              VALUES (:user_id, :title, :message, 'email', NOW())";
                $notifStmt = $db->prepare($notifQuery);
                $notifStmt->execute([
                    ':user_id' => $admin['id'],
                    ':title' => 'New Email Received: ' . $subject,
                    ':message' => 'From: ' . $from . "\n\n" . substr($message, 0, 200)
                ]);
            }
        } catch (Exception $e) {
            error_log("Error creating email notification: " . $e->getMessage());
        }
    }

    /**
     * Log email to database
     */
    private function logEmail($to, $subject, $status, $messageId = null, $error = null)
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $db = $database->getConnection();

            $query = "INSERT INTO email_messages 
                     (sender_email, sender_name, recipient_email, subject, status, message_id, error_message, direction, created_at) 
                     VALUES (:from_email, :from_name, :to_email, :subject, :status, :message_id, :error, 'outbound', NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':from_email' => $this->fromEmail,
                ':from_name' => $this->fromName,
                ':to_email' => $to,
                ':subject' => $subject,
                ':status' => $status,
                ':message_id' => $messageId,
                ':error' => $error
            ]);
        } catch (Exception $e) {
            error_log("Error logging email: " . $e->getMessage());
        }
    }

    /**
     * Log email event (bounce, open, click, etc.)
     */
    public function logEmailEvent($event)
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $db = $database->getConnection();

            $eventType = $event['event'] ?? 'unknown';
            $email = $event['email'] ?? '';
            $timestamp = isset($event['timestamp']) ? date('Y-m-d H:i:s', $event['timestamp']) : date('Y-m-d H:i:s');
            $reason = $event['reason'] ?? '';
            $status = $event['status'] ?? '';

            // Update email status if it's a delivery event
            if (in_array($eventType, ['bounce', 'dropped', 'deferred', 'delivered'])) {
                $query = "UPDATE email_messages 
                         SET status = :status, 
                             error_message = :error,
                             updated_at = :timestamp
                         WHERE recipient_email = :email 
                         ORDER BY created_at DESC 
                         LIMIT 1";
                
                $stmt = $db->prepare($query);
                $statusValue = ($eventType === 'delivered') ? 'sent' : 'failed';
                $stmt->execute([
                    ':status' => $statusValue,
                    ':error' => $reason ?: $status,
                    ':email' => $email,
                    ':timestamp' => $timestamp
                ]);
            }
        } catch (Exception $e) {
            error_log("Error logging email event: " . $e->getMessage());
        }
    }

    /**
     * Get email statistics
     */
    public function getEmailStats($days = 30)
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $db = $database->getConnection();

            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                        SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                     FROM email_messages 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $stmt = $db->prepare($query);
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting email stats: " . $e->getMessage());
            return null;
        }
    }
}

