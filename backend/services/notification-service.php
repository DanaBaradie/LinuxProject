<?php
/**
 * Notification Service
 * 
 * Automated notification system for bus tracking events
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class NotificationService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Check if bus is nearby a stop and send notification
     * 
     * @param int $busId Bus ID
     * @param float $latitude Bus latitude
     * @param float $longitude Bus longitude
     * @param float $radius Radius in kilometers (default 0.5km)
     */
    public function checkBusNearby($busId, $latitude, $longitude, $radius = 0.5) {
        try {
            // Get bus route
            $routeQuery = "SELECT r.id, r.route_name
                          FROM routes r
                          INNER JOIN bus_routes br ON r.id = br.route_id
                          WHERE br.bus_id = :bus_id AND r.active = TRUE";
            $routeStmt = $this->db->prepare($routeQuery);
            $routeStmt->bindParam(':bus_id', $busId);
            $routeStmt->execute();
            $routes = $routeStmt->fetchAll();
            
            foreach ($routes as $route) {
                // Get stops for this route
                $stopsQuery = "SELECT rs.*, s.parent_id
                              FROM route_stops rs
                              LEFT JOIN students s ON rs.id = s.assigned_stop_id
                              WHERE rs.route_id = :route_id AND s.parent_id IS NOT NULL";
                $stopsStmt = $this->db->prepare($stopsQuery);
                $stopsStmt->bindParam(':route_id', $route['id']);
                $stopsStmt->execute();
                $stops = $stopsStmt->fetchAll();
                
                foreach ($stops as $stop) {
                    $distance = $this->calculateDistance(
                        $latitude, $longitude,
                        $stop['latitude'], $stop['longitude']
                    );
                    
                    // If bus is within radius, send notification
                    if ($distance <= $radius) {
                        // Check if notification already sent in last 5 minutes
                        $checkQuery = "SELECT id FROM notifications 
                                      WHERE parent_id = :parent_id 
                                        AND bus_id = :bus_id 
                                        AND notification_type = 'nearby'
                                        AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
                        $checkStmt = $this->db->prepare($checkQuery);
                        $checkStmt->bindParam(':parent_id', $stop['parent_id']);
                        $checkStmt->bindParam(':bus_id', $busId);
                        $checkStmt->execute();
                        
                        if ($checkStmt->rowCount() === 0) {
                            $this->createNotification(
                                $stop['parent_id'],
                                $busId,
                                "Bus is approaching {$stop['stop_name']}. Estimated arrival: " . round($distance * 60) . " minutes.",
                                'nearby'
                            );
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Check bus nearby error: " . $e->getMessage());
        }
    }
    
    /**
     * Check bus speed and send warning if exceeding limit
     * 
     * @param int $busId Bus ID
     * @param float $speed Current speed in km/h
     * @param float $speedLimit Speed limit (default 60 km/h)
     */
    public function checkSpeedWarning($busId, $speed, $speedLimit = 60) {
        if ($speed > $speedLimit) {
            try {
                // Get parents for this bus
                $parentsQuery = "SELECT DISTINCT s.parent_id
                                FROM students s
                                INNER JOIN route_stops rs ON s.assigned_stop_id = rs.id
                                INNER JOIN routes r ON rs.route_id = r.id
                                INNER JOIN bus_routes br ON r.id = br.route_id
                                WHERE br.bus_id = :bus_id";
                $parentsStmt = $this->db->prepare($parentsQuery);
                $parentsStmt->bindParam(':bus_id', $busId);
                $parentsStmt->execute();
                $parents = $parentsStmt->fetchAll();
                
                foreach ($parents as $parent) {
                    // Check if notification already sent in last 10 minutes
                    $checkQuery = "SELECT id FROM notifications 
                                  WHERE parent_id = :parent_id 
                                    AND bus_id = :bus_id 
                                    AND notification_type = 'speed_warning'
                                    AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
                    $checkStmt = $this->db->prepare($checkQuery);
                    $checkStmt->bindParam(':parent_id', $parent['parent_id']);
                    $checkStmt->bindParam(':bus_id', $busId);
                    $checkStmt->execute();
                    
                    if ($checkStmt->rowCount() === 0) {
                        $this->createNotification(
                            $parent['parent_id'],
                            $busId,
                            "Speed alert: Bus is traveling at " . round($speed) . " km/h (limit: {$speedLimit} km/h).",
                            'speed_warning'
                        );
                    }
                }
            } catch (Exception $e) {
                error_log("Speed warning error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Create a notification
     * 
     * @param int $parentId Parent ID
     * @param int|null $busId Bus ID
     * @param string $message Notification message
     * @param string $type Notification type
     */
    private function createNotification($parentId, $busId, $message, $type) {
        try {
            $query = "INSERT INTO notifications (parent_id, bus_id, message, notification_type) 
                      VALUES (:parent_id, :bus_id, :message, :type)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':parent_id', $parentId);
            $stmt->bindParam(':bus_id', $busId);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':type', $type);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Create notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate distance between two coordinates (Haversine formula)
     * 
     * @param float $lat1 Latitude 1
     * @param float $lon1 Longitude 1
     * @param float $lat2 Latitude 2
     * @param float $lon2 Longitude 2
     * @return float Distance in kilometers
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}
?>

