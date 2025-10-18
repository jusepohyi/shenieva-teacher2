<?php

// Disable error display to prevent HTML output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

$servername = "localhost"; // Change if your database is hosted elsewhere
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$database = "shenieva_DB";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    // Return JSON error instead of die() which outputs HTML
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
} 

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

?>
