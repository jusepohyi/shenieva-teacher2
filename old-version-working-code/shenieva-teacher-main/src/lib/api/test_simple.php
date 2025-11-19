<?php
// Simple test to verify basic functionality
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/apache/logs/php_error.log');

try {
    // Step 1: Include database connection
    error_log("TEST: Including conn.php");
    include 'conn.php';
    
    if (!isset($conn)) {
        throw new Exception("conn.php did not create \$conn variable");
    }
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    error_log("TEST: Database connected successfully");
    
    // Step 2: Check if table exists
    error_log("TEST: Checking if level2_quiz table exists");
    $tableCheck = $conn->query("SHOW TABLES LIKE 'level2_quiz'");
    if ($tableCheck->num_rows === 0) {
        throw new Exception("Table level2_quiz does not exist");
    }
    error_log("TEST: Table exists");
    
    // Step 3: Get table structure
    error_log("TEST: Getting table structure");
    $struct = $conn->query("DESCRIBE level2_quiz");
    $columns = [];
    while ($row = $struct->fetch_assoc()) {
        $columns[] = $row['Field'] . ' (' . $row['Type'] . ')';
    }
    error_log("TEST: Columns: " . implode(', ', $columns));
    
    // Step 4: Try to prepare statement
    error_log("TEST: Preparing INSERT statement");
    $stmt = $conn->prepare("INSERT INTO level2_quiz (studentID, storyTitle, question, correctAnswer, selectedAnswer, score, attempt) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    error_log("TEST: Prepare successful");
    
    // Step 5: Try a test insert
    $testData = [
        'studentID' => 1,
        'storyTitle' => 'Test Story',
        'question' => 'Test Question',
        'correctAnswer' => 'Test Correct Answer',
        'selectedAnswer' => 'Test Selected Answer',
        'score' => 1,
        'attempt' => 1
    ];
    
    error_log("TEST: Binding parameters");
    $stmt->bind_param("issssii", 
        $testData['studentID'],
        $testData['storyTitle'],
        $testData['question'],
        $testData['correctAnswer'],
        $testData['selectedAnswer'],
        $testData['score'],
        $testData['attempt']
    );
    
    error_log("TEST: Executing insert");
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $insertId = $stmt->insert_id;
    error_log("TEST: Insert successful, ID: " . $insertId);
    
    // Clean up test data
    $conn->query("DELETE FROM level2_quiz WHERE quizID = $insertId");
    error_log("TEST: Test data cleaned up");
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'All tests passed!',
        'table_columns' => $columns,
        'test_insert_id' => $insertId
    ]);
    
} catch (Exception $e) {
    error_log("TEST ERROR: " . $e->getMessage());
    error_log("TEST ERROR TRACE: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
