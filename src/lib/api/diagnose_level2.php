<?php
// Diagnostic script for Level 2 quiz submission
// Access: http://localhost/shenieva-teacher/src/lib/api/diagnose_level2.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Level 2 Quiz Diagnostic</h1>";
echo "<pre>";

echo "=== 1. Testing Database Connection ===\n";
include 'conn.php';

if (!isset($conn)) {
    echo "❌ ERROR: \$conn variable not set after including conn.php\n";
    die();
}

if ($conn->connect_error) {
    echo "❌ ERROR: Database connection failed: " . $conn->connect_error . "\n";
    die();
}

echo "✅ Database connection successful\n";
echo "   Database: shenieva_DB\n\n";

echo "=== 2. Checking level2_quiz Table ===\n";
$result = $conn->query("SHOW TABLES LIKE 'level2_quiz'");
if ($result->num_rows === 0) {
    echo "❌ ERROR: level2_quiz table does NOT exist\n";
    echo "   You need to create this table first!\n";
} else {
    echo "✅ level2_quiz table exists\n\n";
    
    echo "=== 3. Table Structure ===\n";
    $struct = $conn->query("DESCRIBE level2_quiz");
    if ($struct) {
        echo str_pad("Field", 20) . str_pad("Type", 20) . str_pad("Null", 10) . str_pad("Key", 10) . "Default\n";
        echo str_repeat("-", 80) . "\n";
        while ($row = $struct->fetch_assoc()) {
            echo str_pad($row['Field'], 20) . 
                 str_pad($row['Type'], 20) . 
                 str_pad($row['Null'], 10) . 
                 str_pad($row['Key'], 10) . 
                 $row['Default'] . "\n";
            
            // Check for problematic VARCHAR(255) columns
            if (($row['Field'] === 'correctAnswer' || $row['Field'] === 'selectedAnswer') && 
                strpos($row['Type'], 'varchar(255)') !== false) {
                echo "   ⚠️  WARNING: " . $row['Field'] . " is VARCHAR(255) - should be TEXT for long answers!\n";
            }
        }
    }
}

echo "\n=== 4. Testing INSERT Statement ===\n";
$testStmt = $conn->prepare("INSERT INTO level2_quiz (studentID, storyTitle, question, correctAnswer, selectedAnswer, score, attempt) VALUES (?, ?, ?, ?, ?, ?, ?)");
if ($testStmt) {
    echo "✅ Prepare statement successful\n";
    echo "   The SQL syntax is correct\n";
    $testStmt->close();
} else {
    echo "❌ ERROR: Prepare statement failed\n";
    echo "   Error: " . $conn->error . "\n";
}

echo "\n=== 5. Simulating Data Insert ===\n";
$testStudentID = 1;
$testStoryTitle = "Test Story";
$testQuestion = "Test Question";
$testCorrectAnswer = "Test correct answer (short)";
$testSelectedAnswer = "Test selected answer (short)";
$testScore = 1;
$testAttempt = 1;

$stmt = $conn->prepare("INSERT INTO level2_quiz (studentID, storyTitle, question, correctAnswer, selectedAnswer, score, attempt) VALUES (?, ?, ?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("issssii", $testStudentID, $testStoryTitle, $testQuestion, $testCorrectAnswer, $testSelectedAnswer, $testScore, $testAttempt);
    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        echo "✅ Test insert successful!\n";
        echo "   Inserted ID: $insertId\n";
        
        // Clean up test data
        $conn->query("DELETE FROM level2_quiz WHERE quizID = $insertId");
        echo "   (Test data cleaned up)\n";
    } else {
        echo "❌ ERROR: Test insert failed\n";
        echo "   Error: " . $stmt->error . "\n";
    }
    $stmt->close();
}

echo "\n=== 6. Testing Long Text Insert ===\n";
$longText = str_repeat("This is a very long answer that exceeds 255 characters. ", 10);
echo "   Test text length: " . strlen($longText) . " characters\n";

$stmt = $conn->prepare("INSERT INTO level2_quiz (studentID, storyTitle, question, correctAnswer, selectedAnswer, score, attempt) VALUES (?, ?, ?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("issssii", $testStudentID, $testStoryTitle, $testQuestion, $longText, $longText, $testScore, $testAttempt);
    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        echo "✅ Long text insert successful!\n";
        echo "   Your columns can handle long text\n";
        
        // Clean up
        $conn->query("DELETE FROM level2_quiz WHERE quizID = $insertId");
    } else {
        echo "❌ ERROR: Long text insert failed\n";
        echo "   Error: " . $stmt->error . "\n";
        echo "   ⚠️  Your correctAnswer/selectedAnswer columns are likely VARCHAR(255)\n";
        echo "   ⚠️  Run the fix script to change them to TEXT type\n";
    }
    $stmt->close();
}

echo "\n=== 7. Checking PHP Error Log ===\n";
$errorLogPath = 'C:\\xampp\\apache\\logs\\error.log';
if (file_exists($errorLogPath)) {
    echo "✅ Error log found at: $errorLogPath\n";
    echo "   Last 10 lines:\n";
    $lines = file($errorLogPath);
    $lastLines = array_slice($lines, -10);
    foreach ($lastLines as $line) {
        if (stripos($line, 'level2') !== false || stripos($line, 'quiz') !== false) {
            echo "   >> " . trim($line) . "\n";
        }
    }
} else {
    echo "⚠️  Error log not found at default location\n";
}

echo "\n=== Summary ===\n";
echo "If you see any ❌ errors above, those need to be fixed.\n";
echo "If correctAnswer/selectedAnswer are VARCHAR(255), run the fix script:\n";
echo "http://localhost/shenieva-teacher/src/lib/api/fix_level2_table.php\n";

echo "</pre>";
$conn->close();
?>
