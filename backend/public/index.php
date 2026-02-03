<?php

declare(strict_types=1);

/**
 * InfraMind Backend - Main Application Entry Point
 * 
 * This is the single entry point for all HTTP requests. All requests
 * are routed through this file to the appropriate controllers.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Load composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use InfraMind\Core\Config;
use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Core\Router;
use InfraMind\Core\Logger;
use InfraMind\Middleware\AuthMiddleware;
use InfraMind\Middleware\RoleMiddleware;
use InfraMind\Middleware\CorsMiddleware;
use InfraMind\Middleware\RateLimitMiddleware;
use InfraMind\Middleware\LoggingMiddleware;

try {
    // Load configuration
    $configPath = dirname(__DIR__) . '/.env';
    Config::load($configPath);

    $logger = Logger::getInstance();

    // Create request and router
    $request = new Request();
    $router = new Router();

    // Register routes

    // AUTH ROUTES (no auth required for signup/login)
    $router->register('POST', '/auth/signup', 'AuthController', 'signup', [CorsMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/auth/login', 'AuthController', 'login', [CorsMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/auth/refresh', 'AuthController', 'refreshToken', [CorsMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/auth/me', 'AuthController', 'getCurrentUser', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    // TASK ROUTES (authenticated, manager/employee access)
    $router->register('POST', '/tasks', 'TaskController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/tasks', 'TaskController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/tasks/{id}', 'TaskController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/tasks/{id}/status', 'TaskController', 'updateStatus', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // ANALYSIS ROUTES (authenticated)
    $router->register('POST', '/analyses', 'AnalysisController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/analyses', 'AnalysisController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/analyses/{id}', 'AnalysisController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/analyses/{id}', 'AnalysisController', 'update', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/analyses/{id}/submit', 'AnalysisController', 'submit', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/analyses/{id}/review', 'AnalysisController', 'review', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // REPORT ROUTES (authenticated)
    $router->register('POST', '/reports', 'ReportController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/reports', 'ReportController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/reports/{id}', 'ReportController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/reports/{id}/full', 'ReportController', 'getFull', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    // HEALTH CHECK
    $router->register('GET', '/health', 'HealthController', 'check', [LoggingMiddleware::class]);

    // Route request
    $response = $router->route($request);

    // Send response
    $response->send();
} catch (\Exception $e) {
    Logger::getInstance()->critical('Unhandled exception: ' . $e->getMessage());
    $response = (new Response(500))->error('Internal server error');
    $response->send();
    exit(1);
}
