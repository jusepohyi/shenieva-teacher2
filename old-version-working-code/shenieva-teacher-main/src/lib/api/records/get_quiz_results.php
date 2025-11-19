<?php
// Enable error reporting for debugging, but log instead of display
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Use central connection variables from conn.php and create a PDO instance
require_once __DIR__ . '/../conn.php';

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $servername, $database);
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Query to fetch Quiz 1 results with choices
$query = "
    SELECT 
        stq.taken_quiz_id,
        stq.student_id,
        s.studentName AS name,
        s.studentGender AS gender,
        stq.question,
        stq.choices AS selected_choices,
        stq.is_correct,
        stq.points,
        stq.attempt,
        stq.is_final,
        stq.store,
        stq.item_number,
        stq.quiz_id,
        GROUP_CONCAT(
            JSON_OBJECT(
                'id', c.id,
                'text', c.choice_text,
                'is_correct', c.is_correct
            )
        ) AS choice_data
    FROM story1_taken_quizzes stq
    JOIN students_table s ON stq.student_id = s.pk_studentID
    LEFT JOIN quizzes_story1 q ON stq.quiz_id = q.id
    LEFT JOIN choices_story1 c ON q.id = c.quiz_id
    WHERE stq.store = 1
    GROUP BY stq.taken_quiz_id
    ORDER BY stq.student_id, stq.store, stq.attempt, stq.item_number
";

try {
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by student_id, store, and attempt
    $groupedResults = [];
    foreach ($results as $row) {
        $key = $row['student_id'] . '-' . $row['store'] . '-' . $row['attempt'];
        if (!isset($groupedResults[$key])) {
            $groupedResults[$key] = [
                'taken_quiz_id' => $row['taken_quiz_id'],
                'student_id' => $row['student_id'],
                'name' => $row['name'],
                'gender' => $row['gender'],
                'datetime' => date('Y-m-d H:i'), // Placeholder; no quizDateTime in schema
                'score' => 0,
                'attempts' => $row['attempt'],
                'status' => $row['is_final'] ? 'Reviewed' : 'Pending',
                'questions' => []
            ];
        }

        // Parse choices from choices_story1
        $choices = $row['choice_data'] ? json_decode('[' . $row['choice_data'] . ']', true) : [];
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Invalid JSON in choice_data for taken_quiz_id ' . $row['taken_quiz_id'] . ': ' . json_last_error_msg());
            $choices = [];
        }

        // Parse selected choices from story1_taken_quizzes
        $selectedChoices = [];
        if (!empty($row['selected_choices'])) {
            $decoded = json_decode($row['selected_choices'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $selectedChoices = $decoded;
            } else {
                error_log('Invalid JSON in selected_choices for taken_quiz_id ' . $row['taken_quiz_id'] . ': ' . json_last_error_msg());
                $selectedChoices = [['text' => $row['selected_choices']]];
            }
        }

        $chosenAnswer = null;
        $correctAnswer = null;
        $choiceMap = [];

        // Build choices map and determine answers
        foreach ($choices as $index => $choice) {
            $letter = chr(97 + $index); // a, b, c, d
            $choiceMap[$letter] = $choice['text'];
            if ($choice['is_correct']) {
                $correctAnswer = $choice['text'];
            }
        }

        // Determine chosenAnswer using is_correct and choices JSON
        if ($selectedChoices) {
            foreach ($selectedChoices as $selected) {
                foreach ($choices as $choice) {
                    if (isset($selected['is_correct']) && $selected['is_correct'] == $choice['is_correct'] && $selected['text'] == $choice['text']) {
                        $chosenAnswer = $choice['text'];
                        break 2;
                    }
                }
            }
        }

        // Fallback: Use is_correct to infer chosenAnswer
        if (!$chosenAnswer && $choices) {
            if ($row['is_correct']) {
                foreach ($choices as $choice) {
                    if ($choice['is_correct']) {
                        $chosenAnswer = $choice['text'];
                        break;
                    }
                }
            } else {
                foreach ($choices as $choice) {
                    if (!$choice['is_correct']) {
                        $chosenAnswer = $choice['text'];
                        break;
                    }
                }
            }
        }

        $groupedResults[$key]['questions'][] = [
            'text' => $row['question'],
            'choices' => count($choices) > 0 ? $choiceMap : [],
            'chosenAnswer' => $chosenAnswer,
            'correctAnswer' => $correctAnswer,
            'scored' => $row['is_correct'] ? true : false,
            'quiz_id' => $row['quiz_id'],
            'is_final' => $row['is_final'],
            'taken_quiz_id' => $row['taken_quiz_id']
        ];
        $groupedResults[$key]['score'] += $row['points'] ?? 0;
    }

    $formattedResults = array_values($groupedResults);
    echo json_encode($formattedResults, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}
?>