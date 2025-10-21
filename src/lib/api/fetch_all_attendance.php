<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=shenieva_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

try {
    // join with students_table to include idNo and gender for display/filtering
    $query = "SELECT a.fk_studentID, a.studentName, a.attendanceDateTime, s.idNo, s.studentGender AS gender, s.studentLevel FROM attendance_table a LEFT JOIN students_table s ON a.fk_studentID = s.pk_studentID ORDER BY a.attendanceDateTime DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $rows = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // normalize datetime to a consistent format (Y-m-d H:i:s)
        try {
            $dt = new DateTime($r['attendanceDateTime']);
            $r['attendanceDateTime'] = $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            // leave as-is if parsing fails
        }
    // ensure idNo, gender and studentLevel keys exist (may be null)
    if (!isset($r['idNo'])) $r['idNo'] = null;
    if (!isset($r['gender'])) $r['gender'] = null;
    if (!isset($r['studentLevel'])) $r['studentLevel'] = null;
        $rows[] = $r;
    }

    echo json_encode($rows);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
