<?php
// Simple test endpoint to verify CORS headers and preflight handling.
// Usage: call OPTIONS or GET from your dev origin and inspect response headers.
if (file_exists(__DIR__ . '/cors.php')) {
    include_once __DIR__ . '/cors.php';
}

header('Content-Type: application/json');

echo json_encode(["ok" => true, "ts" => date('c'), "origin" => $_SERVER['HTTP_ORIGIN'] ?? null]);
?>
