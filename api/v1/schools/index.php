<?php
/**
 * Schools API - List/Create Schools
 * 
 * GET /api/v1/schools - List schools
 * POST /api/v1/schools - Create school
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // List schools
    requireRoleMiddleware(['super_admin', 'admin']);
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, school_name, school_code, email, phone, status, 
                         subscription_plan, created_at
                  FROM schools
                  ORDER BY school_name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $schools = $stmt->fetchAll();
        
        sendJsonResponse(true, ['schools' => $schools], 'Schools retrieved successfully');
    } catch (Exception $e) {
        error_log("Get schools error: " . $e->getMessage());
        sendJsonResponse(false, null, 'Error retrieving schools', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create school
    requireRoleMiddleware(['super_admin']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['school_name']) || !isset($input['school_code'])) {
        sendJsonResponse(false, null, 'School name and code are required', 400);
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if code exists
        $checkQuery = "SELECT id FROM schools WHERE school_code = :code";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':code', $input['school_code']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            sendJsonResponse(false, null, 'School code already exists', 409);
        }
        
        // Insert school
        $query = "INSERT INTO schools 
                 (school_name, school_code, email, phone, address, city, country, 
                  subscription_plan, max_buses, max_students, max_drivers) 
                 VALUES (:name, :code, :email, :phone, :address, :city, :country, 
                         :plan, :max_buses, :max_students, :max_drivers)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $input['school_name']);
        $stmt->bindParam(':code', $input['school_code']);
        $stmt->bindParam(':email', $input['email'] ?? null);
        $stmt->bindParam(':phone', $input['phone'] ?? null);
        $stmt->bindParam(':address', $input['address'] ?? null);
        $stmt->bindParam(':city', $input['city'] ?? null);
        $stmt->bindParam(':country', $input['country'] ?? 'Lebanon');
        $stmt->bindParam(':plan', $input['subscription_plan'] ?? 'basic');
        $stmt->bindParam(':max_buses', $input['max_buses'] ?? 10);
        $stmt->bindParam(':max_students', $input['max_students'] ?? 100);
        $stmt->bindParam(':max_drivers', $input['max_drivers'] ?? 5);
        $stmt->execute();
        
        $schoolId = $db->lastInsertId();
        
        // Get created school
        $getQuery = "SELECT * FROM schools WHERE id = :id";
        $getStmt = $db->prepare($getQuery);
        $getStmt->bindParam(':id', $schoolId);
        $getStmt->execute();
        $school = $getStmt->fetch();
        
        sendJsonResponse(true, ['school' => $school], 'School created successfully', 201);
    } catch (Exception $e) {
        error_log("Create school error: " . $e->getMessage());
        sendJsonResponse(false, null, 'Error creating school', 500);
    }
} else {
    sendJsonResponse(false, null, 'Method not allowed', 405);
}
?>

