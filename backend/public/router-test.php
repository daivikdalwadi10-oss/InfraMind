<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use InfraMind\Core\Config;
use InfraMind\Core\Request;
use InfraMind\Core\Response;

try {
    Config::load(__DIR__ . '/../.env');
    
    $request = new Request();
    $response = new Response(200);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'test' => 'Router test',
        'method' => $request->getMethod(),
        'path' => $request->getPath(),
        'request_uri' => $_SERVER['REQUEST_URI']
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
