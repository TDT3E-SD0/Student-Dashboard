<?php
/**
 * Database Connection Configuration
 * 
 * Secure PDO connection for Student Dashboard
 * Uses environment variables for sensitive data
 */

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'student_dashboard');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// PDO Options
$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Create DSN (Data Source Name)
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // Create PDO connection
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    
    // Optional: Log successful connection (remove in production)
    // error_log("Database connection successful");
    
} catch (PDOException $e) {
    // Log error to file instead of displaying
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Show generic error to user (don't expose database details)
    http_response_code(500);
    die("Service temporarily unavailable. Please try again later.");
}

?>
