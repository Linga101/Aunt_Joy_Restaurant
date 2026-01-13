<?php
/**
 * Centralized Logging System for Aunt Joy's Restaurant
 * Provides error, security, and business event logging
 */

/**
 * Log error messages with context
 * @param string $message - Error message
 * @param array $context - Additional context data
 */
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logMessage = "[ERROR] {$timestamp} - {$message}{$contextStr}\n";
    
    // Log to custom application log
    $logFile = __DIR__ . '/../logs/application.log';
    error_log($logMessage, 3, $logFile);
    
    // Also log to PHP error log
    error_log($logMessage);
}

/**
 * Log informational messages
 * @param string $message - Info message
 * @param array $context - Additional context data
 */
function logInfo($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logMessage = "[INFO] {$timestamp} - {$message}{$contextStr}\n";
    
    $logFile = __DIR__ . '/../logs/application.log';
    error_log($logMessage, 3, $logFile);
}

/**
 * Log security-related events
 * @param string $message - Security message
 * @param array $context - Security context (IP, user agent, etc.)
 */
function logSecurity($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logMessage = "[SECURITY] {$timestamp} - {$message}{$contextStr}\n";
    
    // Log to security-specific log
    $logFile = __DIR__ . '/../logs/security.log';
    error_log($logMessage, 3, $logFile);
    
    // Also log to application log
    error_log($logMessage, 3, __DIR__ . '/../logs/application.log');
}

/**
 * Log business events (orders, payments, etc.)
 * @param string $message - Business event message
 * @param array $context - Business context
 */
function logBusiness($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logMessage = "[BUSINESS] {$timestamp} - {$message}{$contextStr}\n";
    
    $logFile = __DIR__ . '/../logs/business.log';
    error_log($logMessage, 3, $logFile);
    
    // Also log to application log
    error_log($logMessage, 3, __DIR__ . '/../logs/application.log');
}

/**
 * Initialize logging system
 * Creates necessary directories and sets up log rotation
 */
function initLogging() {
    $logDir = __DIR__ . '/../logs';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        if (!mkdir($logDir, 0755, true)) {
            error_log("Failed to create logs directory: {$logDir}");
            return false;
        }
    }
    
    // Create log files
    $logFiles = [
        $logDir . '/application.log',
        $logDir . '/security.log', 
        $logDir . '/business.log',
        $logDir . '/php_errors.log'
    ];
    
    foreach ($logFiles as $logFile) {
        if (!file_exists($logFile)) {
            touch($logFile);
        }
        // Set proper permissions
        chmod($logFile, 0644);
    }
    
    // Set up error log configuration
    ini_set('log_errors', 1);
    ini_set('error_log', $logDir . '/php_errors.log');
    ini_set('error_reporting', E_ALL);
    
    logInfo("Logging system initialized");
    return true;
}

// Auto-initialize logging when this file is included
initLogging();
?>