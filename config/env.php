<?php
/**
 * Environment Configuration Loader
 * 
 * Loads configuration from .env file with fallback to defaults
 * 
 * @author Dana Baradie
 * @course IT404
 */

class Env {
    private static $loaded = false;
    private static $cache = [];

    /**
     * Load environment variables from .env file
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }

        if ($path === null) {
            $path = __DIR__ . '/../.env';
        }

        if (!file_exists($path)) {
            // Try alternative locations
            $alternatives = [
                __DIR__ . '/.env',
                dirname(__DIR__) . '/.env',
                $_SERVER['DOCUMENT_ROOT'] . '/.env'
            ];
            
            foreach ($alternatives as $alt) {
                if (file_exists($alt)) {
                    $path = $alt;
                    break;
                }
            }
        }

        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    $value = trim($value, '"\'');
                    
                    // Set environment variable if not already set
                    if (!isset($_ENV[$key]) && !getenv($key)) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;
                    }
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Get environment variable with default value
     * 
     * @param string $key Environment variable key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        self::load();
        
        // Check cache first
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $value = getenv($key);
        
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }

        // Convert string booleans
        if (is_string($value)) {
            $lower = strtolower($value);
            if ($lower === 'true') {
                $value = true;
            } elseif ($lower === 'false') {
                $value = false;
            } elseif ($lower === 'null') {
                $value = null;
            } elseif (is_numeric($value)) {
                $value = strpos($value, '.') !== false ? (float)$value : (int)$value;
            }
        }

        self::$cache[$key] = $value;
        return $value;
    }

    /**
     * Check if environment is production
     * 
     * @return bool
     */
    public static function isProduction() {
        return self::get('APP_ENV', 'production') === 'production';
    }

    /**
     * Check if debugging is enabled
     * 
     * @return bool
     */
    public static function isDebug() {
        return self::get('APP_DEBUG', false) === true || self::get('APP_DEBUG', 'false') === 'true';
    }
}

// Auto-load on include
Env::load();

