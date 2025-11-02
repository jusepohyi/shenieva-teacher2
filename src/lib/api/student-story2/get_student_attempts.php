<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';

// $conn provided by conn.php

try {
    if (!isset($_GET['student_id'])) {
        throw new Exception("Missing student_id parameter");
    }

    $student_id = (int)$_GET['student_id'];
    
    // Count distinct attempts for the student
    $query = "SELECT COUNT(DISTINCT attempt) as attempts FROM story2_taken_quizzes WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $student_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $attempts = (int)$row['attempts'];

    echo json_encode(["attempts" => $attempts]);
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>