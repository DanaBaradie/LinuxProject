<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $email = sanitizeInput($_POST['email']);
        $full_name = sanitizeInput($_POST['full_name']);
        $phone = sanitizeInput($_POST['phone']);
        $role = sanitizeInput($_POST['role']);
        $plainPassword = $_POST['password']; // Store before hashing for email
        $password = password_hash($plainPassword, PASSWORD_DEFAULT);

        try {
            $query = "INSERT INTO users (email, password, full_name, phone, role) 
                      VALUES (:email, :password, :full_name, :phone, :role)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':role', $role);
            $stmt->execute();

            // Send welcome email
            try {
                require_once '../includes/MailgunService.php';
                $mailgun = new MailgunService();
                $mailgun->sendWelcomeEmail($email, $full_name, $plainPassword);
            } catch (Exception $emailError) {
                error_log("Failed to send welcome email: " . $emailError->getMessage());
            }

            $success = 'User added successfully! Welcome email sent.';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($action === 'edit') {
        $user_id = intval($_POST['user_id']);
        $email = sanitizeInput($_POST['email']);
        $full_name = sanitizeInput($_POST['full_name']);
        $phone = sanitizeInput($_POST['phone']);

        try {
            // Update password only if provided
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $query = "UPDATE users SET email = :email, password = :password, full_name = :full_name, phone = :phone WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $password);
            } else {
                $query = "UPDATE users SET email = :email, full_name = :full_name, phone = :phone WHERE id = :id";
                $stmt = $db->prepare($query);
            }

            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();

            $success = 'User updated successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $user_id = intval($_POST['user_id']);

        // Prevent deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            $error = 'You cannot delete your own account!';
        } else {
            try {
                $query = "DELETE FROM users WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();

                $success = 'User deleted successfully!';
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Get all users with additional info
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM students WHERE parent_id = u.id) as student_count,
          b.bus_number
          FROM users u
          LEFT JOIN buses b ON u.id = b.driver_id
          ORDER BY u.role, u.full_name";
$users = $db->query($query)->fetchAll();

// Calculate statistics
$stats = [
    'total_admins' => 0,
    'total_drivers' => 0,
    'total_parents' => 0,
    'assigned_drivers' => 0,
    'parents_with_students' => 0
];

foreach ($users as $user) {
    if ($user['role'] === 'admin')
        $stats['total_admins']++;
    if ($user['role'] === 'driver') {
        $stats['total_drivers']++;
        if ($user['bus_number'])
            $stats['assigned_drivers']++;
    }
    if ($user['role'] === 'parent') {
        $stats['total_parents']++;
        if ($user['student_count'] > 0)
            $stats['parents_with_students']++;
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
                <h1 class="h2"><i class="fas fa-users me-2"></i>Manage Users</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus me-2"></i>Add New User
                </button>
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

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm" style="border-left: 4px solid #0d6efd !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Administrators</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_admins']; ?></h3>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-user-shield fa-2x"></i>
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
                                    <h6 class="text-muted mb-1">Drivers</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_drivers']; ?></h3>
                                    <small class="text-success"><?php echo $stats['assigned_drivers']; ?>
                                        assigned</small>
                                </div>
                                <div class="text-success">
                                    <i class="fas fa-user-tie fa-2x"></i>
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
                                    <h6 class="text-muted mb-1">Parents</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_parents']; ?></h3>
                                    <small class="text-info"><?php echo $stats['parents_with_students']; ?> with
                                        students</small>
                                </div>
                                <div class="text-info">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm" style="border-left: 4px solid #6c757d !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Users</h6>
                                    <h3 class="mb-0"><?php echo count($users); ?></h3>
                                </div>
                                <div class="text-secondary">
                                    <i class="fas fa-users-cog fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Box -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" id="searchUsers" class="form-control"
                                placeholder="🔍 Search by name or email...">
                        </div>
                        <div class="col-md-3">
                            <select id="filterRole" class="form-select">
                                <option value="">All Roles</option>
                                <option value="admin">Administrators</option>
                                <option value="driver">Drivers</option>
                                <option value="parent">Parents</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admins -->
            <div class="card border-0 shadow-sm mb-4 user-section" data-role="admin">
                <div class="card-header"
                    style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white;">
                    <h5 class="mb-0" style="color: white !important;"><i
                            class="fas fa-user-shield me-2"></i>Administrators</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $admins = array_filter($users, fn($u) => $u['role'] === 'admin');
                                foreach ($admins as $user):
                                    ?>
                                    <tr class="user-row" data-name="<?php echo strtolower($user['full_name']); ?>"
                                        data-email="<?php echo strtolower($user['email']); ?>">
                                        <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-info me-1"
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline"
                                                    onsubmit="return confirm('Delete this user?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-info">You</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Drivers -->
            <div class="card border-0 shadow-sm mb-4 user-section" data-role="driver">
                <div class="card-header"
                    style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white;">
                    <h5 class="mb-0" style="color: white !important;"><i class="fas fa-user-tie me-2"></i>Drivers</h5>
                </div>
                <div class="card-body">
                    <?php
                    $drivers = array_filter($users, fn($u) => $u['role'] === 'driver');
                    if (empty($drivers)):
                        ?>
                        <p class="text-muted text-center py-3">No drivers added yet</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th class="d-none d-md-table-cell">Email</th>
                                        <th class="d-none d-md-table-cell">Phone</th>
                                        <th>Assigned Bus</th>
                                        <th class="d-none d-md-table-cell">Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drivers as $user): ?>
                                        <tr class="user-row" data-name="<?php echo strtolower($user['full_name']); ?>"
                                            data-email="<?php echo strtolower($user['email']); ?>">
                                            <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                            <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($user['bus_number']): ?>
                                                    <span class="badge bg-success">
                                                        <i
                                                            class="fas fa-bus me-1"></i><?php echo htmlspecialchars($user['bus_number']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <?php if ($user['bus_number']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex">
                                                    <button class="btn btn-sm btn-primary me-1"
                                                        onclick="viewDriverDetails(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-success me-1 d-none d-md-inline-block"
                                                        onclick="sendEmailToUser('<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-info me-1"
                                                        onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline"
                                                        onsubmit="return confirm('Delete this user?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Parents -->
            <div class="card border-0 shadow-sm mb-4 user-section" data-role="parent">
                <div class="card-header"
                    style="background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%); color: white;">
                    <h5 class="mb-0" style="color: white !important;"><i class="fas fa-users me-2"></i>Parents</h5>
                </div>
                <div class="card-body">
                    <?php
                    $parents = array_filter($users, fn($u) => $u['role'] === 'parent');
                    if (empty($parents)):
                        ?>
                        <p class="text-muted text-center py-3">No parents added yet</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th class="d-none d-md-table-cell">Email</th>
                                        <th class="d-none d-md-table-cell">Phone</th>
                                        <th>Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parents as $user): ?>
                                        <tr class="user-row" data-name="<?php echo strtolower($user['full_name']); ?>"
                                            data-email="<?php echo strtolower($user['email']); ?>">
                                            <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                            <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($user['student_count'] > 0): ?>
                                                    <span class="badge bg-info">
                                                        <i
                                                            class="fas fa-user-graduate me-1"></i><?php echo $user['student_count']; ?>
                                                        student<?php echo $user['student_count'] > 1 ? 's' : ''; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">No students</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex">
                                                    <button class="btn btn-sm btn-primary me-1"
                                                        onclick="viewParentDetails(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-success me-1 d-none d-md-inline-block"
                                                        onclick="sendEmailToUser('<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-info me-1"
                                                        onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline"
                                                        onsubmit="return confirm('Delete this user?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>Full Name <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email <span class="required">*</span>
                            </label>
                            <input type="email" class="form-control" name="email" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-phone me-1"></i>Phone
                            </label>
                            <input type="text" class="form-control" name="phone" placeholder="e.g., 961-3-123456">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-lock me-1"></i>Password <span class="required">*</span>
                            </label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                            <small class="form-text">Minimum 6 characters</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-tag me-1"></i>Role <span class="required">*</span>
                            </label>
                            <select class="form-select" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="driver">Driver</option>
                                <option value="parent">Parent</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>Full Name <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email <span class="required">*</span>
                            </label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-phone me-1"></i>Phone
                            </label>
                            <input type="text" class="form-control" name="phone" id="edit_phone"
                                placeholder="e.g., 961-3-123456">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock me-1"></i>New Password
                        </label>
                        <input type="password" class="form-control" name="password" id="edit_password" minlength="6">
                        <small class="form-text">Leave blank to keep current password</small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>User role cannot be changed for security reasons.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Driver Details Modal -->
<div class="modal fade" id="viewDriverModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-tie me-2"></i>Driver Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="driverDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- View Parent Details Modal -->
<div class="modal fade" id="viewParentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-users me-2"></i>Parent Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="parentDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Send Email Modal -->
<div class="modal fade" id="sendEmailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="emailForm" onsubmit="submitEmailForm(event)">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Send Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="type" value="custom">
                    <input type="hidden" id="email_to_name" name="to_name">

                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>To <span class="required">*</span>
                        </label>
                        <input type="email" class="form-control" id="email_to" name="to" required readonly>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-heading me-1"></i>Subject <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" id="email_subject" name="subject" required
                            placeholder="Enter email subject">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-comment me-1"></i>Message <span class="required">*</span>
                        </label>
                        <textarea class="form-control" id="email_message" name="message" rows="8" required
                            placeholder="Enter your message here..."></textarea>
                        <small class="form-text">You can use HTML formatting in your message</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="sendEmailBtn">
                        <i class="fas fa-paper-plane me-2"></i>Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Edit user function
    function editUser(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_full_name').value = user.full_name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_phone').value = user.phone || '';
        document.getElementById('edit_password').value = '';

        var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        editModal.show();
    }

    // View driver details
    function viewDriverDetails(userId) {
        var modal = new bootstrap.Modal(document.getElementById('viewDriverModal'));
        modal.show();

        fetch('/api/users/driver-details.php?id=' + userId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user me-2"></i>Personal Information</h6>
                            <table class="table table-sm">
                                <tr><th>Name:</th><td>${data.user.full_name}</td></tr>
                                <tr><th>Email:</th><td>${data.user.email}</td></tr>
                                <tr><th>Phone:</th><td>${data.user.phone || 'N/A'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-bus me-2"></i>Bus Assignment</h6>
                            ${data.bus ? `
                                <table class="table table-sm">
                                    <tr><th>Bus Number:</th><td>${data.bus.bus_number}</td></tr>
                                    <tr><th>License Plate:</th><td>${data.bus.license_plate || 'N/A'}</td></tr>
                                    <tr><th>Capacity:</th><td>${data.bus.capacity} students</td></tr>
                                    <tr><th>Status:</th><td><span class="badge bg-${data.bus.status === 'active' ? 'success' : 'warning'}">${data.bus.status}</span></td></tr>
                                </table>
                            ` : '<p class="text-muted">No bus assigned</p>'}
                        </div>
                    </div>
                    ${data.routes && data.routes.length > 0 ? `
                        <hr>
                        <h6><i class="fas fa-route me-2"></i>Assigned Routes</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Route Name</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.routes.map(route => `
                                        <tr>
                                            <td>${route.route_name}</td>
                                            <td>${route.start_time}</td>
                                            <td>${route.end_time}</td>
                                            <td><span class="badge bg-${route.active ? 'success' : 'secondary'}">${route.active ? 'Active' : 'Inactive'}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    ` : ''}
                `;
                    document.getElementById('driverDetailsContent').innerHTML = html;
                } else {
                    document.getElementById('driverDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading driver details</div>';
                }
            })
            .catch(error => {
                document.getElementById('driverDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading driver details</div>';
            });
    }

    // View parent details
    function viewParentDetails(userId) {
        var modal = new bootstrap.Modal(document.getElementById('viewParentModal'));
        modal.show();

        fetch('/api/users/parent-details.php?id=' + userId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = `
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h6><i class="fas fa-user me-2"></i>Personal Information</h6>
                            <table class="table table-sm">
                                <tr><th>Name:</th><td>${data.user.full_name}</td></tr>
                                <tr><th>Email:</th><td>${data.user.email}</td></tr>
                                <tr><th>Phone:</th><td>${data.user.phone || 'N/A'}</td></tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <h6><i class="fas fa-user-graduate me-2"></i>Students (${data.students.length})</h6>
                    ${data.students.length > 0 ? `
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Grade</th>
                                        <th>Assigned Stop</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.students.map(student => `
                                        <tr>
                                            <td><i class="fas fa-user-graduate text-primary me-2"></i>${student.student_name}</td>
                                            <td>${student.grade || 'N/A'}</td>
                                            <td>${student.stop_name ? `<i class="fas fa-map-marker-alt text-danger me-1"></i>${student.stop_name}` : '<span class="text-muted">Not assigned</span>'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    ` : '<p class="text-muted">No students registered</p>'}
                `;
                    document.getElementById('parentDetailsContent').innerHTML = html;
                } else {
                    document.getElementById('parentDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading parent details</div>';
                }
            })
            .catch(error => {
                document.getElementById('parentDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading parent details</div>';
            });
    }

    // Search and filter functionality
    document.getElementById('searchUsers').addEventListener('keyup', filterUsers);
    document.getElementById('filterRole').addEventListener('change', filterUsers);

    function filterUsers() {
        const searchTerm = document.getElementById('searchUsers').value.toLowerCase();
        const roleFilter = document.getElementById('filterRole').value;

        // Hide/show sections based on role filter
        document.querySelectorAll('.user-section').forEach(section => {
            if (roleFilter === '' || section.dataset.role === roleFilter) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });

        // Filter rows within visible sections
        document.querySelectorAll('.user-row').forEach(row => {
            const name = row.dataset.name;
            const email = row.dataset.email;
            const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);

            if (matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Send email to user
    function sendEmailToUser(userEmail, userName) {
        document.getElementById('email_to').value = userEmail;
        document.getElementById('email_to_name').value = userName;
        document.getElementById('email_subject').value = '';
        document.getElementById('email_message').value = '';

        var modal = new bootstrap.Modal(document.getElementById('sendEmailModal'));
        modal.show();
    }

    // Handle email form submission
    function submitEmailForm(event) {
        event.preventDefault();

        const submitBtn = document.getElementById('sendEmailBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';

        const formData = new FormData(document.getElementById('emailForm'));

        fetch('/api/email/send.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Email sent successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('sendEmailModal')).hide();
                    document.getElementById('emailForm').reset();
                } else {
                    alert('❌ Failed to send email: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('❌ Error sending email: ' + error.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
    }
</script>

<?php require_once '../includes/footer.php'; ?>