<?php
/**
 * PHP Development Server Router
 * Routes all requests to the public directory
 * Place this file in the backend root directory
 */

$requested_file = __DIR__ . '/public' . $_SERVER['REQUEST_URI'];

// If the requested file exists in public directory, serve it
if (file_exists($requested_file) && is_file($requested_file)) {
    return false; // Let the server serve the file
}

// If it's a directory request, try index.php
if (is_dir(__DIR__ . '/public' . $_SERVER['REQUEST_URI'])) {
    $index = __DIR__ . '/public' . $_SERVER['REQUEST_URI'] . '/index.php';
    if (file_exists($index)) {
        include $index;
        exit;
    }
}

// Otherwise route to public directory
include __DIR__ . '/public/index.php';
