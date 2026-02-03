<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use InfraMind\Core\Config;
use InfraMind\Core\Database;

// Set request to /health
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/health';

header('Content-Type: application/json');

try {
    Config::load(__DIR__ . '/../.env');
    
    // Test database connection
    $db = Database::getInstance();
    
    echo json_encode([
        'success' => true,
        'status' => 'healthy',
        'database' => 'connected',
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
