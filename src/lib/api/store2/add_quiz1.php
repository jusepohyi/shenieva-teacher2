<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

    // central connection
    require_once __DIR__ . '/../conn.php';

    // $conn is provided by conn.php

    // Validate input
    if (empty($question) || empty($choices) || empty($answer) || $points <= 0 || !in_array($answer, $choices)) {
        echo json_encode(["success" => false, "error" => "Missing or invalid required fields"]);
        exit();
    }

    // $conn is provided by conn.php (conn.php will exit with JSON on failure)

    $tableName = "quizzes_store2";
    $choicesTable = "choices_store2";

    // Insert quiz (without answer, as it’s in choices_store2)
    $stmt = $conn->prepare("INSERT INTO $tableName (question, points) VALUES (?, ?)");
    $stmt->bind_param("si", $question, $points);

    if ($stmt->execute()) {
        $quizId = $conn->insert_id;

        // Insert choices
        $stmtChoice = $conn->prepare("INSERT INTO $choicesTable (quiz_id, choice_text, is_correct) VALUES (?, ?, ?)");
        foreach ($choices as $choice) {
            $isCorrect = ($choice === $answer) ? 1 : 0;
            $stmtChoice->bind_param("isi", $quizId, $choice, $isCorrect);
            $stmtChoice->execute();
        }

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to insert quiz: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid or missing data"]);
}
?>