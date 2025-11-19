<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Centralized CORS handling
include_once __DIR__ . '/cors.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $id = $data['id'] ?? 0;

        // Use central connection
        require_once __DIR__ . '/conn.php';

        // $conn is provided by conn.php

    $story = isset($_GET['story']) ? $_GET['story'] : 'story1';

    $allowedStories = ['story1', 'story2', 'story3'];
    if (!in_array($story, $allowedStories)) {
        echo json_encode(["error" => "Invalid story selection"]);
        exit();
    }

    // Delete logic
    if ($story === 'story1') {
        $stmt = $conn->prepare("DELETE FROM choices_story1 WHERE quiz_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    if ($story === 'story2') {
        // Story 2 has no choices deletion logic
    }

    // Delete quiz entry for all stories
    $stmt = $conn->prepare("DELETE FROM quizzes_{$story} WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid data"]);
}
?>
