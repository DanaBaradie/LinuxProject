<?php
/**
 * Dashboard Export API
 * 
 * Exports dashboard data to CSV format
 * 
 * @author Dana Baradie
 * @course IT404
 */

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="dashboard-export-' . date('Y-m-d') . '.csv"');

require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$date_filter = $_GET['date'] ?? 'today';
$selected_date = $_GET['selected_date'] ?? date('Y-m-d');
$start_date = $_GET['start_date'] ?? $selected_date;
$end_date = $_GET['end_date'] ?? $selected_date;

// Calculate date range
switch ($date_filter) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'week':
        $day_of_week = date('w', strtotime($selected_date));
        $days_to_monday = ($day_of_week == 0) ? 6 : $day_of_week - 1;
        $start_date = date('Y-m-d', strtotime($selected_date . ' -' . $days_to_monday . ' days'));
        $end_date = date('Y-m-d', strtotime($start_date . ' +6 days'));
        break;
    case 'month':
        $start_date = date('Y-m-01', strtotime($selected_date));
        $end_date = date('Y-m-t', strtotime($selected_date));
        break;
}

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
fputcsv($output, ['Dashboard Export Report']);
fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
fputcsv($output, ['Date Range: ' . $start_date . ' to ' . $end_date]);
fputcsv($output, []); // Empty row

try {
    // Export Statistics
    fputcsv($output, ['STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    
    $query = "SELECT COUNT(*) as count FROM buses WHERE status = 'active'";
    $stats['active_buses'] = $db->query($query)->fetch()['count'];
    fputcsv($output, ['Active Buses', $stats['active_buses']]);
    
    $query = "SELECT COUNT(*) as count FROM users WHERE role = 'driver'";
    $stats['total_drivers'] = $db->query($query)->fetch()['count'];
    fputcsv($output, ['Total Drivers', $stats['total_drivers']]);
    
    $query = "SELECT COUNT(*) as count FROM users WHERE role = 'parent'";
    $stats['total_parents'] = $db->query($query)->fetch()['count'];
    fputcsv($output, ['Total Parents', $stats['total_parents']]);
    
    $query = "SELECT COUNT(*) as count FROM routes WHERE active = 1";
    $stats['active_routes'] = $db->query($query)->fetch()['count'];
    fputcsv($output, ['Active Routes', $stats['active_routes']]);
    
    $query = "SELECT COUNT(*) as count FROM students";
    $stats['total_students'] = $db->query($query)->fetch()['count'];
    fputcsv($output, ['Total Students', $stats['total_students']]);
    
    $query = "SELECT COUNT(*) as count FROM buses 
              WHERE status = 'active' 
                AND current_latitude IS NOT NULL 
                AND current_longitude IS NOT NULL";
    $stats['tracking_buses'] = $db->query($query)->fetch()['count'];
    fputcsv($output, ['Buses with GPS Tracking', $stats['tracking_buses']]);
    
    fputcsv($output, []); // Empty row
    
    // Export Recent Buses
    fputcsv($output, ['RECENT BUSES']);
    fputcsv($output, ['Bus Number', 'Driver', 'Status', 'Last Update', 'GPS Status']);
    
    $query = "SELECT b.*, u.full_name as driver_name 
              FROM buses b 
              LEFT JOIN users u ON b.driver_id = u.id 
              WHERE DATE(b.updated_at) BETWEEN :start_date AND :end_date
              ORDER BY b.updated_at DESC LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ]);
    $buses = $stmt->fetchAll();
    
    foreach ($buses as $bus) {
        $gps_status = ($bus['current_latitude'] && $bus['current_longitude']) ? 'Active' : 'Offline';
        fputcsv($output, [
            $bus['bus_number'],
            $bus['driver_name'] ?? 'Unassigned',
            ucfirst($bus['status']),
            $bus['last_location_update'] ?? 'Never',
            $gps_status
        ]);
    }
    
    fputcsv($output, []); // Empty row
    
    // Export GPS Logs Summary
    fputcsv($output, ['GPS LOGS SUMMARY']);
    fputcsv($output, ['Date', 'Total Updates', 'Unique Buses']);
    
    $query = "SELECT DATE(timestamp) as log_date, 
              COUNT(*) as total_updates, 
              COUNT(DISTINCT bus_id) as unique_buses
              FROM gps_logs 
              WHERE DATE(timestamp) BETWEEN :start_date AND :end_date
              GROUP BY DATE(timestamp)
              ORDER BY log_date DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ]);
    $gps_logs = $stmt->fetchAll();
    
    foreach ($gps_logs as $log) {
        fputcsv($output, [
            $log['log_date'],
            $log['total_updates'],
            $log['unique_buses']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    fputcsv($output, ['Error: ' . $e->getMessage()]);
}

fclose($output);
exit;
?>

