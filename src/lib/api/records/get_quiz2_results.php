<?php
// Error reporting
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Use central connection variables and create a PDO instance
require_once __DIR__ . '/../conn.php';
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $servername, $database);
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// Fetch and update query
$query = "
    SELECT 
        stq.taken_quiz_id,
        stq.student_id,
        s.studentName AS name,
        s.studentGender AS gender,
        stq.attempt,
        stq.question,
        stq.answer AS student_answer,
        q.answer AS correct_answer,
        stq.is_final,
        stq.is_correct
    FROM story2_taken_quizzes stq
    JOIN students_table s ON stq.student_id = s.pk_studentID
    LEFT JOIN quizzes_story2 q ON TRIM(LOWER(q.question)) = TRIM(LOWER(stq.question))
    ORDER BY stq.student_id, stq.attempt, stq.taken_quiz_id
";

try {
    $stmt = $pdo->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = [];

    foreach ($rows as $row) {
        $key = $row['student_id'] . '-' . $row['attempt'];

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'student_id' => $row['student_id'],
                'name' => $row['name'],
                'gender' => $row['gender'],
                'datetime' => date('Y-m-d H:i'),
                'score' => 0,
                'attempts' => $row['attempt'],
                'status' => $row['is_final'] ? 'Reviewed' : 'Pending',
                'questions' => []
            ];
        }

        $isCorrect = 0;
        if (
            isset($row['student_answer'], $row['correct_answer']) &&
            trim(strtolower($row['student_answer'])) === trim(strtolower($row['correct_answer']))
        ) {
            $isCorrect = 1;
            $grouped[$key]['score'] += 1;
        }

        // Only update if different from what's in the DB
        if ((int)$row['is_correct'] !== $isCorrect) {
            $updateStmt = $pdo->prepare("UPDATE story2_taken_quizzes SET is_correct = :is_correct WHERE taken_quiz_id = :id");
            $updateStmt->execute([
                ':is_correct' => $isCorrect,
                ':id' => $row['taken_quiz_id']
            ]);
        }

        $grouped[$key]['questions'][] = [
            'text' => $row['question'],
            'chosenAnswer' => $row['student_answer'],
            'correctAnswer' => $row['correct_answer'],
            'scored' => $isCorrect === 1,
            'is_final' => $row['is_final'],
            'taken_quiz_id' => $row['taken_quiz_id']
        ];
    }

    echo json_encode(array_values($grouped), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}
?>
