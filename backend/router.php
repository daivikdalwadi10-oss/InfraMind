<?php
/**
 * PHP Development Server Router
 * Routes all requests to the public directory
 * Place this file in the backend root directory
 */

// Parse the request URI to remove query strings
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requested_file = __DIR__ . '/public' . $uri;

// If the requested file exists in public directory, serve it
if (file_exists($requested_file) && is_file($requested_file)) {
    // For PHP files, include them to execute
    if (pathinfo($requested_file, PATHINFO_EXTENSION) === 'php') {
        include $requested_file;
        exit;
    }
    return false; // Let the server serve static files
}

// If it's a directory request, try index.php
if (is_dir(__DIR__ . '/public' . $uri)) {
    $index = __DIR__ . '/public' . $uri . '/index.php';
    if (file_exists($index)) {
        include $index;
        exit;
    }
}

// Otherwise route to public/index.php for API requests
include __DIR__ . '/public/index.php';

