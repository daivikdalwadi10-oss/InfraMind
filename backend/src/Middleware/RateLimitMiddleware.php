<?php

declare(strict_types=1);

namespace InfraMind\Middleware;

use InfraMind\Core\Request;
use InfraMind\Core\Response;

/**
 * Rate limiting middleware.
 */
class RateLimitMiddleware implements Middleware
{
    private const CACHE_DIR = './logs/rate_limit';

    public function handle(Request $request, callable $next): Response
    {
        if (!\InfraMind\Core\Config::getBool('RATE_LIMIT_ENABLED')) {
            return $next($request);
        }

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!$this->checkRateLimit($clientIp)) {
            return (new Response(429))->error('Too many requests');
        }

        return $next($request);
    }

    private function checkRateLimit(string $ip): bool
    {
        $maxRequests = \InfraMind\Core\Config::getInt('RATE_LIMIT_REQUESTS', 100);
        $window = \InfraMind\Core\Config::getInt('RATE_LIMIT_WINDOW', 60);

        $now = time();
        $windowKey = $ip . '_' . floor($now / $window);
        $cacheFile = self::CACHE_DIR . '/' . md5($windowKey);

        // Create cache directory if needed
        if (!is_dir(self::CACHE_DIR)) {
            @mkdir(self::CACHE_DIR, 0755, true);
        }

        // Clean old cache files
        $this->cleanOldCache();

        $count = 0;
        if (file_exists($cacheFile)) {
            $count = (int) file_get_contents($cacheFile);
        }

        if ($count >= $maxRequests) {
            return false;
        }

        file_put_contents($cacheFile, (string) ($count + 1));
        return true;
    }

    private function cleanOldCache(): void
    {
        if (!is_dir(self::CACHE_DIR)) {
            return;
        }

        $files = glob(self::CACHE_DIR . '/*');
        foreach ($files as $file) {
            if (is_file($file) && time() - filemtime($file) > 3600) {
                @unlink($file);
            }
        }
    }
}
