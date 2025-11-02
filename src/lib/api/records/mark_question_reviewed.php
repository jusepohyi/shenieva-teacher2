<?php
// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Use centralized conn.php variables and create PDO
require_once __DIR__ . '/../conn.php';
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $servername, $database);
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$taken_quiz_id = $data['taken_quiz_id'] ?? null;
$is_final = $data['is_final'] ?? null;

if (!$taken_quiz_id || $is_final !== 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $query = "UPDATE story1_taken_quizzes SET is_final = :is_final WHERE taken_quiz_id = :taken_quiz_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['is_final' => $is_final, 'taken_quiz_id' => $taken_quiz_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}
?>