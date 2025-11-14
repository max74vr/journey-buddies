<?php
// Router for PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route to the requested file
$file = __DIR__ . $uri;
if (file_exists($file) && is_file($file)) {
    return false;
}

// Default fallback
if (file_exists(__DIR__ . $uri . '.php')) {
    include __DIR__ . $uri . '.php';
    exit;
}

return false;
