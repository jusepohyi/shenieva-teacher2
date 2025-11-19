<?php
// Use centralized CORS helper
include_once __DIR__ . '/cors.php';
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

    header("Content-Type: application/json");

    // central connection
    require_once __DIR__ . '/conn.php';

    // $conn is provided by conn.php (conn.php will exit with JSON on failure)

    $story = isset($_GET['story']) ? $_GET['story'] : 'story1';

    $allowedStories = ['story1', 'story2', 'story3'];
    if (!in_array($story, $allowedStories)) {
        echo json_encode(["error" => "Invalid story selection"]);
        exit();
    }

    // Insert into quizzes table
    $tableName = "quizzes_{$story}";

    if ($story === 'story3') {
        // Logic for story3: Store only the question
        $stmt = $conn->prepare("INSERT INTO $tableName (question, points) VALUES (?, ?)");
        $stmt->bind_param("si", $question, $points);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $stmt->error]);
        }

        $stmt->close();
    } elseif ($story === 'story2') {
        // Logic for story2: Exclude choices logic
        $stmt = $conn->prepare("INSERT INTO $tableName (question, answer, points) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $question, $answer, $points);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $stmt->error]);
        }

        $stmt->close();
    } else {
        // Logic for story1: Includes choices
        $choices = $data['choices'] ?? [];
        $choiceStory = "choices_{$story}";
        $stmt = $conn->prepare("INSERT INTO $tableName (question, answer, points) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $question, $answer, $points);

        if ($stmt->execute()) {
            $quizId = $conn->insert_id;

            foreach ($choices as $choice) {
                $isCorrect = ($choice === $answer) ? 1 : 0;
                $stmtChoice = $conn->prepare("INSERT INTO $choiceStory (quiz_id, choice_text, is_correct) VALUES (?, ?, ?)");
                $stmtChoice->bind_param("isi", $quizId, $choice, $isCorrect);
                $stmtChoice->execute();
            }

            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $stmt->error]);
        }

        $stmt->close();
    }

    $conn->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid data"]);
}
