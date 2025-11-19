<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);

$quizID = isset($payload['quizID']) ? (int)$payload['quizID'] : 0;
$score = isset($payload['score']) ? (int)$payload['score'] : null;

if ($quizID <= 0 || $score === null) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing quizID or score'
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE level3_quiz SET point = ? WHERE quizID = ?");
    $stmt->bind_param('ii', $score, $quizID);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'No rows updated. Please check the quiz ID.'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'quizID' => $quizID,
            'score' => $score
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
