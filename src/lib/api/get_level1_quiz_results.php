<?php
// Allow CORS for local dev (adjust origin for production)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // You can restrict this to a specific origin instead of '*'
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// respond to preflight OPTIONS request and exit early
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["success" => false, "error" => "Invalid request method"]);
    exit;
}

$storyTitle = isset($_GET['storyTitle']) && $_GET['storyTitle'] !== '' ? $_GET['storyTitle'] : null;
$studentID = isset($_GET['studentID']) && $_GET['studentID'] !== '' ? intval($_GET['studentID']) : null;

$results = [];
try {
    if ($storyTitle) {
        $sql = "SELECT 
                    lq.quizID,
                    lq.studentID,
                    s.studentName,
                    s.idNo,
                    lq.storyTitle,
                    lq.question,
                    lq.choiceA,
                    lq.choiceB,
                    lq.choiceC,
                    lq.choiceD,
                    lq.correctAnswer,
                    lq.selectedAnswer,
                    lq.point as score,
                    lq.attempt,
                    lq.createdAt
                FROM level1_quiz lq
                JOIN students_table s ON lq.studentID = s.pk_studentID
                WHERE lq.storyTitle = ?
                ORDER BY s.studentName, lq.attempt DESC, lq.createdAt DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $storyTitle);
    } elseif ($studentID) {
        $sql = "SELECT 
                    lq.quizID,
                    lq.studentID,
                    s.studentName,
                    s.idNo,
                    lq.storyTitle,
                    lq.question,
                    lq.choiceA,
                    lq.choiceB,
                    lq.choiceC,
                    lq.choiceD,
                    lq.correctAnswer,
                    lq.selectedAnswer,
                    lq.point as score,
                    lq.attempt,
                    lq.createdAt
                FROM level1_quiz lq
                JOIN students_table s ON lq.studentID = s.pk_studentID
                WHERE lq.studentID = ?
                ORDER BY lq.attempt DESC, lq.createdAt DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $studentID);
    } else {
        $sql = "SELECT 
                    lq.quizID,
                    lq.studentID,
                    s.studentName,
                    s.idNo,
                    lq.storyTitle,
                    lq.question,
                    lq.choiceA,
                    lq.choiceB,
                    lq.choiceC,
                    lq.choiceD,
                    lq.correctAnswer,
                    lq.selectedAnswer,
                    lq.point as score,
                    lq.attempt,
                    lq.createdAt
                FROM level1_quiz lq
                JOIN students_table s ON lq.studentID = s.pk_studentID
                ORDER BY s.studentName, lq.storyTitle, lq.attempt DESC, lq.createdAt DESC";
        $stmt = $conn->prepare($sql);
    }

    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $results[] = $row;
        }
    } else {
        // fallback for environments without mysqlnd
        $meta = $stmt->result_metadata();
        if ($meta) {
            $fields = [];
            $row = [];
            $bindParams = [];
            while ($field = $meta->fetch_field()) {
                $fields[] = &$row[$field->name];
                $bindParams[] = $row[$field->name];
            }
            call_user_func_array([$stmt, 'bind_result'], $fields);
            while ($stmt->fetch()) {
                $record = [];
                foreach ($row as $k => $v) $record[$k] = $v;
                $results[] = $record;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results)
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    if (isset($stmt) && $stmt) $stmt->close();
    if (isset($conn) && $conn) $conn->close();
}
