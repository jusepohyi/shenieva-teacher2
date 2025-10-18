<?php
include 'conn.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all origins (adjust for production)
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['pk_studentID'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid request data']));
}

$pk_studentID = $data['pk_studentID'];
$trashCollected = isset($data['trashCollected']) ? $data['trashCollected'] : null;

try {
    $conn->begin_transaction();

    // Fetch current studentColtrash and studentLevel
    $stmt = $conn->prepare("SELECT studentColtrash, studentLevel FROM students_table WHERE pk_studentID = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $pk_studentID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $existingTrash = $row['studentColtrash'] ?? 0;
    $currentLevel = $row['studentLevel'] ?? 0; // Default to 0 if null
    $stmt->close();

    if ($trashCollected !== null) {
        $newTrashTotal = $existingTrash + $trashCollected;
        
        // Check if studentLevel is below 3 and update it to 3 if so
        if ($currentLevel < 3) {
            $newLevel = 3;
            $stmt = $conn->prepare("UPDATE students_table SET studentColtrash = ?, studentLevel = ? WHERE pk_studentID = ?");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('iii', $newTrashTotal, $newLevel, $pk_studentID);
        } else {
            // Update only studentColtrash if level is 3 or higher
            $stmt = $conn->prepare("UPDATE students_table SET studentColtrash = ? WHERE pk_studentID = ?");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('ii', $newTrashTotal, $pk_studentID);
        }

        $success = $stmt->execute();
        if (!$success) {
            throw new Exception('Update failed: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        $newTrashTotal = $existingTrash;
        // No level update needed if trashCollected is null (just fetching)
    }

    $conn->commit();

    // Return both studentColtrash and studentLevel in the response
    echo json_encode([
        'success' => true,
        'studentColtrash' => (int)$newTrashTotal,
        'studentLevel' => $trashCollected !== null && $currentLevel < 3 ? 3 : (int)$currentLevel
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo(json_encode(['error' => $e->getMessage()]));
}

$conn->close();
?>
