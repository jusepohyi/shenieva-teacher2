<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $studentID = isset($data['studentID']) ? intval($data['studentID']) : 0;
    $giftName = isset($data['giftName']) ? $data['giftName'] : '';
    $giftPrice = isset($data['giftPrice']) ? intval($data['giftPrice']) : 0;
    
    if ($studentID <= 0 || empty($giftName) || $giftPrice <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if student already has a gift
        $checkStmt = $conn->prepare("SELECT giftID FROM gifts_table WHERE studentID = ?");
        $checkStmt->bind_param("i", $studentID);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        $hasExistingGift = $checkResult->num_rows > 0;
        $checkStmt->close();
        
        // Get current trash count
        $trashStmt = $conn->prepare("SELECT studentColtrash FROM students_table WHERE pk_studentID = ?");
        $trashStmt->bind_param("i", $studentID);
        $trashStmt->execute();
        $trashResult = $trashStmt->get_result();
        
        if ($trashResult->num_rows === 0) {
            throw new Exception('Student not found');
        }
        
        $student = $trashResult->fetch_assoc();
        $currentTrash = intval($student['studentColtrash']);
        $trashStmt->close();
        
        if ($currentTrash < $giftPrice) {
            throw new Exception('Not enough trash collected');
        }
        
        // Deduct trash
        $newTrash = $currentTrash - $giftPrice;
        $updateStmt = $conn->prepare("UPDATE students_table SET studentColtrash = ? WHERE pk_studentID = ?");
        $updateStmt->bind_param("ii", $newTrash, $studentID);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Always insert new gift (allow multiple gifts)
        $insertStmt = $conn->prepare("INSERT INTO gifts_table (studentID, gift) VALUES (?, ?)");
        $insertStmt->bind_param("is", $studentID, $giftName);
        $insertStmt->execute();
        $insertStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Gift purchased successfully!',
            'newTrashCount' => $newTrash
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
