<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Centralized CORS handling and DB connection
include_once __DIR__ . '/../cors.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../conn.php';

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $id = $data['id'] ?? 0;
    $question = $data['question'] ?? '';
    $choices = $data['choices'] ?? [];
    $answer = $data['answer'] ?? '';
    $points = $data['points'] ?? 0;

    if ($id <= 0 || empty($question) || empty($choices) || empty($answer) || $points <= 0 || !in_array($answer, $choices)) {
        echo json_encode(["success" => false, "error" => "Invalid or missing required fields"]);
        exit();
    }

    // $conn provided by conn.php

    $tableName = "quizzes_store2";
    $choicesTable = "choices_store2";

    $stmt = $conn->prepare("UPDATE $tableName SET question = ?, points = ? WHERE id = ?");
    $stmt->bind_param("sii", $question, $points, $id);

    if ($stmt->execute()) {
        // Delete existing choices
        $stmtDelete = $conn->prepare("DELETE FROM $choicesTable WHERE quiz_id = ?");
        $stmtDelete->bind_param("i", $id);
        $stmtDelete->execute();

        // Insert new choices
        $stmtChoice = $conn->prepare("INSERT INTO $choicesTable (quiz_id, choice_text, is_correct) VALUES (?, ?, ?)");
        foreach ($choices as $choice) {
            $isCorrect = ($choice === $answer) ? 1 : 0;
            $stmtChoice->bind_param("isi", $id, $choice, $isCorrect);
            $stmtChoice->execute();
        }

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update quiz: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid or missing data"]);
}
?>