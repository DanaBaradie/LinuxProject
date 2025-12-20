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
        $student_name = sanitizeInput($_POST['student_name']);
        $parent_id = intval($_POST['parent_id']);
        $grade = sanitizeInput($_POST['grade']);
        $assigned_stop_id = !empty($_POST['assigned_stop_id']) ? intval($_POST['assigned_stop_id']) : null;
        
        try {
            $query = "INSERT INTO students (student_name, parent_id, grade, assigned_stop_id) 
                      VALUES (:student_name, :parent_id, :grade, :assigned_stop_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_name', $student_name);
            $stmt->bindParam(':parent_id', $parent_id);
            $stmt->bindParam(':grade', $grade);
            $stmt->bindParam(':assigned_stop_id', $assigned_stop_id);
            $stmt->execute();
            
            $success = 'Student added successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $student_id = intval($_POST['student_id']);
        
        try {
            $query = "DELETE FROM students WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $student_id);
            $stmt->execute();
            
            $success = 'Student deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get all students with parent info
$query = "SELECT s.*, u.full_name as parent_name, rs.stop_name 
          FROM students s 
          LEFT JOIN users u ON s.parent_id = u.id 
          LEFT JOIN route_stops rs ON s.assigned_stop_id = rs.id 
          ORDER BY s.student_name";
$students = $db->query($query)->fetchAll();

// Get all parents
$query = "SELECT id, full_name FROM users WHERE role = 'parent'";
$parents = $db->query($query)->fetchAll();

// Get all route stops
$query = "SELECT rs.*, r.route_name 
          FROM route_stops rs 
          JOIN routes r ON rs.route_id = r.id 
          ORDER BY r.route_name, rs.stop_order";
$stops = $db->query($query)->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>

        <main class="main-content-area">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-user-graduate me-2"></i>Manage Students</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-2"></i>Add New Student
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

            <?php if (empty($parents)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No parents in the system. Please <a href="/users.php">add parents</a> first before adding students.
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($students)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-user-graduate fa-4x text-muted mb-3"></i>
                            <h4>No Students Added Yet</h4>
                            <p class="text-muted">Click "Add New Student" to get started</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Grade</th>
                                        <th>Parent</th>
                                        <th>Assigned Stop</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-user-graduate text-primary me-2"></i>
                                                <strong><?php echo htmlspecialchars($student['student_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['grade'] ?? 'N/A'); ?></td>
                                            <td>
                                                <i class="fas fa-user text-info me-1"></i>
                                                <?php echo htmlspecialchars($student['parent_name']); ?>
                                            </td>
                                            <td>
                                                <?php if ($student['stop_name']): ?>
                                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                                    <?php echo htmlspecialchars($student['stop_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this student?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-graduate me-2"></i>Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-graduate me-1"></i>Student Name <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" name="student_name" required 
                               placeholder="e.g., Alex Johnson">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-graduation-cap me-1"></i>Grade
                            </label>
                            <input type="text" class="form-control" name="grade" 
                                   placeholder="e.g., Grade 5">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user me-1"></i>Parent <span class="required">*</span>
                            </label>
                            <select class="form-select" name="parent_id" required>
                                <option value="">-- Select Parent --</option>
                                <?php foreach ($parents as $parent): ?>
                                    <option value="<?php echo $parent['id']; ?>">
                                        <?php echo htmlspecialchars($parent['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i>Assigned Stop (Optional)
                        </label>
                        <select class="form-select" name="assigned_stop_id">
                            <option value="">-- No Stop Yet --</option>
                            <?php foreach ($stops as $stop): ?>
                                <option value="<?php echo $stop['id']; ?>">
                                    <?php echo htmlspecialchars($stop['route_name'] . ' - ' . $stop['stop_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">You need to add route stops first</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
