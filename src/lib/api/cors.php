<?php
// cors.php
// Centralized CORS helper. Include this at the top of API entry files or from conn.php
// so all responses send appropriate CORS headers and handle preflight OPTIONS.

// Allow origins can be provided via the ALLOWED_ORIGINS environment variable
// (comma-separated). If not provided, default to localhost dev and a safe
// Hostinger preview domain if present in env (ALLOWED_HOSTING_PREVIEW), else
// no wildcard in production.

$allowed_env = getenv('ALLOWED_ORIGINS') ?: '';
$allowed = array_filter(array_map('trim', explode(',', $allowed_env)));

// If ALLOWED_ORIGINS wasn't set, try a reasonable default list.
if (empty($allowed)) {
    $allowed = [
        'http://localhost:5173',
        'http://localhost:5174',  // Alternative dev port
        // Hostinger preview domain (temporary). Add production domains via ALLOWED_ORIGINS or ALLOWED_HOSTING_PREVIEW env.
        'https://darkred-dinosaur-537713.hostingersite.com',
        // Netlify default origin for your site and your custom domain — add more as needed
        'https://shenieviareads.netlify.app',
        'https://shenieviareads.fun',
    ];

    // add preview host from environment if provided (useful for Hostinger temp domains)
    $preview = getenv('ALLOWED_HOSTING_PREVIEW');
    if ($preview) $allowed[] = $preview;
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// Debug mode: if ALLOW_CORS_DEBUG=1 is set in the environment on the host,
// respond permissively to CORS requests from any origin found in the
// request. Use this only for short-term debugging (e.g. localhost dev).
// Read ALLOW_CORS_DEBUG (set to '1' for temporary permissive CORS during testing)
$allow_debug = getenv('ALLOW_CORS_DEBUG') ?: '0';
if ($allow_debug === '1' && $origin) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }
}
if ($origin && in_array($origin, $allowed)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // In development it can be helpful to allow localhost; do not fallback to '*'
    // in production unless you understand the security implications.
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

?>
