<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $studentID = isset($_GET['studentID']) ? intval($_GET['studentID']) : 0;
    
    if ($studentID <= 0) {
        echo json_encode(['error' => 'Invalid student ID']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT giftID, gift FROM gifts_table WHERE studentID = ? ORDER BY giftID DESC");
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $gifts = [];
        while ($row = $result->fetch_assoc()) {
            $gifts[] = [
                'giftID' => $row['giftID'],
                'gift' => $row['gift']
            ];
        }
        echo json_encode(['hasGift' => true, 'gifts' => $gifts, 'count' => count($gifts)]);
    } else {
        echo json_encode(['hasGift' => false]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>
