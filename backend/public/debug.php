<?php
declare(strict_types=1);

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'request' => [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
        'path_info' => $_SERVER['PATH_INFO'] ?? 'NONE',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'NONE',
        'php_self' => $_SERVER['PHP_SELF'] ?? 'NONE'
    ]
]);
