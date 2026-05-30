<?php
/**
 * PHP Built-in Server Router
 * Handles static file serving and routes all other requests through index files.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Route to the appropriate PHP file
$scriptFile = __DIR__ . $uri;
if (is_dir($scriptFile)) {
    $scriptFile = rtrim($scriptFile, '/') . '/index.php';
}

if (file_exists($scriptFile) && str_ends_with($scriptFile, '.php')) {
    require $scriptFile;
    return true;
}

http_response_code(404);
echo '<!DOCTYPE html><html><body><h1>404 Not Found</h1></body></html>';
