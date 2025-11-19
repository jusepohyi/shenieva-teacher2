<?php
// src/lib/api/login_teacher.php

// Use centralized CORS helper if present
if (file_exists(__DIR__ . '/cors.php')) {
    include_once __DIR__ . '/cors.php';
} else {
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

header("Content-Type: application/json");

include 'conn.php';

// optional debug logger
if (file_exists(__DIR__ . '/debug_log.php')) {
    include_once __DIR__ . '/debug_log.php';
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

try {
    if (!isset($data['idNo']) || !isset($data['teacherPass'])) {
        if (function_exists('api_debug_log')) api_debug_log('login_teacher_bad_request', ['raw' => $raw]);
        echo json_encode(["success" => false, "message" => "Missing idNo or password"]);
        exit;
    }

    // Only log idNo (not passwords)
    $idNo_safe = $data['idNo'];
    if (function_exists('api_debug_log')) api_debug_log('login_teacher_attempt', ['idNo' => $idNo_safe]);

    $idNo = $conn->real_escape_string($data['idNo']);
    $teacherPass = $conn->real_escape_string($data['teacherPass']);

    $sql = "SELECT * FROM teacher_table WHERE LOWER(idNo) = LOWER('$idNo')";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $teacher = $result->fetch_assoc();
        if ($teacher['teacherPass'] === $teacherPass) {
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie('teacherId', (string)$teacher['pk_teacherID'], [
                'path' => '/',
                'httponly' => true,
                'secure' => $isSecure,
                'samesite' => 'None'
            ]);

            if (function_exists('api_debug_log')) api_debug_log('login_teacher_success', ['idNo' => $idNo_safe, 'teacherId' => (int)$teacher['pk_teacherID']]);

            echo json_encode([
                "success" => true,
                "message" => "Login successful",
                "data" => [
                    'teacherId' => (int)$teacher['pk_teacherID'],
                    'teacherName' => $teacher['teacherName']
                ]
            ]);
        } else {
            if (function_exists('api_debug_log')) api_debug_log('login_teacher_failed_password', ['idNo' => $idNo_safe]);
            echo json_encode(["success" => false, "message" => "Invalid ID or password"]);
        }
    } else {
        if (function_exists('api_debug_log')) api_debug_log('login_teacher_not_found', ['idNo' => $idNo_safe]);
        echo json_encode(["success" => false, "message" => "Invalid ID or password"]);
    }

    if ($conn->error) {
        if (function_exists('api_debug_log')) api_debug_log('login_teacher_sql_error', ['error' => $conn->error]);
    }

    $conn->close();
} catch (Exception $e) {
    if (function_exists('api_debug_log')) api_debug_log('login_teacher_exception', ['idNo' => $data['idNo'] ?? null, 'message' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Internal server error"]);
    exit;
}
?>
