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
        'story3-1' => "Tonya's Tooth",
        'story3-2' => "Lola Gloria's Flowerpot",
        'story3-3' => "Liloy and Lingling The Dog"
    ];
    $storyTitle = isset($map[$storyKey]) ? $conn->real_escape_string($map[$storyKey]) : $storyKey;
}
$attempt = $payload['attempt'];
$answers = isset($attempt['answers']) ? $attempt['answers'] : [];
$attemptNum = isset($attempt['retakeCount']) ? (int)$attempt['retakeCount'] : 0;

// optional: questions metadata mapping sent from client
$questions = isset($payload['questions']) ? $payload['questions'] : [];

try {
    $conn->begin_transaction();

    // Level 3 uses essay-type questions where answers are text responses
    // Score is set to 0 (pending) as it requires manual teacher review
    $stmt = $conn->prepare("INSERT INTO level3_quiz (studentID, storyTitle, question, studentAnswer, score, attempt) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

    // Define default question texts for each story
    $defaultQuestions = [
        'story3-1' => [
            'story3-1_q1' => "Based on Tonya's actions, how does she feel about her wiggly tooth?",
            'story3-1_q2' => "Why do you think Tonya's tooth tilted and wiggled when she touched it?",
            'story3-1_q3' => "What do you think will happen next to Tonya's wiggly tooth?"
        ],
        'story3-2' => [
            'story3-2_q1' => "What do you think Rosa and Juan felt when they broke Lola Gloria's flowerpot?",
            'story3-2_q2' => "Why is it important to tell the truth even when you make a mistake?",
            'story3-2_q3' => "What would you do if you were Rosa or Juan in this situation?"
        ],
        'story3-3' => [
            'story3-3_q1' => "How do you think Liloy felt when he couldn't find Lingling?",
            'story3-3_q2' => "Why is it important to take care of our pets?",
            'story3-3_q3' => "What would you do to help Liloy find his dog?"
        ]
    ];

    foreach ($answers as $qid => $studentAnswer) {
        // Get question text from payload or use default
        $questionText = '';
        if (isset($questions[$qid]) && isset($questions[$qid]['text'])) {
            $questionText = $conn->real_escape_string($questions[$qid]['text']);
        } else if (isset($defaultQuestions[$storyKey]) && isset($defaultQuestions[$storyKey][$qid])) {
            $questionText = $conn->real_escape_string($defaultQuestions[$storyKey][$qid]);
        } else {
            // Fallback to qid as question text
            $questionText = $conn->real_escape_string($qid);
        }

        $studentAnswerEsc = $conn->real_escape_string($studentAnswer);
        
        // Score is 0 (pending manual review by teacher)
        $score = 0;
        $aNum = $attemptNum + 1; // attempt number: retakeCount + 1

        $stmt->bind_param("issiii", $student_id, $storyTitle, $questionText, $studentAnswerEsc, $score, $aNum);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
    }

    $stmt->close();
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Quiz submitted successfully. Pending teacher review.']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save quiz: ' . $e->getMessage()]);
} finally {
    $conn->close();
}

?>
