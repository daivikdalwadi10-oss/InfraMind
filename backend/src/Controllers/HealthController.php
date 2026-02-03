<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Core\Database;

/**
 * Health check controller.
 */
class HealthController
{
    public function check(Request $request): Response
    {
        try {
            // Test database connection
            $db = Database::getInstance();
            $row = $db->fetchOne('SELECT 1 as status');

            if (!$row) {
                return (new Response(503))->error('Database check failed');
            }

            return (new Response(200))->success([
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            return (new Response(503))->error('Service unavailable: ' . $e->getMessage());
        }
    }
}
