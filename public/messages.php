<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get all email messages
$query = "SELECT * FROM email_messages ORDER BY created_at DESC LIMIT 100";
$messages = $db->query($query)->fetchAll();

// Get statistics
$stats = [
    'total' => 0,
    'sent' => 0,
    'received' => 0,
    'failed' => 0
];

$statsQuery = "SELECT status, COUNT(*) as count FROM email_messages GROUP BY status";
$statsResult = $db->query($statsQuery)->fetchAll();
foreach ($statsResult as $stat) {
    $stats[$stat['status']] = $stat['count'];
    $stats['total'] += $stat['count'];
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-envelope me-2"></i>Email Messages</h1>
                <button class="btn btn-primary" onclick="window.location.href='/users.php'">
                    <i class="fas fa-arrow-left me-2"></i>Back to Users
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm" style="border-left: 4px solid #0d6efd !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Messages</h6>
                                    <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-envelope fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm" style="border-left: 4px solid #198754 !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Sent</h6>
                                    <h3 class="mb-0"><?php echo $stats['sent']; ?></h3>
                                </div>
                                <div class="text-success">
                                    <i class="fas fa-paper-plane fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm" style="border-left: 4px solid #0dcaf0 !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Received</h6>
                                    <h3 class="mb-0"><?php echo $stats['received']; ?></h3>
                                </div>
                                <div class="text-info">
                                    <i class="fas fa-inbox fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm" style="border-left: 4px solid #dc3545 !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Failed</h6>
                                    <h3 class="mb-0"><?php echo $stats['failed']; ?></h3>
                                </div>
                                <div class="text-danger">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-envelope fa-4x text-muted mb-3"></i>
                            <h4>No Messages Yet</h4>
                            <p class="text-muted">Email messages will appear here</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $msg): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                            <td>
                                                <i class="fas fa-user-circle me-1"></i>
                                                <?php echo htmlspecialchars($msg['sender_email']); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($msg['recipient_email']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'sent' => 'success',
                                                    'delivered' => 'info',
                                                    'failed' => 'danger',
                                                    'received' => 'primary'
                                                ];
                                                $color = $statusColors[$msg['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo ucfirst($msg['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info"
                                                    onclick="viewMessage(<?php echo htmlspecialchars(json_encode($msg)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- View Message Modal -->
<div class="modal fade" id="viewMessageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="messageContent">
                <!-- Content loaded by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewMessage(message) {
        let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>From:</strong><br>
                ${message.sender_email}
            </div>
            <div class="col-md-6">
                <strong>To:</strong><br>
                ${message.recipient_email}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Subject:</strong><br>
                ${message.subject}
            </div>
            <div class="col-md-6">
                <strong>Date:</strong><br>
                ${new Date(message.created_at).toLocaleString()}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <strong>Status:</strong><br>
                <span class="badge bg-${message.status === 'sent' ? 'success' : message.status === 'failed' ? 'danger' : 'info'}">${message.status}</span>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12">
                <strong>Message:</strong><br>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px;">
                    ${message.body_html || message.body_text || 'No content'}
                </div>
            </div>
        </div>
        ${message.error_message ? `
        <hr>
        <div class="alert alert-danger">
            <strong>Error:</strong> ${message.error_message}
        </div>
        ` : ''}
    `;

        document.getElementById('messageContent').innerHTML = html;
        var modal = new bootstrap.Modal(document.getElementById('viewMessageModal'));
        modal.show();
    }
</script>

<?php require_once '../includes/footer.php'; ?>