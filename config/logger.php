<?php
/**
 * Professional Logging System
 * 
 * Provides structured logging with different log levels
 * 
 * @author Dana Baradie
 * @course IT404
 */

require_once __DIR__ . '/env.php';

class Logger {
    private static $logFile = null;
    private static $logLevel = 'error';

    /**
     * Initialize logger
     */
    public static function init() {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        self::$logFile = $logDir . '/' . Env::get('LOG_FILE', 'app.log');
        self::$logLevel = Env::get('LOG_LEVEL', 'error');
    }

    /**
     * Get log level priority
     * 
     * @param string $level
     * @return int
     */
    private static function getLevelPriority($level) {
        $levels = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3,
            'critical' => 4
        ];
        return $levels[strtolower($level)] ?? 999;
    }

    /**
     * Check if should log this level
     * 
     * @param string $level
     * @return bool
     */
    private static function shouldLog($level) {
        self::init();
        return self::getLevelPriority($level) >= self::getLevelPriority(self::$logLevel);
    }

    /**
     * Write log entry
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     */
    private static function write($level, $message, $context = []) {
        if (!self::shouldLog($level)) {
            return;
        }

        self::init();

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;

        // Write to file
        if (self::$logFile) {
            file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }

        // Also log to PHP error log if critical
        if ($level === 'critical' || $level === 'error') {
            error_log($message . $contextStr);
        }
    }

    /**
     * Log debug message
     * 
     * @param string $message
     * @param array $context
     */
    public static function debug($message, $context = []) {
        self::write('debug', $message, $context);
    }

    /**
     * Log info message
     * 
     * @param string $message
     * @param array $context
     */
    public static function info($message, $context = []) {
        self::write('info', $message, $context);
    }

    /**
     * Log warning message
     * 
     * @param string $message
     * @param array $context
     */
    public static function warning($message, $context = []) {
        self::write('warning', $message, $context);
    }

    /**
     * Log error message
     * 
     * @param string $message
     * @param array $context
     */
    public static function error($message, $context = []) {
        self::write('error', $message, $context);
    }

    /**
     * Log critical error
     * 
     * @param string $message
     * @param array $context
     */
    public static function critical($message, $context = []) {
        self::write('critical', $message, $context);
    }

    /**
     * Log exception
     * 
     * @param Exception $exception
     * @param array $context
     */
    public static function exception($exception, $context = []) {
        $message = get_class($exception) . ': ' . $exception->getMessage();
        $context['file'] = $exception->getFile();
        $context['line'] = $exception->getLine();
        $context['trace'] = $exception->getTraceAsString();
        self::error($message, $context);
    }
}

