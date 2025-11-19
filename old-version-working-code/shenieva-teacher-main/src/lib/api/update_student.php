<?php
// src/lib/api/update_student.php

// Allow requests from any origin (for development). Restrict in production.
header("Access-Control-Allow-Origin: *");

// Allow only the POST method.
header("Access-Control-Allow-Methods: POST");

// Allow specific headers.
header("Access-Control-Allow-Headers: Content-Type");

// Set content type to JSON.
header('Content-Type: application/json');

include 'conn.php';

// Get the raw input from the request body.
$data = json_decode(file_get_contents("php://input"), true);

// Check if required fields are provided.
if (!isset($data['pk_studentID']) || !isset($data['idNo']) || !isset($data['studentName']) || !isset($data['studentGender'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields (pk_studentID, idNo, studentName, or studentGender)"]);
    exit;
}

$pk_studentID = $data['pk_studentID']; // Primary key to identify the record
$idNo = $data['idNo']; // Updated field
$studentName = $data['studentName']; // Updated field
$studentGender = $data['studentGender']; // Updated field

// Prepare the update statement.
$sql = "UPDATE students_table SET idNo = ?, studentName = ?, studentGender = ? WHERE pk_studentID = ?";
$stmt = $conn->prepare($sql);

// Bind parameters: "sssi" (string, string, string, integer) assumes pk_studentID is an integer.
$stmt->bind_param("sssi", $idNo, $studentName, $studentGender, $pk_studentID);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Student updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error updating student: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>