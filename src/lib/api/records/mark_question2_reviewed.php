<?php
// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight (OPTIONS) request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// Use central conn.php variables and create PDO
require_once __DIR__ . '/../conn.php';
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $servername, $database);
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Parse JSON payload
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['taken_quiz_id']) || !isset($data['is_final'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid parameters']);
    exit;
}

$takenQuizId = (int)$data['taken_quiz_id'];
$isFinal = (int)$data['is_final'];

try {
    // First update is_final on this specific question
    $stmt = $pdo->prepare("UPDATE story2_taken_quizzes SET is_final = :is_final WHERE taken_quiz_id = :taken_quiz_id");
    $stmt->execute([
        ':is_final' => $isFinal,
        ':taken_quiz_id' => $takenQuizId
    ]);

    // Fetch student_id and attempt of this row to check for group completion
    $infoStmt = $pdo->prepare("SELECT student_id, attempt FROM story2_taken_quizzes WHERE taken_quiz_id = :taken_quiz_id");
    $infoStmt->execute([':taken_quiz_id' => $takenQuizId]);
    $quizInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);

    if ($quizInfo) {
        $studentId = $quizInfo['student_id'];
        $attempt = $quizInfo['attempt'];

        // Check if all questions for that student and attempt are reviewed
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) AS total, 
                   SUM(CASE WHEN is_final = 1 THEN 1 ELSE 0 END) AS reviewed 
            FROM story2_taken_quizzes 
            WHERE student_id = :student_id AND attempt = :attempt
        ");
        $checkStmt->execute([
            ':student_id' => $studentId,
            ':attempt' => $attempt
        ]);
        $status = $checkStmt->fetch(PDO::FETCH_ASSOC);

        $allReviewed = ($status['total'] > 0 && $status['total'] == $status['reviewed']);

        echo json_encode([
            'success' => true,
            'message' => 'Question marked as reviewed',
            'allReviewed' => $allReviewed
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Question reviewed, but could not verify full status (row not found)'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed: ' . $e->getMessage()]);
}
?>
