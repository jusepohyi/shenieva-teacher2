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
    $question = $data['question'] ?? '';
    $answer = $data['answer'] ?? '';
    $points = $data['points'] ?? 0;

    // Use central connection
    require_once __DIR__ . '/conn.php';

    // $conn is provided by conn.php (and it will exit with a JSON error if connection fails)

    $story = isset($_GET['story']) ? $_GET['story'] : 'story1';

    $allowedStories = ['story1', 'story2', 'story3'];
    if (!in_array($story, $allowedStories)) {
        echo json_encode(["error" => "Invalid story selection"]);
        exit();
    }

    $tableName = "quizzes_{$story}";

    if ($story === 'story3') {
        // Update logic for story3 - only edit the question
        $stmt = $conn->prepare("UPDATE $tableName SET question = ?, points = ? WHERE id = ?");
        $stmt->bind_param("sii", $question, $points, $id);
    } elseif ($story === 'story2') {
        // Update logic for story2 (no choices)
        $stmt = $conn->prepare("UPDATE $tableName SET question = ?, answer = ?, points = ? WHERE id = ?");
        $stmt->bind_param("ssii", $question, $answer, $points, $id);
    } else {
        // Original logic for story1 (with choices)
        $stmt = $conn->prepare("UPDATE $tableName SET question = ?, answer = ?, points = ? WHERE id = ?");
        $stmt->bind_param("ssii", $question, $answer, $points, $id);

        if ($stmt->execute()) {
            // Manage choices for story1
            foreach ($choices as $choice) {
                $isCorrect = ($choice === $answer) ? 1 : 0;
                $stmtChoice = $conn->prepare("INSERT INTO choices_{$story} (quiz_id, choice_text, is_correct) VALUES (?, ?, ?)");
                $stmtChoice->bind_param("isi", $id, $choice, $isCorrect);
                $stmtChoice->execute();
            }
        }
    }

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
