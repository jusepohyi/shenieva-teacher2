<?php
// src/lib/api/get_story1_quizzes.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Enable verbose errors during development so client can report useful diagnostics.
// Do NOT enable this in production.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection (use absolute path for reliability)
require_once __DIR__ . '/conn.php';

// Check if $conn is defined and valid
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection not initialized: ' . ($conn->connect_error ?? 'Unknown error')
    ]);
    exit;
}

try {
    // Query to get quizzes and their choices
    $query = "
        SELECT 
            q.id AS quiz_id,
            q.question,
            q.answer,
            q.points,
            c.id AS choice_id,
            c.choice_text,
            c.is_correct
        FROM quizzes_story1 q
        LEFT JOIN choices_story1 c ON q.id = c.quiz_id
        ORDER BY q.id, c.id
    ";
    
    $result = $conn->query($query);

    if ($result === FALSE) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . ($conn->error ?? 'Unknown SQL error'),
            'query' => $query
        ]);
        $conn->close();
        exit;
    }

    // Organize the data into a structured array
    $quizData = [];
    $currentQuizId = null;
    $currentQuiz = null;

    while ($row = $result->fetch_assoc()) {
        if ($currentQuizId !== $row['quiz_id']) {
            if ($currentQuiz !== null) {
                $quizData[] = $currentQuiz;
            }
            $currentQuiz = [
                'id' => $row['quiz_id'],
                'question' => $row['question'],
                'answer' => $row['answer'],
                'points' => (int)$row['points'], // Ensure points is an integer
                'choices' => []
            ];
            $currentQuizId = $row['quiz_id'];
        }
        if ($row['choice_id']) {
            $currentQuiz['choices'][] = [
                'id' => $row['choice_id'],
                'text' => $row['choice_text'],
                'is_correct' => (bool)$row['is_correct'] // Convert to boolean
            ];
        }
    }
    if ($currentQuiz !== null) {
        $quizData[] = $currentQuiz;
    }

    // Return the data as JSON
    echo json_encode([
        'success' => true,
        'data' => $quizData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>