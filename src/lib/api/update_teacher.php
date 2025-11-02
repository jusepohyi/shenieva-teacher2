<?php
// src/lib/api/update_teacher.php

// CORS: allow the requesting origin and support credentials when present
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    header("Access-Control-Allow-Origin: " . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // Use centralized CORS helper
    include_once __DIR__ . '/cors.php';
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'conn.php';

// read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid JSON input"]);
    exit;
}

$name = isset($input['name']) ? trim($input['name']) : null;
$email = isset($input['email']) ? trim($input['email']) : null;
$currentPassword = isset($input['currentPassword']) ? $input['currentPassword'] : null;
$newPassword = isset($input['newPassword']) ? $input['newPassword'] : null;

if (!$email || !$currentPassword) {
    echo json_encode(["success" => false, "message" => "Email and currentPassword are required"]);
    exit;
}

// Find teacher by email and verify current password
$stmt = $conn->prepare('SELECT pk_teacherID, teacherPass, teacherName, teacherEmail FROM teacher_table WHERE teacherEmail = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No teacher found with that email"]);
    $stmt->close();
    $conn->close();
    exit;
}
$row = $result->fetch_assoc();

if ($row['teacherPass'] !== $currentPassword) {
    echo json_encode(["success" => false, "message" => "Incorrect current password"]);
    $stmt->close();
    $conn->close();
    exit;
}

$pk = (int)$row['pk_teacherID'];
$updates = [];
$types = '';
$values = [];

if ($name && $name !== $row['teacherName']) {
    $updates[] = 'teacherName = ?';
    $types .= 's';
    $values[] = $name;
}
if ($email && $email !== $row['teacherEmail']) {
    $updates[] = 'teacherEmail = ?';
    $types .= 's';
    $values[] = $email;
}
if ($newPassword) {
    $updates[] = 'teacherPass = ?';
    $types .= 's';
    $values[] = $newPassword;
}

if (count($updates) === 0) {
    echo json_encode(["success" => true, "message" => "No changes to save"]);
    $stmt->close();
    $conn->close();
    exit;
}

// build query
$sql = 'UPDATE teacher_table SET ' . implode(', ', $updates) . ' WHERE pk_teacherID = ?';
$types .= 'i';
$values[] = $pk;

$updateStmt = $conn->prepare($sql);
// bind params dynamically
$bind_names[] = $types;
for ($i = 0; $i < count($values); $i++) {
    $bind_name = 'bind' . $i;
    $$bind_name = $values[$i];
    $bind_names[] = &$$bind_name;
}

call_user_func_array([$updateStmt, 'bind_param'], $bind_names);

if ($updateStmt->execute()) {
    echo json_encode(["success" => true, "message" => "Profile updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error updating profile: " . $updateStmt->error]);
}

$updateStmt->close();
$stmt->close();
$conn->close();
?>