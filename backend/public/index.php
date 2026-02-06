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

    if ($request->getMethod() === 'OPTIONS') {
        $cors = new CorsMiddleware();
        $response = $cors->handle($request, function () {
            return (new Response(200))->success();
        });
        $response->send();
        exit(0);
    }

    // Register routes

    // AUTH ROUTES (no auth required for signup/login)
    $router->register('POST', '/api/auth/signup', 'AuthController', 'signup', [CorsMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/auth/login', 'AuthController', 'login', [CorsMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/auth/refresh', 'AuthController', 'refreshToken', [CorsMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/auth/me', 'AuthController', 'getCurrentUser', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    // USER ROUTES (admin only)
    $router->register('GET', '/api/users', 'UserController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/users/{id}', 'UserController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    // TASK ROUTES (authenticated, manager/employee access)
    $router->register('POST', '/api/tasks', 'TaskController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/tasks', 'TaskController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/tasks/{id}', 'TaskController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/api/tasks/{id}/status', 'TaskController', 'updateStatus', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // ANALYSIS ROUTES (authenticated)
    $router->register('POST', '/api/analyses', 'AnalysisController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/analyses', 'AnalysisController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/analyses/{id}', 'AnalysisController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/api/analyses/{id}', 'AnalysisController', 'update', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/analyses/{id}/submit', 'AnalysisController', 'submit', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/analyses/{id}/review', 'AnalysisController', 'review', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // REPORT ROUTES (authenticated)
    $router->register('POST', '/api/reports', 'ReportController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/reports', 'ReportController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/reports/{id}', 'ReportController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/reports/{id}/full', 'ReportController', 'getFull', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    // HEALTH CHECK
    $router->register('GET', '/api/health', 'HealthController', 'check', [LoggingMiddleware::class]);

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
