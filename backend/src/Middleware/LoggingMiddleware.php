<?php

declare(strict_types=1);

namespace InfraMind\Middleware;

use InfraMind\Core\Request;
use InfraMind\Core\Response;

/**
 * Logging middleware.
 */
class LoggingMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $startTime = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $startTime;

        \InfraMind\Core\Logger::getInstance()->info('Request', [
            'method' => $request->getMethod(),
            'path' => $request->getPath(),
            'status' => $response->toArray()['status'] ?? 'unknown',
            'duration_ms' => round($duration * 1000, 2),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        return $response;
    }
}
