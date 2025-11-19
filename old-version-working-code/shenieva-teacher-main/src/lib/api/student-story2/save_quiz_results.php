<?php
// Use centralized CORS helper
include_once __DIR__ . '/../cors.php';
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';

// $conn provided by conn.php

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['student_id'], $data['attempt'], $data['results'])) {
        throw new Exception("Missing required fields");
    }

    $student_id = (int)$data['student_id'];
    $attempt = (int)$data['attempt'];
    $results = $data['results'];
    $is_final = isset($data['is_final']) ? (int)$data['is_final'] : 0;
    
    // Only save to database if it's the final submission
    if ($is_final !== 1) {
        echo json_encode(["success" => true, "message" => "Retake attempt not saved to database"]);
        $conn->close();
        exit();
    }
    
    // Default story title for Level 2 (can be passed from client if needed)
    $storyTitle = isset($data['storyTitle']) ? $conn->real_escape_string($data['storyTitle']) : "Level 2 Quiz";

    // Begin transaction
    $conn->begin_transaction();

    foreach ($results as $result) {
        $question = $conn->real_escape_string($result['question']);
        $answer = $conn->real_escape_string($result['answer']);
        $is_correct = $result['isCorrect'] ? 1 : 0;
        
        // For Level 2, correctAnswer is the right answer from the quiz data
        $correctAnswer = $conn->real_escape_string($answer); // In drag-and-drop, the answer IS the correct answer if matched
        $selectedAnswer = $conn->real_escape_string($answer); // The answer student selected/dragged

        // Insert into level2_quiz table (matching the schema)
        $query = "INSERT INTO level2_quiz (studentID, storyTitle, question, correctAnswer, selectedAnswer, score, attempt)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("issssii", $student_id, $storyTitle, $question, $correctAnswer, $selectedAnswer, $is_correct, $attempt);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(["success" => true, "message" => "Final quiz results saved to database"]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>