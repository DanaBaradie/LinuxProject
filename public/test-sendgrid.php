<?php
/**
 * SendGrid Test Page
 * 
 * Test sending and receiving emails via SendGrid
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/SendGridService.php';

requireLogin();
requireRole('admin');

$result = null;
$error = null;

// Handle test email send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_send'])) {
    $to = sanitizeInput($_POST['test_email'] ?? '');
    $subject = sanitizeInput($_POST['test_subject'] ?? 'Test Email from Bus Tracking System');
    $message = $_POST['test_message'] ?? 'This is a test email from the School Bus Tracking System.';
    
    if (empty($to)) {
        $error = 'Please enter a test email address';
    } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        try {
            $sendgrid = new SendGridService();
            $result = $sendgrid->sendHtmlEmail(
                $to,
                $subject,
                '<html><body><h2>Test Email</h2><p>' . htmlspecialchars($message) . '</p><p><strong>Sent via SendGrid</strong></p><p>Time: ' . date('Y-m-d H:i:s') . '</p></body></html>',
                strip_tags($message)
            );
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get email statistics
$stats = null;
try {
    $sendgrid = new SendGridService();
    $stats = $sendgrid->getEmailStats(30);
} catch (Exception $e) {
    // Stats not critical
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-vial me-2"></i>Test SendGrid</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($result): ?>
                <?php if ($result['success']): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><strong>Email Sent Successfully!</strong><br>
                        Message ID: <?php echo htmlspecialchars($result['message_id'] ?? 'N/A'); ?><br>
                        Status Code: <?php echo htmlspecialchars($result['status_code'] ?? 'N/A'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-times-circle me-2"></i><strong>Failed to Send Email</strong><br>
                        <?php echo htmlspecialchars($result['message'] ?? 'Unknown error'); ?><br>
                        <?php if (isset($result['error'])): ?>
                            <small>Error: <?php echo htmlspecialchars($result['error']); ?></small>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Configuration Status -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>SendGrid Configuration Status</h5>
                </div>
                <div class="card-body">
                    <?php
                    $apiKey = defined('SENDGRID_API_KEY') ? SENDGRID_API_KEY : (getenv('SENDGRID_API_KEY') ?: '');
                    $fromEmail = defined('SENDGRID_FROM_EMAIL') ? SENDGRID_FROM_EMAIL : (getenv('SENDGRID_FROM_EMAIL') ?: '');
                    $fromName = defined('SENDGRID_FROM_NAME') ? SENDGRID_FROM_NAME : (getenv('SENDGRID_FROM_NAME') ?: '');
                    ?>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>API Key:</strong> 
                                <?php if (!empty($apiKey) && $apiKey !== 'YOUR_SENDGRID_API_KEY_HERE'): ?>
                                    <span class="badge bg-success">Configured</span>
                                    <small class="text-muted">(<?php echo substr($apiKey, 0, 10) . '...'; ?>)</small>
                                <?php else: ?>
                                    <span class="badge bg-danger">Not Configured</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>From Email:</strong> 
                                <?php if (!empty($fromEmail) && $fromEmail !== 'noreply@yourdomain.com'): ?>
                                    <span class="badge bg-success"><?php echo htmlspecialchars($fromEmail); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Default (needs configuration)</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>From Name:</strong> 
                                <span class="badge bg-info"><?php echo htmlspecialchars($fromName); ?></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <?php if ($stats): ?>
                                <h6>Email Statistics (Last 30 Days)</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Total:</strong> <?php echo $stats['total'] ?? 0; ?></li>
                                    <li><strong>Sent:</strong> <span class="text-success"><?php echo $stats['sent'] ?? 0; ?></span></li>
                                    <li><strong>Received:</strong> <span class="text-info"><?php echo $stats['received'] ?? 0; ?></span></li>
                                    <li><strong>Failed:</strong> <span class="text-danger"><?php echo $stats['failed'] ?? 0; ?></span></li>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (empty($apiKey) || $apiKey === 'YOUR_SENDGRID_API_KEY_HERE'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>SendGrid not configured!</strong><br><br>
                            <strong>How to Configure SendGrid:</strong><br><br>
                            <strong>Step 1: Get SendGrid API Key</strong><br>
                            1. Sign up at <a href="https://sendgrid.com/" target="_blank">https://sendgrid.com/</a> (free tier available)<br>
                            2. Log in to your SendGrid account<br>
                            3. Go to <strong>Settings → API Keys</strong><br>
                            4. Click <strong>"Create API Key"</strong><br>
                            5. Name it (e.g., "Bus Tracking System")<br>
                            6. Select <strong>"Full Access"</strong> or <strong>"Restricted Access"</strong> with Mail Send permission<br>
                            7. Click <strong>"Create & View"</strong><br>
                            8. <strong>COPY THE API KEY</strong> (starts with "SG.") - you won't see it again!<br><br>
                            
                            <strong>Step 2: Verify Your Sender Email</strong><br>
                            1. Go to <strong>Settings → Sender Authentication</strong><br>
                            2. Click <strong>"Single Sender Verification"</strong> or <strong>"Domain Authentication"</strong><br>
                            3. Follow the verification steps<br>
                            4. Wait for verification email and confirm<br><br>
                            
                            <strong>Step 3: Update Configuration</strong><br>
                            Edit the file: <code>config/config.php</code><br><br>
                            Replace this line:<br>
                            <code>define('SENDGRID_API_KEY', 'YOUR_SENDGRID_API_KEY_HERE');</code><br><br>
                            With your actual API key:<br>
                            <code>define('SENDGRID_API_KEY', 'SG.your_actual_api_key_here');</code><br><br>
                            
                            Also update the from email:<br>
                            <code>define('SENDGRID_FROM_EMAIL', 'your-verified-email@yourdomain.com');</code><br><br>
                            
                            <strong>Step 4: Test</strong><br>
                            After saving the file, refresh this page and try sending a test email!<br><br>
                            
                            <strong>Need Help?</strong> See <code>SENDGRID_SETUP.md</code> for detailed instructions.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Test Email Sending -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Test Email Sending</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="test_email" class="form-label">Test Email Address</label>
                            <input type="email" class="form-control" id="test_email" name="test_email" 
                                   placeholder="your-email@example.com" required
                                   value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
                            <small class="form-text text-muted">Enter the email address where you want to receive the test email</small>
                        </div>

                        <div class="mb-3">
                            <label for="test_subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="test_subject" name="test_subject" 
                                   value="Test Email from Bus Tracking System" required>
                        </div>

                        <div class="mb-3">
                            <label for="test_message" class="form-label">Message</label>
                            <textarea class="form-control" id="test_message" name="test_message" rows="5" required>This is a test email from the School Bus Tracking System.

If you receive this email, SendGrid is working correctly!

Time: <?php echo date('Y-m-d H:i:s'); ?></textarea>
                        </div>

                        <button type="submit" name="test_send" class="btn btn-success btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Send Test Email
                        </button>
                    </form>
                </div>
            </div>

            <!-- Test Different Email Types -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-envelope-open-text me-2"></i>Test Email Types</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6><i class="fas fa-user-plus me-2"></i>Welcome Email</h6>
                                    <p class="text-muted small">Test welcome email template</p>
                                    <button class="btn btn-sm btn-primary" onclick="testWelcomeEmail()">
                                        <i class="fas fa-play me-1"></i>Test
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6><i class="fas fa-key me-2"></i>Password Reset</h6>
                                    <p class="text-muted small">Test password reset email</p>
                                    <button class="btn btn-sm btn-primary" onclick="testPasswordReset()">
                                        <i class="fas fa-play me-1"></i>Test
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6><i class="fas fa-bell me-2"></i>Notification</h6>
                                    <p class="text-muted small">Test notification email</p>
                                    <button class="btn btn-sm btn-primary" onclick="testNotification()">
                                        <i class="fas fa-play me-1"></i>Test
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Email Logs -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Email Logs</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $database = new Database();
                        $db = $database->getConnection();
                        $query = "SELECT * FROM email_messages ORDER BY created_at DESC LIMIT 10";
                        $stmt = $db->query($query);
                        $emails = $stmt->fetchAll();
                        
                        if (empty($emails)):
                    ?>
                        <p class="text-muted">No emails logged yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Direction</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($emails as $email): ?>
                                        <tr>
                                            <td><?php echo date('M d, H:i', strtotime($email['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($email['sender_email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($email['recipient_email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($email['subject'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = 'secondary';
                                                if ($email['status'] === 'sent') $statusClass = 'success';
                                                elseif ($email['status'] === 'received') $statusClass = 'info';
                                                elseif ($email['status'] === 'failed') $statusClass = 'danger';
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($email['status'] ?? 'unknown'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo ($email['direction'] ?? 'outbound') === 'inbound' ? 'primary' : 'secondary'; ?>">
                                                    <?php echo ucfirst($email['direction'] ?? 'outbound'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <?php
                    } catch (Exception $e) {
                        echo '<div class="alert alert-warning">';
                        echo '<i class="fas fa-exclamation-triangle me-2"></i>Could not load email logs: ' . htmlspecialchars($e->getMessage());
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function testWelcomeEmail() {
    const email = document.getElementById('test_email').value || '<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>';
    if (!email) {
        alert('Please enter a test email address first');
        return;
    }
    
    if (confirm('Send welcome email test to ' + email + '?')) {
        fetch('/api/email/send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                to: email,
                subject: 'Welcome Email Test',
                message: 'This is a test welcome email.',
                type: 'notification'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Welcome email sent successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to send email'));
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}

function testPasswordReset() {
    const email = document.getElementById('test_email').value || '<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>';
    if (!email) {
        alert('Please enter a test email address first');
        return;
    }
    
    if (confirm('Send password reset email test to ' + email + '?')) {
        fetch('/api/email/send-password-reset.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                email: email
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password reset email sent successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to send email'));
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}

function testNotification() {
    const email = document.getElementById('test_email').value || '<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>';
    if (!email) {
        alert('Please enter a test email address first');
        return;
    }
    
    if (confirm('Send notification email test to ' + email + '?')) {
        fetch('/api/email/send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                to: email,
                subject: 'Test Notification',
                message: 'This is a test notification email from the Bus Tracking System.',
                type: 'notification'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Notification email sent successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to send email'));
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>

