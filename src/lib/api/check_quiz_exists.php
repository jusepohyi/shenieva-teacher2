<?php
// Check if a student has already submitted a final quiz for a specific story
// Prevents duplicate ribbon claims and quiz submissions

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'conn.php';

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Get parameters from GET or POST
$student_id = null;
$story_key = null;
$level = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
    $story_key = isset($_GET['story_key']) ? $_GET['story_key'] : null;
    $level = isset($_GET['level']) ? (int)$_GET['level'] : null;
} else {
    $data = json_decode(file_get_contents('php://input'), true);
    $student_id = isset($data['student_id']) ? (int)$data['student_id'] : null;
    $story_key = isset($data['story_key']) ? $data['story_key'] : null;
    $level = isset($data['level']) ? (int)$data['level'] : null;
}

if (!$student_id || !$story_key || !$level) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters: student_id, story_key, level']);
    exit();
}

// Determine which table to check based on level
$table_name = '';
$story_title_col = 'storyTitle';

if ($level === 1) {
    $table_name = 'level1_quiz';
} else if ($level === 2) {
    $table_name = 'level2_quiz';
} else if ($level === 3) {
    $table_name = 'level3_quiz';
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid level. Must be 1, 2, or 3']);
    exit();
}

// Story key to title mapping for verification
$story_titles = [
    'story1-1' => "Maria's Promise",
    'story1-2' => 'Candice and Candies',
    'story1-3' => 'Hannah, the Honest Vendor',
    'story2-1' => "Hector's Health",
    'story2-2' => 'Helpful Maya',
    'story2-3' => 'Royce Choice',
    'story3-1' => "Tonya's Tooth",
    'story3-2' => "Lola Gloria's Flowerpot",
    'story3-3' => "Liloy and Lingling The Dog"
];

$story_title = isset($story_titles[$story_key]) ? $story_titles[$story_key] : null;

try {
    // Check if quiz exists for this student in this LEVEL (regardless of story)
    // Once a student has submitted ANY quiz in a level, they cannot claim ribbons again
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table_name WHERE studentID = ?");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $student_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $exists = $row['count'] > 0;
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'exists' => $exists,
        'student_id' => $student_id,
        'level' => $level,
        'table' => $table_name,
        'message' => $exists 
            ? "Student has already submitted a quiz in Level $level" 
            : "No quiz found for student in Level $level"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
    if (isset($conn)) {
        $conn->close();
    }
}
?>
