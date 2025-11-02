<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';

// $conn provided by conn.php

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['student_id'], $data['ribbons'])) {
        throw new Exception("Missing required fields: student_id or ribbons");
    }

    $student_id = (int)$data['student_id'];
    $ribbons = (int)$data['ribbons'];

    // Validate inputs
    if ($student_id <= 0) {
        throw new Exception("Invalid student ID");
    }
    if ($ribbons < 0) {
        throw new Exception("Invalid ribbon count");
    }

    // Update studentRibbon
    $query = "UPDATE students_table SET studentRibbon = COALESCE(studentRibbon, 0) + ? WHERE pk_studentID = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $ribbons, $student_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No student found with ID: $student_id");
    }

    $stmt->close();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>