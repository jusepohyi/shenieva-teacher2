<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conn.php';

$studentID = null;
if (isset($_GET['studentID'])) {
    $studentID = trim($_GET['studentID']);
}
// allow idNo fallback
$idNo = null;
if (isset($_GET['idNo'])) {
    $idNo = trim($_GET['idNo']);
}

if (!$studentID && !$idNo) {
    echo json_encode(['success' => false, 'error' => 'Missing student identifier (studentID or idNo)']);
    exit();
}

try {
    if ($studentID) {
        $stmt = $conn->prepare('SELECT pk_studentID, idNo, studentName, studentGender, studentLevel, studentRibbon, studentColtrash FROM students_table WHERE pk_studentID = ? LIMIT 1');
        $stmt->bind_param('i', $studentID);
    } else {
        $stmt = $conn->prepare('SELECT pk_studentID, idNo, studentName, studentGender, studentLevel, studentRibbon, studentColtrash FROM students_table WHERE idNo = ? LIMIT 1');
        $stmt->bind_param('s', $idNo);
    }

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!$row) {
        echo json_encode(['success' => true, 'data' => null]);
        exit();
    }
    echo json_encode(['success' => true, 'data' => $row]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
