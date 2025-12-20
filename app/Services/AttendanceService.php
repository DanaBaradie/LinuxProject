<?php
/**
 * Attendance Service
 * 
 * Handles student attendance tracking for bus pickups and dropoffs
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/database.php';

class AttendanceService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Mark student attendance
     * 
     * @param int $studentId Student ID
     * @param int $busId Bus ID
     * @param int $routeId Route ID
     * @param string $type 'pickup' or 'dropoff'
     * @param string $status 'present', 'absent', 'late', 'excused'
     * @param int $checkedBy User ID who checked
     * @param float|null $latitude GPS latitude
     * @param float|null $longitude GPS longitude
     * @return array Result
     */
    public function markAttendance($studentId, $busId, $routeId, $type, $status = 'present', $checkedBy = null, $latitude = null, $longitude = null) {
        try {
            $date = date('Y-m-d');
            $time = date('H:i:s');
            
            // Check if attendance already exists
            $checkQuery = "SELECT id FROM attendance 
                          WHERE student_id = :student_id 
                            AND bus_id = :bus_id 
                            AND attendance_date = :date 
                            AND attendance_type = :type";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':student_id', $studentId);
            $checkStmt->bindParam(':bus_id', $busId);
            $checkStmt->bindParam(':date', $date);
            $checkStmt->bindParam(':type', $type);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // Update existing
                $updateQuery = "UPDATE attendance 
                               SET status = :status, 
                                   check_in_time = :time,
                                   check_in_latitude = :lat,
                                   check_in_longitude = :lng,
                                   checked_by = :checked_by
                               WHERE student_id = :student_id 
                                 AND bus_id = :bus_id 
                                 AND attendance_date = :date 
                                 AND attendance_type = :type";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindParam(':status', $status);
                $updateStmt->bindParam(':time', $time);
                $updateStmt->bindParam(':lat', $latitude);
                $updateStmt->bindParam(':lng', $longitude);
                $updateStmt->bindParam(':checked_by', $checkedBy);
                $updateStmt->bindParam(':student_id', $studentId);
                $updateStmt->bindParam(':bus_id', $busId);
                $updateStmt->bindParam(':date', $date);
                $updateStmt->bindParam(':type', $type);
                $updateStmt->execute();
                
                return ['success' => true, 'message' => 'Attendance updated', 'action' => 'updated'];
            } else {
                // Insert new
                $insertQuery = "INSERT INTO attendance 
                               (student_id, bus_id, route_id, attendance_date, attendance_type, 
                                status, check_in_time, check_in_latitude, check_in_longitude, checked_by) 
                               VALUES (:student_id, :bus_id, :route_id, :date, :type, 
                                       :status, :time, :lat, :lng, :checked_by)";
                $insertStmt = $this->db->prepare($insertQuery);
                $insertStmt->bindParam(':student_id', $studentId);
                $insertStmt->bindParam(':bus_id', $busId);
                $insertStmt->bindParam(':route_id', $routeId);
                $insertStmt->bindParam(':date', $date);
                $insertStmt->bindParam(':type', $type);
                $insertStmt->bindParam(':status', $status);
                $insertStmt->bindParam(':time', $time);
                $insertStmt->bindParam(':lat', $latitude);
                $insertStmt->bindParam(':lng', $longitude);
                $insertStmt->bindParam(':checked_by', $checkedBy);
                $insertStmt->execute();
                
                // Send notification if absent
                if ($status === 'absent') {
                    $this->notifyAbsence($studentId, $busId, $type);
                }
                
                return ['success' => true, 'message' => 'Attendance recorded', 'action' => 'created'];
            }
        } catch (Exception $e) {
            error_log("Attendance error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error recording attendance'];
        }
    }
    
    /**
     * Get attendance report
     * 
     * @param int $schoolId School ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param int|null $studentId Filter by student
     * @param int|null $busId Filter by bus
     * @return array Attendance data
     */
    public function getAttendanceReport($schoolId, $startDate, $endDate, $studentId = null, $busId = null) {
        try {
            $query = "SELECT a.*, s.student_name, s.grade, b.bus_number, r.route_name,
                             u.full_name as checked_by_name
                      FROM attendance a
                      INNER JOIN students s ON a.student_id = s.id
                      INNER JOIN buses b ON a.bus_id = b.id
                      INNER JOIN routes r ON a.route_id = r.id
                      LEFT JOIN users u ON a.checked_by = u.id
                      WHERE s.school_id = :school_id
                        AND a.attendance_date BETWEEN :start_date AND :end_date";
            
            if ($studentId) {
                $query .= " AND a.student_id = :student_id";
            }
            if ($busId) {
                $query .= " AND a.bus_id = :bus_id";
            }
            
            $query .= " ORDER BY a.attendance_date DESC, a.check_in_time DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            
            if ($studentId) {
                $stmt->bindParam(':student_id', $studentId);
            }
            if ($busId) {
                $stmt->bindParam(':bus_id', $busId);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Attendance report error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get attendance statistics
     * 
     * @param int $schoolId School ID
     * @param string $date Date
     * @return array Statistics
     */
    public function getAttendanceStats($schoolId, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                        SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
                      FROM attendance a
                      INNER JOIN students s ON a.student_id = s.id
                      WHERE s.school_id = :school_id
                        AND a.attendance_date = :date";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Attendance stats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Notify parent of student absence
     * 
     * @param int $studentId Student ID
     * @param int $busId Bus ID
     * @param string $type Attendance type
     */
    private function notifyAbsence($studentId, $busId, $type) {
        try {
            $studentQuery = "SELECT s.*, u.id as parent_id, u.email as parent_email, 
                                   u.phone as parent_phone, sch.id as school_id
                            FROM students s
                            INNER JOIN users u ON s.parent_id = u.id
                            INNER JOIN schools sch ON s.school_id = sch.id
                            WHERE s.id = :student_id";
            $studentStmt = $this->db->prepare($studentQuery);
            $studentStmt->bindParam(':student_id', $studentId);
            $studentStmt->execute();
            $student = $studentStmt->fetch();
            
            if ($student) {
                $message = "Alert: {$student['student_name']} was marked as ABSENT for {$type} on bus.";
                
                $notifQuery = "INSERT INTO notifications 
                              (school_id, recipient_type, recipient_id, bus_id, message, 
                               notification_type, priority, channels) 
                              VALUES (:school_id, 'user', :parent_id, :bus_id, :message, 
                                      'attendance', 'high', :channels)";
                $notifStmt = $this->db->prepare($notifQuery);
                $channels = json_encode(['email', 'sms', 'in_app']);
                $notifStmt->bindParam(':school_id', $student['school_id']);
                $notifStmt->bindParam(':parent_id', $student['parent_id']);
                $notifStmt->bindParam(':bus_id', $busId);
                $notifStmt->bindParam(':message', $message);
                $notifStmt->bindParam(':channels', $channels);
                $notifStmt->execute();
            }
        } catch (Exception $e) {
            error_log("Absence notification error: " . $e->getMessage());
        }
    }
}
?>

