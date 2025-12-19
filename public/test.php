<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Database Connection</h2>";

try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✓ Database connected successfully!<br><br>";
    
    // Test query
    $query = "SELECT * FROM users WHERE email = 'admin@school.com'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch();
    
    echo "<strong>User found:</strong><br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Name: " . $user['full_name'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Password Hash: " . $user['password'] . "<br><br>";
    
    // Test password
    $test_password = 'admin123';
    echo "<strong>Testing password 'admin123':</strong><br>";
    if (password_verify($test_password, $user['password'])) {
        echo "✓ Password verification SUCCESS!";
    } else {
        echo "✗ Password verification FAILED!";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
