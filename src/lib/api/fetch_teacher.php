<?php
// fetch_teacher.php

// CORS: allow requests from the dev server (Vite) and from same-origin Apache.
// When credentials mode is 'include', Access-Control-Allow-Origin must be an explicit origin
// and Access-Control-Allow-Credentials must be true.
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    // Optionally restrict allowed origins here.
    header("Access-Control-Allow-Origin: " . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // Use centralized CORS helper
    include_once __DIR__ . '/cors.php';
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header("Content-Type: application/json");

// respond to preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'conn.php';

$teacherId = isset($_COOKIE['teacherId']) ? $_COOKIE['teacherId'] : null;
if (!$teacherId) {
    echo json_encode(["success" => false, "message" => "No teacherId cookie"]);
    exit;
}

$stmt = $conn->prepare('SELECT pk_teacherID, teacherName, teacherEmail, idNo FROM teacher_table WHERE pk_teacherID = ? LIMIT 1');
$stmt->bind_param('i', $teacherId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Teacher not found"]);
    $stmt->close();
    $conn->close();
    exit;
}
$row = $result->fetch_assoc();

echo json_encode(["success" => true, "data" => [
    "id" => (int)$row['pk_teacherID'],
    "name" => $row['teacherName'],
    "email" => $row['teacherEmail'],
    "idNo" => $row['idNo']
]]);

$stmt->close();
$conn->close();
?>