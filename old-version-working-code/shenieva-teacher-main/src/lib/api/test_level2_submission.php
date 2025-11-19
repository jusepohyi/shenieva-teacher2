<?php
// Test script to verify submit_level2_quiz.php
// Access: http://localhost/shenieva-teacher/src/lib/api/test_level2_submission.php

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conn.php';

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed', 'details' => $conn->connect_error ?? 'Unknown']);
    exit();
}

// Check if level2_quiz table exists
$result = $conn->query("SHOW TABLES LIKE 'level2_quiz'");
$tableExists = $result->num_rows > 0;

// Get table structure if exists
$structure = null;
if ($tableExists) {
    $structResult = $conn->query("DESCRIBE level2_quiz");
    $structure = [];
    while ($row = $structResult->fetch_assoc()) {
        $structure[] = $row;
    }
}

echo json_encode([
    'connection' => 'OK',
    'database' => 'shenieva_DB',
    'level2_quiz_exists' => $tableExists,
    'table_structure' => $structure,
    'test' => 'Database check complete'
], JSON_PRETTY_PRINT);

$conn->close();
?>
