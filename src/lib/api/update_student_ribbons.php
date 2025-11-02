<?php
// Suppress error display to prevent HTML output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Centralized CORS handling
include_once __DIR__ . '/cors.php';

header('Content-Type: application/json');

include 'conn.php';

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error')]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['student_id']) || !isset($data['ribbons'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit();
}

$student_id = (int)$data['student_id'];
$ribbons = (int)$data['ribbons'];

try {
    $conn->begin_transaction();

    // Update studentRibbon by adding the new ribbons
    $stmt = $conn->prepare("
        UPDATE students_table 
        SET studentRibbon = COALESCE(studentRibbon, 0) + ? 
        WHERE pk_studentID = ?
    ");
    $stmt->bind_param("ii", $ribbons, $student_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("No rows updated. Student ID may not exist.");
    }

    $stmt->close();
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update ribbons: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>