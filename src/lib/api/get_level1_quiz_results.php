<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $storyTitle = isset($_GET['storyTitle']) ? $_GET['storyTitle'] : null;
    
    try {
        if ($storyTitle) {
            // Get results for specific story
            $stmt = $conn->prepare("
                SELECT 
                    lq.quizID,
                    lq.studentID,
                    s.studentName,
                    s.idNo,
                    lq.storyTitle,
                    lq.question,
                    lq.choiceA,
                    lq.choiceB,
                    lq.choiceC,
                    lq.choiceD,
                    lq.correctAnswer,
                    lq.selectedAnswer,
                    lq.point as score,
                    lq.attempt,
                    lq.createdAt
                FROM level1_quiz lq
                JOIN students_table s ON lq.studentID = s.pk_studentID
                WHERE lq.storyTitle = ?
                ORDER BY s.studentName, lq.attempt DESC, lq.createdAt DESC
            ");
            $stmt->bind_param("s", $storyTitle);
        } else {
            // Get all results
            $stmt = $conn->prepare("
                SELECT 
                    lq.quizID,
                    lq.studentID,
                    s.studentName,
                    s.idNo,
                    lq.storyTitle,
                    lq.question,
                    lq.choiceA,
                    lq.choiceB,
                    lq.choiceC,
                    lq.choiceD,
                    lq.correctAnswer,
                    lq.selectedAnswer,
                    lq.point as score,
                    lq.attempt,
                    lq.createdAt
                FROM level1_quiz lq
                JOIN students_table s ON lq.studentID = s.pk_studentID
                ORDER BY s.studentName, lq.storyTitle, lq.attempt DESC, lq.createdAt DESC
            ");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $results,
            'count' => count($results)
        ]);
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}
?>
