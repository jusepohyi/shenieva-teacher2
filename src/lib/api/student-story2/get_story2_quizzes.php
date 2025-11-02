<?php
// Centralized CORS handling
include_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';

// $conn provided by conn.php

try {
    // Fetch quiz questions
    $query = "SELECT `id`, `question`, `answer`, `points` FROM `quizzes_story2`";
    $result = $conn->query($query);

    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $quizzes = [];
    while ($row = $result->fetch_assoc()) {
        $quizzes[] = $row;
    }

    echo json_encode($quizzes);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>