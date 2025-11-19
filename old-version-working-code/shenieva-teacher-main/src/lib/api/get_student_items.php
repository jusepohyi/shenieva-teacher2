<?php
// src/lib/api/get_student_items.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'conn.php';

if (!isset($_GET['studentID'])) {
    echo json_encode(["success" => false, "message" => "Missing student ID"]);
    exit;
}

$studentID = $conn->real_escape_string($_GET['studentID']);
// Ensure expected tables exist to avoid throwing SQL exceptions on absent schema
$check1 = $conn->query("SHOW TABLES LIKE 'student_items'");
$check2 = $conn->query("SHOW TABLES LIKE 'items_table'");
if (!$check1 || $check1->num_rows === 0 || !$check2 || $check2->num_rows === 0) {
    // Return an empty gifts list rather than an error to keep the client resilient
    echo json_encode(["success" => true, "gifts" => []]);
    $conn->close();
    exit;
}

$sql = "SELECT i.itemName 
        FROM student_items si 
        JOIN items_table i ON si.itemID = i.itemID 
        WHERE si.studentID = '$studentID'";
$result = $conn->query($sql);

if ($result === FALSE) {
    echo json_encode(["success" => true, "gifts" => []]);
    $conn->close();
    exit;
}

$gifts = [];
while ($row = $result->fetch_assoc()) {
    $gifts[] = $row['itemName'];
}

echo json_encode(["success" => true, "gifts" => $gifts]);

$conn->close();
?>