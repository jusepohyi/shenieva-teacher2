<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Centralized CORS handling
include_once __DIR__ . '/cors.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$studentId = $data['studentID'] ?? ($_GET['studentID'] ?? '');

if (!$studentId) {
    echo json_encode(['error' => 'Student ID is required']);
    exit();
}

// Database Connection - reuse conn.php variables and create a PDO instance
try {
    require_once __DIR__ . '/conn.php';
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $servername, $database);
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

try {
    $query = "SELECT studentName, attendanceDateTime FROM attendance_table WHERE fk_studentID = :studentID ORDER BY attendanceDateTime DESC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':studentID', $studentId, PDO::PARAM_INT);
    $stmt->execute();

    $attendanceData = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = new DateTime($row['attendanceDateTime']);
        $row['attendanceDateTime'] = $date->format('Y-m-d g:i A'); // Format before sending to frontend
        $attendanceData[] = $row;
    }

    if (empty($attendanceData)) {
        echo json_encode(['error' => 'No records found for the provided Student ID']);
    } else {
        echo json_encode($attendanceData);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
