<?php
// src/lib/api/update_student.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'conn.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['pk_studentID']) || !isset($data['studentGender'])) {
    echo json_encode(["success" => false, "message" => "Missing student ID or gender"]);
    exit;
}

$pk_studentID = $conn->real_escape_string($data['pk_studentID']);
$studentGender = $conn->real_escape_string($data['studentGender']);

$sql = "UPDATE students_table SET studentGender = '$studentGender' WHERE pk_studentID = '$pk_studentID'";
if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Gender updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error updating gender: " . $conn->error]);
}

$conn->close();
?>