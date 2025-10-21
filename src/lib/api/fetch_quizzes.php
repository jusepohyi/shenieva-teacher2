<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$database = "shenieva_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

$story = isset($_GET['story']) ? $_GET['story'] : 'story1';

$allowedStories = ['story1', 'story2', 'story3'];
if (!in_array($story, $allowedStories)) {
    echo json_encode(["error" => "Invalid story selection"]);
    exit();
}

$tableName = "quizzes_{$story}";
// Ensure the quiz table exists before querying to avoid fatal errors when the DB/table
// isn't present on the developer machine. If the table doesn't exist, return an
// empty JSON array so the front-end can handle it gracefully.
$escapedTable = $conn->real_escape_string($tableName);
$check = $conn->query("SHOW TABLES LIKE '$escapedTable'");
if (!$check || $check->num_rows === 0) {
    echo json_encode([]);
    exit();
}

// Build SQL per story, but guard for the choices table when needed.
$quizzes = [];
if ($story === 'story3') {
    $sql = "SELECT id, points, question FROM $tableName";
} elseif ($story === 'story2') {
    $sql = "SELECT id, question, answer, points FROM $tableName";
} else {
    $choicesTable = "choices_{$story}";
    // Verify choices table exists too — if it doesn't exist return empty list.
    $escapedChoices = $conn->real_escape_string($choicesTable);
    $checkChoices = $conn->query("SHOW TABLES LIKE '$escapedChoices'");
    if (!$checkChoices || $checkChoices->num_rows === 0) {
        echo json_encode([]);
        exit();
    }

    $sql = "SELECT q.id, q.question, q.answer, q.points, 
                   GROUP_CONCAT(c.choice_text) AS choices
            FROM $tableName q
            JOIN $choicesTable c ON q.id = c.quiz_id
            GROUP BY q.id";
}

$result = $conn->query($sql);
if (!$result) {
    // Query failed for some reason (e.g. malformed schema) — return empty array
    // instead of letting PHP throw an exception.
    echo json_encode([]);
    exit();
}

while ($row = $result->fetch_assoc()) {
    if ($story === 'story1' && isset($row['choices'])) {
        $row['choices'] = explode(',', $row['choices']);
    }
    $row['points'] = isset($row['points']) ? (int)$row['points'] : null;
    $quizzes[] = $row;
}

echo json_encode($quizzes);
?>
