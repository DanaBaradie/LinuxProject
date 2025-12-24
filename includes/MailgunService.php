<?php
/**
 * Mailgun Email Service
 * Handles sending and receiving emails via Mailgun API
 */

class MailgunService
{
    private $apiKey;
    private $domain;
    private $fromEmail;
    private $fromName;
    private $apiUrl;

    public function __construct()
    {
        // Load configuration from environment or config
        $this->apiKey = getenv('MAILGUN_API_KEY') ?: 'your_api_key_here';
        $this->domain = getenv('MAILGUN_DOMAIN') ?: 'sandbox.mailgun.org';
        $this->fromEmail = getenv('MAILGUN_FROM_EMAIL') ?: 'noreply@yourdomain.com';
        $this->fromName = getenv('MAILGUN_FROM_NAME') ?: 'Bus Tracking System';
        $this->apiUrl = "https://api.mailgun.net/v3/{$this->domain}/messages";
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
     * Send email via Mailgun API
     */
    public function sendEmail($to, $subject, $text = null, $html = null, $attachments = [])
    {
        $ch = curl_init();

        $postData = [
            'from' => "{$this->fromName} <{$this->fromEmail}>",
            'to' => $to,
            'subject' => $subject
        ];

        if ($text) {
            $postData['text'] = $text;
        }

        if ($html) {
            $postData['html'] = $html;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "api:{$this->apiKey}",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $this->logEmail($to, $subject, 'sent', $result['id'] ?? null);
            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'message_id' => $result['id'] ?? null
            ];
        } else {
            $this->logEmail($to, $subject, 'failed', null, $error ?: $response);
            return [
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $error ?: $response
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

        $resetUrl = getenv('APP_URL') . "/reset-password.php?token={$resetToken}";
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
     * Log email to database
     */
    private function logEmail($to, $subject, $status, $messageId = null, $error = null)
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $db = $database->getConnection();

            $query = "INSERT INTO email_messages (sender_email, sender_name, recipient_email, subject, status, message_id, error_message) 
                      VALUES (:sender_email, :sender_name, :recipient_email, :subject, :status, :message_id, :error_message)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':sender_email' => $this->fromEmail,
                ':sender_name' => $this->fromName,
                ':recipient_email' => $to,
                ':subject' => $subject,
                ':status' => $status,
                ':message_id' => $messageId,
                ':error_message' => $error
            ]);
        } catch (Exception $e) {
            error_log("Failed to log email: " . $e->getMessage());
        }
    }

    /**
     * Verify Mailgun webhook signature
     */
    public function verifyWebhookSignature($timestamp, $token, $signature)
    {
        $data = $timestamp . $token;
        $hash = hash_hmac('sha256', $data, $this->apiKey);
        return hash_equals($hash, $signature);
    }

    /**
     * Process incoming email from webhook
     */
    public function processIncomingEmail($data)
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $db = $database->getConnection();

            $query = "INSERT INTO email_messages (sender_email, sender_name, recipient_email, subject, body_text, body_html, message_id, status) 
                      VALUES (:sender_email, :sender_name, :recipient_email, :subject, :body_text, :body_html, :message_id, 'received')";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':sender_email' => $data['sender'] ?? '',
                ':sender_name' => $data['from'] ?? '',
                ':recipient_email' => $data['recipient'] ?? '',
                ':subject' => $data['subject'] ?? '',
                ':body_text' => $data['body-plain'] ?? '',
                ':body_html' => $data['body-html'] ?? '',
                ':message_id' => $data['Message-Id'] ?? ''
            ]);

            return ['success' => true, 'message' => 'Email received and stored'];
        } catch (Exception $e) {
            error_log("Failed to process incoming email: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>