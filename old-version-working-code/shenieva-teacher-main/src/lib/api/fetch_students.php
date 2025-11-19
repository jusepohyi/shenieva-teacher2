<?php
// fetch_students.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include 'conn.php';

$sql = "SELECT pk_studentID, idNo, studentName, studentGender, studentLevel, studentRibbon, studentColtrash FROM students_table";
$result = $conn->query($sql);

$students = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

echo json_encode($students);

$conn->close();
?>