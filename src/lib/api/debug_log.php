<?php
// Minimal, safe debug logger for API endpoints.
// Writes newline-delimited JSON to src/lib/api/logs/api_debug.log
// Avoids logging sensitive fields like passwords.

function api_debug_log($label, $data = null) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $file = $logDir . '/api_debug.log';

    $entry = [
        'ts' => date('c'),
        'label' => $label,
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'uri' => $_SERVER['REQUEST_URI'] ?? null,
        'data' => $data
    ];

    $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

?>
