<?php
declare(strict_types=1);

// Minimal health check test
use InfraMind\Core\Config;

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    Config::load(__DIR__ . '/../.env');
    
    echo json_encode([
        'success' => true,
        'status' => 'healthy',
        'database' => $_ENV['DB_DRIVER'] ?? 'unknown',
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
