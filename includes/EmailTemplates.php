<?php
/**
 * Email Templates
 * Provides HTML email templates for various purposes
 */

class EmailTemplates
{
    private $appName;
    private $appUrl;

    public function __construct()
    {
        $this->appName = getenv('APP_NAME') ?: 'Bus Tracking System';
        $this->appUrl = getenv('APP_URL') ?: 'http://localhost';
    }

    /**
     * Get base email template
     */
    private function getBaseTemplate($content)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .button { display: inline-block; padding: 12px 30px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .button:hover { background: #0b5ed7; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
                .info-box { background: #e7f3ff; border-left: 4px solid #0d6efd; padding: 15px; margin: 20px 0; }
                .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
                .success-box { background: #d1e7dd; border-left: 4px solid #198754; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$this->appName}</h1>
                </div>
                <div class='content'>
                    {$content}
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " {$this->appName}. All rights reserved.</p>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Welcome email template
     */
    public function getWelcomeTemplate($userName, $userEmail, $password = null)
    {
        $content = "
            <h2>Welcome, {$userName}!</h2>
            <p>Your account has been successfully created in the {$this->appName}.</p>
            
            <div class='info-box'>
                <strong>Your Account Details:</strong><br>
                Email: {$userEmail}<br>
                " . ($password ? "Temporary Password: <strong>{$password}</strong><br>" : "") . "
            </div>
            
            " . ($password ? "
            <div class='warning-box'>
                <strong>Important:</strong> Please change your password after your first login for security purposes.
            </div>
            " : "") . "
            
            <p>You can now log in to access your dashboard and start using the system.</p>
            
            <a href='{$this->appUrl}/login.php' class='button'>Login to Your Account</a>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
        ";

        return $this->getBaseTemplate($content);
    }

    /**
     * Password reset email template
     */
    public function getPasswordResetTemplate($userName, $resetUrl)
    {
        $content = "
            <h2>Password Reset Request</h2>
            <p>Hello {$userName},</p>
            <p>We received a request to reset your password for your {$this->appName} account.</p>
            
            <p>Click the button below to reset your password:</p>
            
            <a href='{$resetUrl}' class='button'>Reset Password</a>
            
            <div class='warning-box'>
                <strong>Important:</strong> This link will expire in 1 hour for security reasons.
            </div>
            
            <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
            
            <p style='color: #6c757d; font-size: 12px;'>If the button doesn't work, copy and paste this link into your browser:<br>
            {$resetUrl}</p>
        ";

        return $this->getBaseTemplate($content);
    }

    /**
     * Notification email template
     */
    public function getNotificationTemplate($title, $message, $type = 'info')
    {
        $boxClass = 'info-box';
        $icon = 'ℹ️';

        if ($type === 'warning') {
            $boxClass = 'warning-box';
            $icon = '⚠️';
        } elseif ($type === 'success') {
            $boxClass = 'success-box';
            $icon = '✅';
        }

        $content = "
            <h2>{$icon} {$title}</h2>
            
            <div class='{$boxClass}'>
                {$message}
            </div>
            
            <p>You can view more details by logging into your account.</p>
            
            <a href='{$this->appUrl}/dashboard.php' class='button'>View Dashboard</a>
        ";

        return $this->getBaseTemplate($content);
    }

    /**
     * Custom message template
     */
    public function getCustomMessageTemplate($subject, $message)
    {
        $content = "
            <h2>{$subject}</h2>
            
            <div style='margin: 20px 0;'>
                {$message}
            </div>
            
            <p>If you have any questions, please contact the administrator.</p>
        ";

        return $this->getBaseTemplate($content);
    }

    /**
     * Student assignment notification
     */
    public function getStudentAssignmentTemplate($parentName, $studentName, $busNumber, $stopName)
    {
        $content = "
            <h2>Student Bus Assignment</h2>
            <p>Hello {$parentName},</p>
            <p>Your child has been assigned to a bus route.</p>
            
            <div class='success-box'>
                <strong>Assignment Details:</strong><br>
                Student: {$studentName}<br>
                Bus Number: {$busNumber}<br>
                Stop: {$stopName}
            </div>
            
            <p>You can now track the bus location in real-time through your parent dashboard.</p>
            
            <a href='{$this->appUrl}/track-bus.php' class='button'>Track Bus</a>
        ";

        return $this->getBaseTemplate($content);
    }

    /**
     * Driver assignment notification
     */
    public function getDriverAssignmentTemplate($driverName, $busNumber, $routeName)
    {
        $content = "
            <h2>Bus Assignment</h2>
            <p>Hello {$driverName},</p>
            <p>You have been assigned to a new bus.</p>
            
            <div class='info-box'>
                <strong>Assignment Details:</strong><br>
                Bus Number: {$busNumber}<br>
                Route: {$routeName}
            </div>
            
            <p>Please review your route details and schedule in your driver dashboard.</p>
            
            <a href='{$this->appUrl}/my-bus.php' class='button'>View My Bus</a>
        ";

        return $this->getBaseTemplate($content);
    }

    /**
     * Bus delay notification
     */
    public function getBusDelayTemplate($recipientName, $busNumber, $delayMinutes, $reason = '')
    {
        $content = "
            <h2>⚠️ Bus Delay Notification</h2>
            <p>Hello {$recipientName},</p>
            <p>We want to inform you about a delay in bus service.</p>
            
            <div class='warning-box'>
                <strong>Delay Information:</strong><br>
                Bus Number: {$busNumber}<br>
                Estimated Delay: {$delayMinutes} minutes<br>
                " . ($reason ? "Reason: {$reason}<br>" : "") . "
            </div>
            
            <p>We apologize for any inconvenience. You can track the bus in real-time for updates.</p>
            
            <a href='{$this->appUrl}/track-bus.php' class='button'>Track Bus Now</a>
        ";

        return $this->getBaseTemplate($content);
    }
}
?>