<?php
/**
 * Security Headers and Functions
 * 
 * Implements security best practices
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/env.php';

class Security {
    /**
     * Set security headers
     */
    public static function setHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://maps.googleapis.com https://*.googleapis.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' https://cdn.jsdelivr.net; " .
               "connect-src 'self' https://api.mailgun.net https://*.googleapis.com;";
        header("Content-Security-Policy: $csp");
        
        // Strict Transport Security (HTTPS only)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /**
     * Set CORS headers
     */
    public static function setCorsHeaders() {
        $allowedOrigins = explode(',', Env::get('CORS_ALLOWED_ORIGINS', '*'));
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 3600');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Rate limiting check
     * 
     * @param string $identifier User identifier (IP, user ID, etc.)
     * @param int $maxRequests Maximum requests
     * @param int $window Time window in seconds
     * @return bool
     */
    public static function checkRateLimit($identifier, $maxRequests = 100, $window = 60) {
        if (!Env::get('RATE_LIMIT_ENABLED', true)) {
            return true;
        }

        $cacheDir = __DIR__ . '/../storage/rate_limit';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $key = md5($identifier);
        $file = $cacheDir . '/' . $key . '.json';
        
        $data = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: [];
        }

        $now = time();
        $requests = array_filter($data['requests'] ?? [], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });

        if (count($requests) >= $maxRequests) {
            return false;
        }

        $requests[] = $now;
        $data['requests'] = $requests;
        $data['last_request'] = $now;

        file_put_contents($file, json_encode($data), LOCK_EX);
        
        // Clean old files
        self::cleanRateLimitCache($cacheDir);
        
        return true;
    }

    /**
     * Clean old rate limit cache files
     * 
     * @param string $cacheDir
     */
    private static function cleanRateLimitCache($cacheDir) {
        $files = glob($cacheDir . '/*.json');
        $now = time();
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['last_request'])) {
                // Delete files older than 1 hour
                if (($now - $data['last_request']) > 3600) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Get client IP address
     * 
     * @return string
     */
    public static function getClientIp() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Generate secure random token
     * 
     * @param int $length
     * @return string
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash password securely
     * 
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

