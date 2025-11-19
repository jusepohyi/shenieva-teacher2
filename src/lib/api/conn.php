<?php

// CORS handling is now done in individual PHP files (not centralized)
// require_once __DIR__ . '/cors.php';  // Disabled - using old approach

// optional debug logger
if (file_exists(__DIR__ . '/debug_log.php')) {
    include_once __DIR__ . '/debug_log.php';
}

// Disable error display to prevent HTML output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Read DB credentials from environment variables when available. This is safer
// for deployments. If not set, fall back to the local defaults used for
// development.
$servername = getenv('DB_HOST') ?: 'localhost'; // Hostinger usually uses localhost for PHP->MySQL on the same host
$username   = getenv('DB_USER') ?: 'u207191294_shenievia';
$password   = getenv('DB_PASS') ?: 'Shenieviareads123!!!';
$database   = getenv('DB_NAME') ?: 'u207191294_shenieva_db';

// If you prefer to hardcode the Hostinger credentials here for deployment,
// replace the defaults above with your values (not recommended for public
// repos). Example (do NOT commit credentials):
// $servername = 'localhost';
// $username = 'u207191294_shenievia';
// $password = 'your_password_here';
// $database = 'u207191294_shenieva_db';

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    // Log connection failures (do NOT log passwords)
    if (function_exists('api_debug_log')) {
        api_debug_log('db_connect_failed', [
            'error' => $conn->connect_error,
            'host' => $servername,
            'user' => $username,
            'db' => $database
        ]);
    }

    // Return JSON error instead of die() which outputs HTML
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Set charset to UTF-8
$conn->set_charset('utf8mb4');

?>
