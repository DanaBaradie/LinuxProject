<?php
/**
 * Email System Test Script
 * Tests Mailgun integration and email functionality
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/MailgunService.php';
require_once '../includes/EmailTemplates.php';

// Set test email (change this to your email)
$testEmail = 'your-email@example.com';
$testName = 'Test User';

echo "<h1>Mailgun Email System Test</h1>";
echo "<hr>";

// Test 1: Check configuration
echo "<h2>Test 1: Configuration Check</h2>";
$apiKey = getenv('MAILGUN_API_KEY') ?: 'Not set';
$domain = getenv('MAILGUN_DOMAIN') ?: 'Not set';
$fromEmail = getenv('MAILGUN_FROM_EMAIL') ?: 'Not set';

echo "API Key: " . (strlen($apiKey) > 10 ? substr($apiKey, 0, 10) . '...' : $apiKey) . "<br>";
echo "Domain: $domain<br>";
echo "From Email: $fromEmail<br>";

if ($apiKey === 'Not set' || $domain === 'Not set') {
    echo "<p style='color: red;'>❌ Configuration incomplete! Please set up .env file.</p>";
    exit;
}
echo "<p style='color: green;'>✅ Configuration looks good!</p>";
echo "<hr>";

// Test 2: Database connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connected successfully!</p>";

    // Check if tables exist
    $tables = ['email_messages', 'password_reset_tokens', 'email_preferences'];
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $result = $db->query($query);
        if ($result->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing! Run email_schema.sql</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
echo "<hr>";

// Test 3: Send simple email
echo "<h2>Test 3: Send Simple Email</h2>";
echo "<p>Sending test email to: $testEmail</p>";

try {
    $mailgun = new MailgunService();
    $result = $mailgun->sendSimpleEmail(
        $testEmail,
        'Test Email from Bus Tracking System',
        'This is a test email to verify Mailgun integration is working correctly.'
    );

    if ($result['success']) {
        echo "<p style='color: green;'>✅ Email sent successfully!</p>";
        echo "<p>Message ID: " . ($result['message_id'] ?? 'N/A') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to send email</p>";
        echo "<p>Error: " . ($result['error'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}
echo "<hr>";

// Test 4: Send HTML email with template
echo "<h2>Test 4: Send HTML Email with Template</h2>";

try {
    $mailgun = new MailgunService();
    $result = $mailgun->sendWelcomeEmail($testEmail, $testName, 'test123');

    if ($result['success']) {
        echo "<p style='color: green;'>✅ Welcome email sent successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to send welcome email</p>";
        echo "<p>Error: " . ($result['error'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}
echo "<hr>";

// Test 5: Check email logs
echo "<h2>Test 5: Email Logs</h2>";
try {
    $query = "SELECT * FROM email_messages ORDER BY created_at DESC LIMIT 5";
    $messages = $db->query($query)->fetchAll();

    if (empty($messages)) {
        echo "<p>No email logs found yet.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>To</th><th>Subject</th><th>Status</th><th>Created</th></tr>";
        foreach ($messages as $msg) {
            $statusColor = $msg['status'] === 'sent' ? 'green' : ($msg['status'] === 'failed' ? 'red' : 'blue');
            echo "<tr>";
            echo "<td>" . htmlspecialchars($msg['recipient_email']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['subject']) . "</td>";
            echo "<td style='color: $statusColor;'>" . $msg['status'] . "</td>";
            echo "<td>" . $msg['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✅ Email logging is working!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error reading logs: " . $e->getMessage() . "</p>";
}
echo "<hr>";

// Test 6: Template rendering
echo "<h2>Test 6: Template Rendering</h2>";
try {
    $templates = new EmailTemplates();
    $html = $templates->getWelcomeTemplate($testName, $testEmail, 'test123');

    if (strlen($html) > 100) {
        echo "<p style='color: green;'>✅ Templates are rendering correctly!</p>";
        echo "<details><summary>View HTML (click to expand)</summary>";
        echo "<pre>" . htmlspecialchars($html) . "</pre>";
        echo "</details>";
    } else {
        echo "<p style='color: red;'>❌ Template rendering failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}
echo "<hr>";

echo "<h2>Summary</h2>";
echo "<p>Tests completed! Check the results above.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Check your email inbox for test messages</li>";
echo "<li>If using sandbox domain, make sure your email is authorized</li>";
echo "<li>Check Mailgun dashboard for delivery logs</li>";
echo "<li>Review the messages page in the application</li>";
echo "</ul>";
?>