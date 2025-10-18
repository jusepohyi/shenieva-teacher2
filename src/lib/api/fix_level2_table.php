<?php
// Script to fix level2_quiz table column types
// Access: http://localhost/shenieva-teacher/src/lib/api/fix_level2_table.php

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Fix level2_quiz Table</h1>";

include 'conn.php';

if (!isset($conn) || $conn->connect_error) {
    die("<p style='color:red'>Database connection failed: " . ($conn->connect_error ?? 'Unknown error') . "</p>");
}

echo "<p style='color:green'>✓ Database connection successful!</p>";

echo "<h2>Current Table Structure</h2>";
$result = $conn->query("DESCRIBE level2_quiz");
if ($result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Error: " . $conn->error . "</p>";
}

echo "<h2>Fixing Column Types...</h2>";

// Change correctAnswer from varchar(255) to TEXT
echo "<h3>1. Changing correctAnswer from VARCHAR(255) to TEXT</h3>";
$sql1 = "ALTER TABLE level2_quiz MODIFY COLUMN correctAnswer TEXT";
if ($conn->query($sql1) === TRUE) {
    echo "<p style='color:green'>✓ correctAnswer column updated to TEXT</p>";
} else {
    echo "<p style='color:red'>✗ Error: " . $conn->error . "</p>";
}

// Change selectedAnswer from varchar(255) to TEXT
echo "<h3>2. Changing selectedAnswer from VARCHAR(255) to TEXT</h3>";
$sql2 = "ALTER TABLE level2_quiz MODIFY COLUMN selectedAnswer TEXT";
if ($conn->query($sql2) === TRUE) {
    echo "<p style='color:green'>✓ selectedAnswer column updated to TEXT</p>";
} else {
    echo "<p style='color:red'>✗ Error: " . $conn->error . "</p>";
}

echo "<h2>Updated Table Structure</h2>";
$result = $conn->query("DESCRIBE level2_quiz");
if ($result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        $isChanged = ($row['Field'] === 'correctAnswer' || $row['Field'] === 'selectedAnswer');
        $style = $isChanged ? " style='background-color: #90EE90;'" : "";
        echo "<td$style>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td$style>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td$style>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td$style>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td$style>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td$style>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color:green; font-weight:bold;'>✓ Table structure has been updated! (Changed columns highlighted in green)</p>";
} else {
    echo "<p style='color:red'>Error: " . $conn->error . "</p>";
}

echo "<h2>✅ All Done!</h2>";
echo "<p>The level2_quiz table has been updated. You can now save quiz submissions without truncation.</p>";
echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";

$conn->close();
?>
