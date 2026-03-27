<?php
/**
 * Central Configuration File - Load this first in all includes
 */

// ==================== BASE URL DETECTION ====================
if (!function_exists('detectBaseUrl')) {
    function detectBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        
        $basePath = dirname(dirname($scriptName));
        $basePath = str_replace('\\', '/', $basePath);
        
        if ($basePath == '/' || $basePath == '\\') {
            $basePath = '';
        }
        
        return rtrim($protocol . $host . $basePath, '/');
    }
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/University');
}

// ==================== DATABASE CONSTANTS ====================
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'student_course_hub');
}
?>