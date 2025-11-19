<?php
// Script to check and create missing quiz tables
// Access this file directly in browser: http://localhost/shenieva-teacher/src/lib/api/check_and_create_tables.php

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Database Table Checker</h1>";

include 'conn.php';

if (!isset($conn) || $conn->connect_error) {
    die("<p style='color:red'>Database connection failed: " . ($conn->connect_error ?? 'Unknown error') . "</p>");
}

echo "<p style='color:green'>✓ Database connection successful!</p>";

// Check and create level1_quiz table
echo "<h2>Checking level1_quiz table...</h2>";
$result = $conn->query("SHOW TABLES LIKE 'level1_quiz'");
if ($result->num_rows > 0) {
    echo "<p style='color:green'>✓ level1_quiz table exists</p>";
} else {
    echo "<p style='color:orange'>⚠ level1_quiz table does not exist. Creating...</p>";
    $sql = "CREATE TABLE `level1_quiz` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `studentID` int(11) NOT NULL,
      `storyTitle` varchar(255) NOT NULL,
      `question` text NOT NULL,
      `choiceA` text DEFAULT NULL,
      `choiceB` text DEFAULT NULL,
      `choiceC` text DEFAULT NULL,
      `choiceD` text DEFAULT NULL,
      `correctAnswer` varchar(10) DEFAULT NULL,
      `selectedAnswer` varchar(10) DEFAULT NULL,
      `score` int(11) DEFAULT 0,
      `attempt` int(11) DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `studentID` (`studentID`),
      KEY `storyTitle` (`storyTitle`),
      KEY `attempt` (`attempt`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>✓ level1_quiz table created successfully!</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating level1_quiz table: " . $conn->error . "</p>";
    }
}

// Check and create level2_quiz table
echo "<h2>Checking level2_quiz table...</h2>";
$result = $conn->query("SHOW TABLES LIKE 'level2_quiz'");
if ($result->num_rows > 0) {
    echo "<p style='color:green'>✓ level2_quiz table exists</p>";
} else {
    echo "<p style='color:orange'>⚠ level2_quiz table does not exist. Creating...</p>";
    $sql = "CREATE TABLE `level2_quiz` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `studentID` int(11) NOT NULL,
      `storyTitle` varchar(255) NOT NULL,
      `question` text NOT NULL,
      `correctAnswer` text DEFAULT NULL,
      `selectedAnswer` text DEFAULT NULL,
      `score` int(11) DEFAULT 0,
      `attempt` int(11) DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `studentID` (`studentID`),
      KEY `storyTitle` (`storyTitle`),
      KEY `attempt` (`attempt`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>✓ level2_quiz table created successfully!</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating level2_quiz table: " . $conn->error . "</p>";
    }
}

// Check and create level3_quiz table
echo "<h2>Checking level3_quiz table...</h2>";
$result = $conn->query("SHOW TABLES LIKE 'level3_quiz'");
if ($result->num_rows > 0) {
    echo "<p style='color:green'>✓ level3_quiz table exists</p>";
} else {
    echo "<p style='color:orange'>⚠ level3_quiz table does not exist. Creating...</p>";
    $sql = "CREATE TABLE `level3_quiz` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `studentID` int(11) NOT NULL,
      `storyTitle` varchar(255) NOT NULL,
      `question` text NOT NULL,
      `correctAnswer` text DEFAULT NULL,
      `selectedAnswer` text DEFAULT NULL,
      `score` int(11) DEFAULT 0,
      `attempt` int(11) DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `studentID` (`studentID`),
      KEY `storyTitle` (`storyTitle`),
      KEY `attempt` (`attempt`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>✓ level3_quiz table created successfully!</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating level3_quiz table: " . $conn->error . "</p>";
    }
}

echo "<h2>Summary</h2>";
echo "<p>All required tables have been checked and created if necessary.</p>";
echo "<p><a href='javascript:history.back()'>← Go Back</a> | <a href='javascript:location.reload()'>↻ Refresh</a></p>";

$conn->close();
?>
