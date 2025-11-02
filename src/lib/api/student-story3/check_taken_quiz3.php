<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// central connection
require_once __DIR__ . '/../conn.php';

// $conn provided by conn.php

// Get the student_id from the POST request
$input = json_decode(file_get_contents('php://input'), true);
$student_id = isset($input['student_id']) ? (int)$input['student_id'] : null;

if (!$student_id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing student_id"]);
    exit();
}

// Query to check if the student has a finalized quiz
$sql = "SELECT taken_quiz_id, student_id, attempt, question, answer, is_correct, is_final 
        FROM story3_taken_quizzes 
        WHERE student_id = ? AND is_final = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$response = ["is_finalized" => false];

if ($result->num_rows > 0) {
    $response["is_finalized"] = true;
    $response["data"] = $result->fetch_all(MYSQLI_ASSOC); // Optional: Include quiz data
}

$stmt->close();
$conn->close();

http_response_code(200);
echo json_encode($response);
?>