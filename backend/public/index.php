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
use InfraMind\Middleware\MaintenanceMiddleware;

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
    $router->register('POST', '/api/analyses/manager', 'AnalysisController', 'createAssigned', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/analyses', 'AnalysisController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/analyses/{id}', 'AnalysisController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/api/analyses/{id}', 'AnalysisController', 'update', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/analyses/{id}/submit', 'AnalysisController', 'submit', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/analyses/{id}/review', 'AnalysisController', 'review', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/analyses/{id}/ai/hypotheses', 'AnalysisController', 'generateHypotheses', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/analyses/{id}/ai/outputs', 'AnalysisController', 'listAiOutputs', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/analyses/{id}/ai/report-draft', 'AnalysisController', 'generateReportDraft', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // REPORT ROUTES (authenticated)
    $router->register('POST', '/api/reports', 'ReportController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/reports', 'ReportController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/reports/{id}', 'ReportController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/reports/{id}/full', 'ReportController', 'getFull', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    // AI OUTPUT ROUTES (authenticated)
    $router->register('PATCH', '/api/ai/outputs/{id}', 'AiOutputController', 'update', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // INCIDENT ROUTES (authenticated)
    $router->register('POST', '/api/incidents', 'IncidentController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/incidents', 'IncidentController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/incidents/{id}', 'IncidentController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/api/incidents/{id}', 'IncidentController', 'update', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // INFRASTRUCTURE STATE ROUTES (authenticated)
    $router->register('POST', '/api/infrastructure', 'InfrastructureStateController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/infrastructure', 'InfrastructureStateController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/infrastructure/{id}', 'InfrastructureStateController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/api/infrastructure/{id}', 'InfrastructureStateController', 'update', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // ARCHITECTURE RISK ROUTES (authenticated)
    $router->register('POST', '/api/risks', 'ArchitectureRiskController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/risks', 'ArchitectureRiskController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/risks/{id}', 'ArchitectureRiskController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/api/risks/{id}', 'ArchitectureRiskController', 'update', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // MEETING ROUTES (authenticated)
    $router->register('POST', '/api/meetings', 'MeetingController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/meetings', 'MeetingController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/meetings/{id}', 'MeetingController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/api/meetings/{id}', 'MeetingController', 'update', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // HEALTH CHECK
    $router->register('GET', '/api/health', 'HealthController', 'check', [LoggingMiddleware::class]);

    // MAINTENANCE (public status)
    $router->register('GET', '/api/maintenance/status', 'MaintenanceController', 'status', [CorsMiddleware::class, LoggingMiddleware::class]);

    // ANNOUNCEMENTS (authenticated for role targeting)
    $router->register('GET', '/api/announcements/active', 'AnnouncementController', 'listActive', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    // ADMIN ROUTES (developer/system admin only)
    $router->register('GET', '/api/admin/health', 'AdminController', 'health', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/admin/insights', 'AdminController', 'insights', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/admin/logs', 'AdminController', 'logs', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/admin/server-actions', 'AdminController', 'serverAction', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/admin/users', 'AdminController', 'users', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('GET', '/api/admin/teams', 'AdminController', 'teams', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    $router->register('GET', '/api/admin/maintenance', 'MaintenanceController', 'get', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/api/admin/maintenance', 'MaintenanceController', 'update', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    $router->register('GET', '/api/admin/announcements', 'AnnouncementController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/admin/announcements', 'AnnouncementController', 'create', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/admin/announcements/{id}/archive', 'AnnouncementController', 'archive', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    $router->register('GET', '/api/admin/credentials', 'CredentialController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/admin/credentials', 'CredentialController', 'create', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/admin/credentials/{id}/rotate', 'CredentialController', 'rotate', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('POST', '/api/admin/credentials/{id}/disable', 'CredentialController', 'disable', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);

    $router->register('GET', '/api/admin/feature-flags', 'FeatureFlagController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    $router->register('PUT', '/api/admin/feature-flags', 'FeatureFlagController', 'upsert', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
    
        // TEAM ROUTES (authenticated)
        $router->register('POST', '/api/teams', 'TeamController', 'create', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
        $router->register('GET', '/api/teams', 'TeamController', 'list', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
        $router->register('GET', '/api/teams/{id}/members', 'TeamController', 'members', [CorsMiddleware::class, AuthMiddleware::class, LoggingMiddleware::class]);
        $router->register('POST', '/api/teams/{id}/members', 'TeamController', 'addMember', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);
        $router->register('DELETE', '/api/teams/{id}/members/{userId}', 'TeamController', 'removeMember', [CorsMiddleware::class, AuthMiddleware::class, RateLimitMiddleware::class, LoggingMiddleware::class]);

    // Route request with global maintenance guard
    $maintenance = new MaintenanceMiddleware();
    $response = $maintenance->handle($request, function ($req) use ($router) {
        return $router->route($req);
    });

    // Send response
    $response->send();
} catch (\Exception $e) {
    Logger::getInstance()->critical('Unhandled exception: ' . $e->getMessage());
    $response = (new Response(500))->error('Internal server error');
    $response->send();
    exit(1);
}
