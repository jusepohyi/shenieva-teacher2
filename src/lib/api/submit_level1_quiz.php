<?php
// Suppress error display to prevent HTML output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

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

// Check if this is a final submission - only save final attempts
$is_final = isset($payload['is_final']) ? (int)$payload['is_final'] : 0;
if ($is_final !== 1) {
    // Not a final submission (student will retake), don't save to database
    echo json_encode(['success' => true, 'message' => 'Retake attempt not saved']);
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
        'story1-1' => "Maria's Promise",
        'story1-2' => 'Candice and Candies',
        'story1-3' => 'Hannah, the Honest Vendor'
    ];
    $storyTitle = isset($map[$storyKey]) ? $conn->real_escape_string($map[$storyKey]) : $storyKey;
}
$attempt = $payload['attempt'];
$answers = isset($attempt['answers']) ? $attempt['answers'] : [];
$score = isset($attempt['score']) ? (int)$attempt['score'] : 0;
$attemptNum = isset($attempt['retakeCount']) ? (int)$attempt['retakeCount'] : 0;

// optional: questions metadata mapping sent from client for better text/choices
$questions = isset($payload['questions']) ? $payload['questions'] : [];
$correctAnswers = isset($payload['correctAnswers']) ? $payload['correctAnswers'] : [];

try {
    $conn->begin_transaction();

    // Changed 'score' to 'point' - each question gets 0 or 1 point
    $stmt = $conn->prepare("INSERT INTO level1_quiz (studentID, storyTitle, question, choiceA, choiceB, choiceC, choiceD, correctAnswer, selectedAnswer, point, attempt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

    foreach ($answers as $qid => $selected) {
        $qText = '';
        $choiceA = '';
        $choiceB = '';
        $choiceC = '';
        $choiceD = '';
        if (isset($questions[$qid])) {
            $qText = $conn->real_escape_string($questions[$qid]['text'] ?? '');
            $choices = $questions[$qid]['choices'] ?? [];
            $choiceA = $conn->real_escape_string($choices['a'] ?? '');
            $choiceB = $conn->real_escape_string($choices['b'] ?? '');
            $choiceC = $conn->real_escape_string($choices['c'] ?? '');
            $choiceD = $conn->real_escape_string($choices['d'] ?? '');
        } else {
            // fallback to qid as question text
            $qText = $conn->real_escape_string($qid);
        }

        $correct = isset($correctAnswers[$qid]) ? $conn->real_escape_string($correctAnswers[$qid]) : '';
        $selectedEsc = $conn->real_escape_string($selected);

        // Calculate point per question: 1 if correct, 0 if wrong
        $point = (strtolower(trim($selectedEsc)) === strtolower(trim($correct))) ? 1 : 0;
        $aNum = $attemptNum + 1; // attempt number: retakeCount + 1

        $stmt->bind_param("issssssssii", $student_id, $storyTitle, $qText, $choiceA, $choiceB, $choiceC, $choiceD, $correct, $selectedEsc, $point, $aNum);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
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
