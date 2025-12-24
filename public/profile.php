<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';
$user_id = $_SESSION['user_id'];

// Get current user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid session token. Please refresh the page and try again.';
    } else {

        try {
            // Check if email is already taken by another user
            $query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':email' => $email, ':user_id' => $user_id]);

            if ($stmt->rowCount() > 0) {
                $error = 'Email address is already in use by another account.';
            } else {
                $query = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':id' => $user_id
                ]);

                // Update session
                $_SESSION['user_name'] = $full_name;

                $success = 'Profile updated successfully!';

                // Refresh user data
                $query = "SELECT * FROM users WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $user = $stmt->fetch();
            }
        } catch (Exception $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
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
                <h1 class="h2"><i class="fas fa-user-circle me-2"></i>My Profile</h1>
                <a href="/change-password.php" class="btn btn-outline-primary">
                    <i class="fas fa-key me-2"></i>Change Password
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

            <div class="row">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php csrfField(); ?>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user me-1"></i>Full Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="full_name"
                                        value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-envelope me-1"></i>Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" name="email"
                                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-phone me-1"></i>Phone Number
                                        </label>
                                        <input type="text" class="form-control" name="phone"
                                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                            placeholder="e.g., 961-3-123456">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user-tag me-1"></i>Role
                                    </label>
                                    <input type="text" class="form-control"
                                        value="<?php echo ucfirst($user['role']); ?>" readonly disabled>
                                    <small class="text-muted">Role cannot be changed</small>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Account Created:</strong><br>
                                <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>

                            <p><strong>Last Updated:</strong><br>
                                <?php echo date('F d, Y', strtotime($user['updated_at'])); ?></p>

                            <p><strong>User ID:</strong><br>
                                #<?php echo $user['id']; ?></p>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">Keep your account secure:</p>
                            <ul class="small">
                                <li>Use a strong password</li>
                                <li>Change password regularly</li>
                                <li>Don't share your credentials</li>
                                <li>Log out on shared devices</li>
                            </ul>
                            <a href="/change-password.php" class="btn btn-warning btn-sm w-100">
                                <i class="fas fa-key me-2"></i>Change Password
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>