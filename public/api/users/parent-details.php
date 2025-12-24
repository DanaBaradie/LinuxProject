<?php
header('Content-Type: application/json');
require_once '../../../config/config.php';
require_once '../../../config/database.php';

requireLogin();
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$userId = intval($_GET['id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    // Get parent information
    $query = "SELECT * FROM users WHERE id = :id AND role = 'parent'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Parent not found']);
        exit;
    }

    // Get students
    $query = "SELECT s.*, rs.stop_name 
              FROM students s
              LEFT JOIN route_stops rs ON s.assigned_stop_id = rs.id
              WHERE s.parent_id = :parent_id
              ORDER BY s.student_name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':parent_id', $userId);
    $stmt->execute();
    $students = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone']
        ],
        'students' => array_map(function ($student) {
            return [
                'id' => $student['id'],
                'student_name' => $student['student_name'],
                'grade' => $student['grade'],
                'stop_name' => $student['stop_name']
            ];
        }, $students)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>