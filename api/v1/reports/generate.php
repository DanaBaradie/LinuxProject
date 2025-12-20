<?php
/**
 * Reports API - Generate Report
 * 
 * POST /api/v1/reports/generate.php
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../app/Services/ReportService.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}

requireRoleMiddleware(['admin', 'super_admin']);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['report_type']) || !isset($input['start_date']) || !isset($input['end_date'])) {
    sendJsonResponse(false, null, 'Report type, start date, and end date are required', 400);
}

$reportType = sanitizeInput($input['report_type']);
$startDate = sanitizeInput($input['start_date']);
$endDate = sanitizeInput($input['end_date']);
$filters = $input['filters'] ?? [];
$schoolId = $_SESSION['school_id'] ?? null;

if (!$schoolId) {
    sendJsonResponse(false, null, 'School ID not found', 400);
}

try {
    $reportService = new ReportService();
    $data = [];
    
    switch ($reportType) {
        case 'attendance':
            $data = $reportService->generateAttendanceReport($schoolId, $startDate, $endDate, $filters);
            break;
        case 'bus_utilization':
            $data = $reportService->generateBusUtilizationReport($schoolId, $startDate, $endDate);
            break;
        case 'route_performance':
            $data = $reportService->generateRoutePerformanceReport($schoolId, $startDate, $endDate);
            break;
        case 'maintenance':
            $data = $reportService->generateMaintenanceReport($schoolId, $startDate, $endDate);
            break;
        default:
            sendJsonResponse(false, null, 'Invalid report type', 400);
    }
    
    // Save report to database
    $database = new Database();
    $db = $database->getConnection();
    
    $insertQuery = "INSERT INTO reports 
                   (school_id, report_name, report_type, date_range_start, date_range_end, 
                    filters, status, generated_by) 
                   VALUES (:school_id, :name, :type, :start, :end, :filters, 'completed', :user_id)";
    $insertStmt = $db->prepare($insertQuery);
    $reportName = ucfirst($reportType) . " Report - " . date('Y-m-d');
    $filtersJson = json_encode($filters);
    $insertStmt->bindParam(':school_id', $schoolId);
    $insertStmt->bindParam(':name', $reportName);
    $insertStmt->bindParam(':type', $reportType);
    $insertStmt->bindParam(':start', $startDate);
    $insertStmt->bindParam(':end', $endDate);
    $insertStmt->bindParam(':filters', $filtersJson);
    $insertStmt->bindParam(':user_id', $_SESSION['user_id']);
    $insertStmt->execute();
    
    sendJsonResponse(true, [
        'report_id' => $db->lastInsertId(),
        'report_type' => $reportType,
        'data' => $data,
        'summary' => [
            'total_records' => count($data),
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]
    ], 'Report generated successfully');
    
} catch (Exception $e) {
    error_log("Generate report error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error generating report', 500);
}
?>

