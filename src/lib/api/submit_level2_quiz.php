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
        'story2-1' => "Hector's Health",
        'story2-2' => 'Helpful Maya',
        'story2-3' => 'Royce Choice'
    ];
    $storyTitle = isset($map[$storyKey]) ? $conn->real_escape_string($map[$storyKey]) : $storyKey;
}
$attempt = $payload['attempt'];
$answers = isset($attempt['answers']) ? $attempt['answers'] : [];
$score = isset($attempt['score']) ? (int)$attempt['score'] : 0;
$clientAttemptNum = isset($attempt['retakeCount']) ? (int)$attempt['retakeCount'] : 0;

// Determine server-side attempt number by checking existing rows for this student & story
$existingMaxAttempt = 0;
try {
    $checkStmt = $conn->prepare("SELECT MAX(attempt) AS max_attempt FROM level2_quiz WHERE studentID = ? AND storyTitle = ?");
    if ($checkStmt) {
        $checkStmt->bind_param("is", $student_id, $storyTitle);
        if ($checkStmt->execute()) {
            $res = $checkStmt->get_result();
            if ($res) {
                $row = $res->fetch_assoc();
                $existingMaxAttempt = isset($row['max_attempt']) ? (int)$row['max_attempt'] : 0;
            }
        }
        $checkStmt->close();
    }
} catch (Exception $ex) {
    error_log('Failed to check existing attempts (level2): ' . $ex->getMessage());
}

// Server-side authoritative attempt number: prefer existing max + 1, else fallback to clientAttemptNum + 1
$aNum = ($existingMaxAttempt > 0) ? ($existingMaxAttempt + 1) : ($clientAttemptNum + 1);

// optional: correctAnswers mapping sent from client
$correctAnswers = isset($payload['correctAnswers']) ? $payload['correctAnswers'] : [];

// optional: questions metadata mapping sent from client (helps store a human-friendly question text)
$questions = isset($payload['questions']) ? $payload['questions'] : [];

// Log the data for debugging
error_log("=== Level 2 Quiz Submission START ===");
error_log("Student ID: $student_id, Story: $storyKey, Title: $storyTitle");
error_log("Answers received: " . print_r($answers, true));
error_log("Correct Answers received: " . print_r($correctAnswers, true));
error_log("Answers count: " . count($answers));
error_log("Correct Answers count: " . count($correctAnswers));

// Check if we have answers to save
if (empty($answers)) {
    error_log("ERROR: No answers provided in submission");
    echo json_encode(['success' => false, 'error' => 'No answers provided']);
    exit();
}

// Verify table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'level2_quiz'");
if ($tableCheck->num_rows === 0) {
    error_log("ERROR: level2_quiz table does not exist");
    echo json_encode(['success' => false, 'error' => 'Database table level2_quiz does not exist']);
    exit();
}
error_log("Table check: level2_quiz exists");

try {
    $conn->begin_transaction();
    error_log("Transaction started");

    // Level 2 uses drag-and-drop quizzes where answers are stored as JSON mappings
    // We need to iterate through each sub-question in the mapping
    // Note: quizID is auto_increment, createdAt has default value
    // Changed 'score' to 'point' - each question gets 0 or 1 point
    error_log("Preparing INSERT statement...");
    $stmt = $conn->prepare("INSERT INTO level2_quiz (studentID, storyTitle, question, correctAnswer, selectedAnswer, point, attempt) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("PREPARE FAILED: " . $conn->error);
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    error_log("Prepare successful");

    $rowsInserted = 0;
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

        error_log("Processing question: $qid, selectedMap: " . print_r($selectedMap, true) . ", correctMap: " . print_r($correctMap, true));

        // If we have mappings, insert a row for each sub-question
        if ($selectedMap && $correctMap && is_array($selectedMap) && is_array($correctMap)) {
            foreach ($correctMap as $subKey => $correctVal) {
                // Prefer a human-friendly question text if provided by the client
                $questionText = '';
                if (isset($questions[$qid])) {
                    $qMeta = $questions[$qid];
                    // If question metadata is an array and contains subitems
                    if (is_array($qMeta) && isset($qMeta[$subKey])) {
                        if (is_array($qMeta[$subKey]) && isset($qMeta[$subKey]['text'])) {
                            $questionText = $conn->real_escape_string($qMeta[$subKey]['text']);
                        } else if (is_string($qMeta[$subKey])) {
                            $questionText = $conn->real_escape_string($qMeta[$subKey]);
                        }
                    } else if (is_array($qMeta) && isset($qMeta['text'])) {
                        $questionText = $conn->real_escape_string($qMeta['text']);
                    } else if (is_string($qMeta)) {
                        $questionText = $conn->real_escape_string($qMeta);
                    }
                }

                // Fallback: use qid and subKey to identify the saved row (do NOT use the correct answer as the question)
                if (empty($questionText)) {
                    $questionText = $conn->real_escape_string($qid . ' - part ' . $subKey);
                }

                $correctAnswer = $conn->real_escape_string($correctVal);
                $selectedAnswer = isset($selectedMap[$subKey]) ? $conn->real_escape_string($selectedMap[$subKey]) : '';
                
                // Check if data will fit in varchar(255) columns and truncate if necessary
                if (strlen($questionText) > 255 || strlen($correctAnswer) > 255 || strlen($selectedAnswer) > 255) {
                    error_log("WARNING: Data too long for varchar(255) - questionText: " . strlen($questionText) . ", correctAnswer: " . strlen($correctAnswer) . ", selectedAnswer: " . strlen($selectedAnswer));
                    // Truncate to fit in database
                    $questionText = substr($questionText, 0, 255);
                    $correctAnswer = substr($correctAnswer, 0, 255);
                    $selectedAnswer = substr($selectedAnswer, 0, 255);
                }
                
                // Calculate point for this specific sub-question: 1 if correct, 0 if wrong
                $point = ((string)($selectedMap[$subKey] ?? '') === (string)$correctVal) ? 1 : 0;

                error_log("Inserting: studentID=$student_id, storyTitle=$storyTitle, question=" . substr($questionText, 0, 80) . "..., point=$point, attempt=$aNum");
                
                $stmt->bind_param("issssii", $student_id, $storyTitle, $questionText, $correctAnswer, $selectedAnswer, $point, $aNum);
                if (!$stmt->execute()) {
                    error_log("SQL Execute Error: " . $stmt->error);
                    throw new Exception('Execute failed: ' . $stmt->error);
                }
                $rowsInserted++;
            }
        } else {
            // Fallback: treat as single question/answer
            error_log("Using fallback single question/answer for $qid");
            $questionText = $conn->real_escape_string($qid);
            $correctAnswer = is_string($correctRaw) ? $conn->real_escape_string($correctRaw) : '';
            $selectedAnswer = is_string($selectedRaw) ? $conn->real_escape_string($selectedRaw) : '';
                $point = ($selectedAnswer === $correctAnswer) ? 1 : 0;

            $stmt->bind_param("issssii", $student_id, $storyTitle, $questionText, $correctAnswer, $selectedAnswer, $point, $aNum);
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            $rowsInserted++;
        }
    }

    $stmt->close();
    
    error_log("Total rows inserted: $rowsInserted");
    
    if ($rowsInserted === 0) {
        throw new Exception('No quiz data was saved - answers array may be empty or in wrong format');
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'rows_inserted' => $rowsInserted]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("EXCEPTION in submit_level2_quiz: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save quiz: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

?>
