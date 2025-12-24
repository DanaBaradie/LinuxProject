<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        try {
            // Get current password hash
            $query = "SELECT password FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch();

            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                // Update password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = :password WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':password' => $new_password_hash,
                    ':id' => $user_id
                ]);

                $success = 'Password changed successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error changing password: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-key me-2"></i>Change Password</h1>
                <a href="/profile.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Profile
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Update Your Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="passwordForm">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-lock me-1"></i>Current Password <span
                                            class="text-danger">*</span>
                                    </label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-key me-1"></i>New Password <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" class="form-control" name="new_password" id="new_password"
                                        required minlength="6">
                                    <div class="progress mt-2" style="height: 5px;">
                                        <div class="progress-bar" id="password-strength" role="progressbar"
                                            style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-check-double me-1"></i>Confirm New Password <span
                                            class="text-danger">*</span>
                                    </label>
                                    <input type="password" class="form-control" name="confirm_password"
                                        id="confirm_password" required minlength="6">
                                    <small id="password-match" class="text-muted"></small>
                                </div>

                                <div class="alert alert-info">
                                    <strong><i class="fas fa-info-circle me-2"></i>Password Requirements:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>At least 6 characters long</li>
                                        <li>Use a mix of letters and numbers</li>
                                        <li>Avoid common words or patterns</li>
                                    </ul>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-warning btn-lg">
                                        <i class="fas fa-save me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Password strength indicator
    document.getElementById('new_password').addEventListener('input', function () {
        const password = this.value;
        const strengthBar = document.getElementById('password-strength');
        let strength = 0;

        if (password.length >= 6) strength += 25;
        if (password.length >= 10) strength += 25;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 25;

        strengthBar.style.width = strength + '%';

        if (strength < 50) {
            strengthBar.className = 'progress-bar bg-danger';
        } else if (strength < 75) {
            strengthBar.className = 'progress-bar bg-warning';
        } else {
            strengthBar.className = 'progress-bar bg-success';
        }
    });

    // Password match indicator
    document.getElementById('confirm_password').addEventListener('input', function () {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;
        const matchIndicator = document.getElementById('password-match');

        if (confirmPassword.length > 0) {
            if (newPassword === confirmPassword) {
                matchIndicator.textContent = '✓ Passwords match';
                matchIndicator.className = 'text-success';
            } else {
                matchIndicator.textContent = '✗ Passwords do not match';
                matchIndicator.className = 'text-danger';
            }
        } else {
            matchIndicator.textContent = '';
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>