<?php
// Dynamic CORS handling: allow dev origins used by the Svelte dev server.
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:5174',
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: null');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'conn.php';

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error')]);
    exit();
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || !isset($payload['student_id']) || !isset($payload['storyKey']) || !isset($payload['attempt'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit();
}

$student_id = (int)$payload['student_id'];
$storyKey = $conn->real_escape_string($payload['storyKey']);
$storyTitle = '';
if (isset($payload['storyTitle']) && is_string($payload['storyTitle']) && $payload['storyTitle'] !== '') {
    $storyTitle = $conn->real_escape_string($payload['storyTitle']);
} else {
    // server-side fallback mapping
    $map = [
        'story2-1' => "Hector's Health",
        'story2-2' => 'Helpful Maya',
        'story2-3' => 'Royce Choice'
    ];
    $storyTitle = isset($map[$storyKey]) ? $conn->real_escape_string($map[$storyKey]) : $storyKey;
}
$attempt = $payload['attempt'];
$answers = isset($attempt['answers']) ? $attempt['answers'] : [];
$score = isset($attempt['score']) ? (int)$attempt['score'] : 0;
$attemptNum = isset($attempt['retakeCount']) ? (int)$attempt['retakeCount'] : 0;

// optional: correctAnswers mapping sent from client
$correctAnswers = isset($payload['correctAnswers']) ? $payload['correctAnswers'] : [];

try {
    $conn->begin_transaction();

    // Level 2 uses drag-and-drop quizzes where answers are stored as JSON mappings
    // We need to iterate through each sub-question in the mapping
    $stmt = $conn->prepare("INSERT INTO level2_quiz (studentID, storyTitle, question, correctAnswer, selectedAnswer, score, attempt) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

    foreach ($answers as $qid => $selectedRaw) {
        // Try to parse as JSON mapping (Level 2 drag-and-drop style)
        $selectedMap = null;
        $correctMap = null;
        
        if (is_string($selectedRaw)) {
            $selectedMap = json_decode($selectedRaw, true);
        } else if (is_array($selectedRaw)) {
            $selectedMap = $selectedRaw;
        }
        
        $correctRaw = isset($correctAnswers[$qid]) ? $correctAnswers[$qid] : null;
        if (is_string($correctRaw)) {
            $correctMap = json_decode($correctRaw, true);
        } else if (is_array($correctRaw)) {
            $correctMap = $correctRaw;
        }

        // If we have mappings, insert a row for each sub-question
        if ($selectedMap && $correctMap && is_array($selectedMap) && is_array($correctMap)) {
            foreach ($correctMap as $subKey => $correctVal) {
                $questionText = "Question $subKey: " . $conn->real_escape_string($correctVal);
                $correctAnswer = $conn->real_escape_string($correctVal);
                $selectedAnswer = isset($selectedMap[$subKey]) ? $conn->real_escape_string($selectedMap[$subKey]) : '';
                
                // Calculate score for this specific sub-question
                $isCorrect = (String($selectedMap[$subKey] ?? '') === String($correctVal)) ? 1 : 0;
                $aNum = $attemptNum + 1; // attempt number: retakeCount + 1

                $stmt->bind_param("issssii", $student_id, $storyTitle, $questionText, $correctAnswer, $selectedAnswer, $isCorrect, $aNum);
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed: ' . $stmt->error);
                }
            }
        } else {
            // Fallback: treat as single question/answer
            $questionText = $conn->real_escape_string($qid);
            $correctAnswer = is_string($correctRaw) ? $conn->real_escape_string($correctRaw) : '';
            $selectedAnswer = is_string($selectedRaw) ? $conn->real_escape_string($selectedRaw) : '';
            $isCorrect = ($selectedAnswer === $correctAnswer) ? 1 : 0;
            $aNum = $attemptNum + 1;

            $stmt->bind_param("issssii", $student_id, $storyTitle, $questionText, $correctAnswer, $selectedAnswer, $isCorrect, $aNum);
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
        }
    }

    $stmt->close();
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save quiz: ' . $e->getMessage()]);
} finally {
    $conn->close();
}

?>
