<?php
/**
 * Database Connection Class
 * 
 * Handles PDO database connections using singleton pattern
 * for efficient resource management.
 * 
 * @author Dana Baradie
 * @course IT404
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'school_bus_tracking';
    private $username = 'root';
    private $password = '';
    private $conn;
    private static $instance = null;

    /**
     * Constructor - supports both singleton and direct instantiation
     */
    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }

    /**
     * Get singleton instance of Database
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get database connection
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>

