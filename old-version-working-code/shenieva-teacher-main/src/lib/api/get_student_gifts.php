<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conn.php';

// Expect studentID as GET parameter
$studentID = null;
if (isset($_GET['studentID'])) {
    $studentID = trim($_GET['studentID']);
}

if (!$studentID) {
    echo json_encode(['success' => false, 'error' => 'Missing studentID parameter']);
    exit();
}

try {
    $stmt = $conn->prepare('SELECT gift, giftID FROM gifts_table WHERE studentID = ? ORDER BY giftID DESC');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $studentID);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>