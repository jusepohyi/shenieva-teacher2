<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $id = $data['id'] ?? 0;

    if ($id <= 0) {
        echo json_encode(["success" => false, "error" => "Invalid or missing quiz ID"]);
        exit();
    }

    // central connection
    require_once __DIR__ . '/../conn.php';

    // $conn provided by conn.php

    $tableName = "quizzes_store3";
    // choices_store2 deletion handled by ON DELETE CASCADE

    $stmt = $conn->prepare("DELETE FROM $tableName WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "No quiz found with the provided ID"]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Failed to delete quiz: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid or missing data"]);
}
?>