<?php
// src/lib/api/record_attendance.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'conn.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['fk_studentID']) || !isset($data['studentName']) || !isset($data['attendanceDateTime'])) {
    echo json_encode(["success" => false, "message" => "Missing student ID, name, or date/time"]);
    exit;
}

date_default_timezone_set('Asia/Manila'); // Change this to your timezone

// Convert the incoming ISO 8601 date string to a formatted datetime    
$rawDateTime = new DateTime($data['attendanceDateTime']);
$formattedDateTime = $rawDateTime->format('Y-m-d g:i A'); // Format as 2025-03-29 9:27 PM

$fk_studentID = $conn->real_escape_string($data['fk_studentID']);
$studentName = $conn->real_escape_string($data['studentName']);
$attendanceDateTime = $conn->real_escape_string($formattedDateTime);


// Insert attendance record with provided studentName
$sql = "INSERT INTO attendance_table (fk_studentID, studentName, attendanceDateTime) 
        VALUES ('$fk_studentID', '$studentName', '$attendanceDateTime')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Attendance recorded successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error recording attendance: " . $conn->error]);
}

$conn->close();
