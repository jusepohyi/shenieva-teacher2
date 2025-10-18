<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $storyTitle = isset($_GET['storyTitle']) ? $_GET['storyTitle'] : null;
    
    try {
        $stats = [];
        
        if ($storyTitle) {
            // Get statistics for specific story
            
            // Total students who took the quiz
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT studentID) as totalStudents
                FROM level1_quiz
                WHERE storyTitle = ?
            ");
            $stmt->bind_param("s", $storyTitle);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['totalStudents'] = $result->fetch_assoc()['totalStudents'];
            $stmt->close();
            
            // Total attempts
            $stmt = $conn->prepare("
                SELECT COUNT(*) as totalAttempts
                FROM level1_quiz
                WHERE storyTitle = ?
            ");
            $stmt->bind_param("s", $storyTitle);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['totalAttempts'] = $result->fetch_assoc()['totalAttempts'];
            $stmt->close();
            
            // Average score
            $stmt = $conn->prepare("
                SELECT AVG(score) as avgScore
                FROM level1_quiz
                WHERE storyTitle = ?
            ");
            $stmt->bind_param("s", $storyTitle);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['averageScore'] = round($result->fetch_assoc()['avgScore'], 2);
            $stmt->close();
            
            // Highest score
            $stmt = $conn->prepare("
                SELECT MAX(score) as highestScore
                FROM level1_quiz
                WHERE storyTitle = ?
            ");
            $stmt->bind_param("s", $storyTitle);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['highestScore'] = $result->fetch_assoc()['highestScore'];
            $stmt->close();
            
            // Lowest score
            $stmt = $conn->prepare("
                SELECT MIN(score) as lowestScore
                FROM level1_quiz
                WHERE storyTitle = ?
            ");
            $stmt->bind_param("s", $storyTitle);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['lowestScore'] = $result->fetch_assoc()['lowestScore'];
            $stmt->close();
            
            // Most attempted question
            $stmt = $conn->prepare("
                SELECT question, COUNT(*) as attempts
                FROM level1_quiz
                WHERE storyTitle = ?
                GROUP BY question
                ORDER BY attempts DESC
                LIMIT 1
            ");
            $stmt->bind_param("s", $storyTitle);
            $stmt->execute();
            $result = $stmt->get_result();
            $mostAttempted = $result->fetch_assoc();
            $stats['mostAttemptedQuestion'] = $mostAttempted ? $mostAttempted['question'] : 'N/A';
            $stmt->close();
            
            // Question with lowest success rate
            $stmt = $conn->prepare("
                SELECT 
                    question,
                    SUM(CASE WHEN selectedAnswer = correctAnswer THEN 1 ELSE 0 END) as correct,
                    COUNT(*) as total,
                    ROUND((SUM(CASE WHEN selectedAnswer = correctAnswer THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as successRate
                FROM level1_quiz
                WHERE storyTitle = ?
                GROUP BY question
                ORDER BY successRate ASC
                LIMIT 1
            ");
            $stmt->bind_param("s", $storyTitle);
            $stmt->execute();
            $result = $stmt->get_result();
            $hardestQuestion = $result->fetch_assoc();
            $stats['hardestQuestion'] = $hardestQuestion ? [
                'question' => $hardestQuestion['question'],
                'successRate' => $hardestQuestion['successRate']
            ] : null;
            $stmt->close();
            
        }
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'storyTitle' => $storyTitle
        ]);
        
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
