<?php

declare(strict_types=1);

namespace InfraMind\Middleware;

use InfraMind\Core\Request;
use InfraMind\Core\Response;

/**
 * Base middleware interface.
 */
interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}
