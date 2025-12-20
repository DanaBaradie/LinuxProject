<?php
/**
 * Report Service
 * 
 * Generates various reports for schools
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/database.php';

class ReportService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Generate attendance report
     * 
     * @param int $schoolId School ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param array $filters Additional filters
     * @return array Report data
     */
    public function generateAttendanceReport($schoolId, $startDate, $endDate, $filters = []) {
        try {
            $query = "SELECT 
                        s.student_name,
                        s.grade,
                        s.section,
                        b.bus_number,
                        r.route_name,
                        a.attendance_date,
                        a.attendance_type,
                        a.status,
                        a.check_in_time,
                        COUNT(*) as total_days,
                        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
                        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days
                      FROM attendance a
                      INNER JOIN students s ON a.student_id = s.id
                      INNER JOIN buses b ON a.bus_id = b.id
                      INNER JOIN routes r ON a.route_id = r.id
                      WHERE s.school_id = :school_id
                        AND a.attendance_date BETWEEN :start_date AND :end_date";
            
            if (isset($filters['student_id'])) {
                $query .= " AND s.id = :student_id";
            }
            if (isset($filters['bus_id'])) {
                $query .= " AND b.id = :bus_id";
            }
            if (isset($filters['route_id'])) {
                $query .= " AND r.id = :route_id";
            }
            
            $query .= " GROUP BY s.id, a.attendance_type
                       ORDER BY s.student_name, a.attendance_date";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            
            if (isset($filters['student_id'])) {
                $stmt->bindParam(':student_id', $filters['student_id']);
            }
            if (isset($filters['bus_id'])) {
                $stmt->bindParam(':bus_id', $filters['bus_id']);
            }
            if (isset($filters['route_id'])) {
                $stmt->bindParam(':route_id', $filters['route_id']);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Report generation error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate bus utilization report
     * 
     * @param int $schoolId School ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Report data
     */
    public function generateBusUtilizationReport($schoolId, $startDate, $endDate) {
        try {
            $query = "SELECT 
                        b.bus_number,
                        b.capacity,
                        COUNT(DISTINCT s.id) as students_assigned,
                        COUNT(DISTINCT a.student_id) as students_transported,
                        COUNT(a.id) as total_trips,
                        ROUND(COUNT(DISTINCT a.student_id) / b.capacity * 100, 2) as utilization_percent,
                        AVG(a.speed) as avg_speed
                      FROM buses b
                      LEFT JOIN students s ON b.id = s.assigned_bus_id
                      LEFT JOIN attendance a ON b.id = a.bus_id 
                        AND a.attendance_date BETWEEN :start_date AND :end_date
                      LEFT JOIN gps_logs g ON b.id = g.bus_id 
                        AND DATE(g.timestamp) BETWEEN :start_date AND :end_date
                      WHERE b.school_id = :school_id
                      GROUP BY b.id
                      ORDER BY utilization_percent DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Bus utilization report error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate route performance report
     * 
     * @param int $schoolId School ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Report data
     */
    public function generateRoutePerformanceReport($schoolId, $startDate, $endDate) {
        try {
            $query = "SELECT 
                        r.route_name,
                        r.route_code,
                        COUNT(DISTINCT rs.id) as total_stops,
                        COUNT(DISTINCT s.id) as students_on_route,
                        COUNT(DISTINCT a.id) as total_attendance_records,
                        AVG(TIMESTAMPDIFF(MINUTE, 
                            CONCAT(a.attendance_date, ' ', rs.estimated_arrival_time),
                            CONCAT(a.attendance_date, ' ', a.check_in_time))) as avg_delay_minutes
                      FROM routes r
                      LEFT JOIN route_stops rs ON r.id = rs.route_id
                      LEFT JOIN students s ON r.id = s.assigned_route_id
                      LEFT JOIN attendance a ON r.id = a.route_id 
                        AND a.attendance_date BETWEEN :start_date AND :end_date
                      WHERE r.school_id = :school_id
                      GROUP BY r.id
                      ORDER BY r.route_name";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Route performance report error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate maintenance report
     * 
     * @param int $schoolId School ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Report data
     */
    public function generateMaintenanceReport($schoolId, $startDate, $endDate) {
        try {
            $query = "SELECT 
                        b.bus_number,
                        m.maintenance_type,
                        m.title,
                        m.maintenance_date,
                        m.cost,
                        m.status,
                        m.next_maintenance_date,
                        DATEDIFF(m.next_maintenance_date, CURDATE()) as days_until_next
                      FROM bus_maintenance m
                      INNER JOIN buses b ON m.bus_id = b.id
                      WHERE b.school_id = :school_id
                        AND m.maintenance_date BETWEEN :start_date AND :end_date
                      ORDER BY m.maintenance_date DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Maintenance report error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate dashboard statistics
     * 
     * @param int $schoolId School ID
     * @return array Statistics
     */
    public function getDashboardStats($schoolId) {
        try {
            $stats = [];
            
            // Total buses
            $query = "SELECT COUNT(*) as count FROM buses WHERE school_id = :school_id AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->execute();
            $stats['active_buses'] = $stmt->fetch()['count'];
            
            // Total students
            $query = "SELECT COUNT(*) as count FROM students WHERE school_id = :school_id AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->execute();
            $stats['active_students'] = $stmt->fetch()['count'];
            
            // Total drivers
            $query = "SELECT COUNT(*) as count FROM users WHERE school_id = :school_id AND role = 'driver' AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->execute();
            $stats['active_drivers'] = $stmt->fetch()['count'];
            
            // Today's attendance
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                      FROM attendance a
                      INNER JOIN students s ON a.student_id = s.id
                      WHERE s.school_id = :school_id
                        AND a.attendance_date = CURDATE()";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->execute();
            $attendance = $stmt->fetch();
            $stats['today_attendance'] = $attendance;
            $stats['attendance_rate'] = $attendance['total'] > 0 
                ? round(($attendance['present'] / $attendance['total']) * 100, 2) 
                : 0;
            
            // Pending maintenance
            $query = "SELECT COUNT(*) as count 
                      FROM bus_maintenance m
                      INNER JOIN buses b ON m.bus_id = b.id
                      WHERE b.school_id = :school_id
                        AND m.status = 'scheduled'
                        AND m.maintenance_date <= CURDATE()";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->execute();
            $stats['pending_maintenance'] = $stmt->fetch()['count'];
            
            // Unread notifications
            $query = "SELECT COUNT(*) as count 
                      FROM notifications 
                      WHERE school_id = :school_id 
                        AND is_read = FALSE";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->execute();
            $stats['unread_notifications'] = $stmt->fetch()['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            return [];
        }
    }
}
?>

