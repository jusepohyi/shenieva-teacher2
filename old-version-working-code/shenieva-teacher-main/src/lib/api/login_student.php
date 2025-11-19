<?php
// src/lib/api/login_student.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'conn.php';

// Get the JSON input from the request
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['idNo']) || !isset($data['studentPass'])) {
    echo json_encode(["success" => false, "message" => "Missing idNo or password"]);
    exit;
}

$idNo = $conn->real_escape_string($data['idNo']);
$studentPass = $conn->real_escape_string($data['studentPass']);

// Query to check student credentials - idNo is case-insensitive, password is case-sensitive
// First, get the student by ID (case-insensitive)
$sql = "SELECT * FROM students_table WHERE LOWER(idNo) = LOWER('$idNo')";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Fetch the student data
    $student = $result->fetch_assoc();
    
    // Now check if password matches EXACTLY (case-sensitive using BINARY comparison)
    if ($student['studentPass'] === $studentPass) {
        // Return success with student data
        echo json_encode([
            "success" => true, 
            "message" => "Login successful",
            "data" => $student
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid ID or password"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid ID or password"]);
}

$conn->close();
?>