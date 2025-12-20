<?php
/**
 * SMS Service
 * 
 * Handles SMS notifications via various providers
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/../../config/database.php';

class SMSService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Send SMS
     * 
     * @param int $schoolId School ID
     * @param string $phone Phone number
     * @param string $message Message
     * @return array Result
     */
    public function sendSMS($schoolId, $phone, $message) {
        try {
            // Get SMS integration settings
            $query = "SELECT * FROM integration_settings 
                     WHERE school_id = :school_id 
                       AND integration_type = 'sms' 
                       AND is_active = TRUE";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':school_id', $schoolId);
            $stmt->execute();
            $settings = $stmt->fetch();
            
            if (!$settings) {
                return ['success' => false, 'message' => 'SMS not configured'];
            }
            
            $provider = $settings['provider'];
            $config = json_decode($settings['config'], true);
            
            // Send via provider
            switch ($provider) {
                case 'twilio':
                    return $this->sendViaTwilio($phone, $message, $config);
                case 'nexmo':
                    return $this->sendViaNexmo($phone, $message, $config);
                case 'custom':
                    return $this->sendViaCustom($phone, $message, $config);
                default:
                    return ['success' => false, 'message' => 'Unknown provider'];
            }
        } catch (Exception $e) {
            error_log("SMS error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error sending SMS'];
        }
    }
    
    /**
     * Send via Twilio
     */
    private function sendViaTwilio($phone, $message, $config) {
        // Implementation for Twilio
        // For now, return mock success
        return ['success' => true, 'message' => 'SMS sent (mock)', 'provider' => 'twilio'];
    }
    
    /**
     * Send via Nexmo/Vonage
     */
    private function sendViaNexmo($phone, $message, $config) {
        // Implementation for Nexmo
        return ['success' => true, 'message' => 'SMS sent (mock)', 'provider' => 'nexmo'];
    }
    
    /**
     * Send via custom API
     */
    private function sendViaCustom($phone, $message, $config) {
        if (!isset($config['api_url'])) {
            return ['success' => false, 'message' => 'Custom API not configured'];
        }
        
        // Make API call
        $ch = curl_init($config['api_url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'phone' => $phone,
            'message' => $message,
            'api_key' => $config['api_key'] ?? ''
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return ['success' => true, 'message' => 'SMS sent', 'response' => $response];
    }
}
?>

