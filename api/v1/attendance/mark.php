<?php
/**
 * Attendance API - Mark Attendance
 * 
 * POST /api/v1/attendance/mark.php
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../app/Services/AttendanceService.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireAuth();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['student_id']) || !isset($input['bus_id']) || !isset($input['route_id']) || !isset($input['type'])) {
    sendJsonResponse(false, null, 'Student ID, Bus ID, Route ID, and type are required', 400);
}

$studentId = intval($input['student_id']);
$busId = intval($input['bus_id']);
$routeId = intval($input['route_id']);
$type = sanitizeInput($input['type']); // 'pickup' or 'dropoff'
$status = sanitizeInput($input['status'] ?? 'present');
$checkedBy = isset($input['checked_by']) ? intval($input['checked_by']) : getUserId();
$latitude = isset($input['latitude']) ? floatval($input['latitude']) : null;
$longitude = isset($input['longitude']) ? floatval($input['longitude']) : null;

// Validate type
if (!in_array($type, ['pickup', 'dropoff'])) {
    sendJsonResponse(false, null, 'Type must be pickup or dropoff', 400);
}

// Validate status
if (!in_array($status, ['present', 'absent', 'late', 'excused'])) {
    sendJsonResponse(false, null, 'Invalid status', 400);
}

try {
    $attendanceService = new AttendanceService();
    $result = $attendanceService->markAttendance(
        $studentId, 
        $busId, 
        $routeId, 
        $type, 
        $status, 
        $checkedBy, 
        $latitude, 
        $longitude
    );
    
    if ($result['success']) {
        sendJsonResponse(true, $result, $result['message']);
    } else {
        sendJsonResponse(false, null, $result['message'], 500);
    }
} catch (Exception $e) {
    error_log("Mark attendance error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error recording attendance', 500);
}
?>

