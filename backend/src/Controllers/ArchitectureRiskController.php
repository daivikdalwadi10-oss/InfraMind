<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Services\ArchitectureRiskService;
use InfraMind\Validators\ArchitectureRiskValidator;
use InfraMind\Exceptions\ValidationException;
use InfraMind\Core\Logger;

/**
 * Architecture risk controller.
 */
class ArchitectureRiskController
{
    private ArchitectureRiskService $riskService;
    private Logger $logger;

    public function __construct()
    {
        $this->riskService = new ArchitectureRiskService();
        $this->logger = Logger::getInstance();
    }

    /**
     * POST /risks
     */
    public function create(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if (!$user || $user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can create architecture risks');
            }

            $data = $request->getAll();
            ArchitectureRiskValidator::validateCreate($data);

            $risk = $this->riskService->createRisk(
                $user->sub,
                $data['title'],
                $data['description'] ?? null,
                $data['severity'] ?? 'MEDIUM',
                $data['status'] ?? 'OPEN',
                $data['analysisId'] ?? null,
            );

            return (new Response(201))->success($risk->toArray(), 'Architecture risk created');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Architecture risk creation error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /risks/:id
     */
    public function get(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!$user) {
                return (new Response(401))->error('Not authenticated');
            }

            if ($user->role === 'EMPLOYEE') {
                return (new Response(403))->error('Employees cannot access architecture risks');
            }

            $risk = $this->riskService->getRisk($id);
            return (new Response(200))->success($risk->toArray());
        } catch (\Exception $e) {
            return (new Response(404))->error($e->getMessage());
        }
    }

    /**
     * GET /risks
     */
    public function list(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if (!$user) {
                return (new Response(401))->error('Not authenticated');
            }

            if ($user->role === 'EMPLOYEE') {
                return (new Response(403))->error('Employees cannot access architecture risks');
            }

            $limit = min((int) $request->getQuery('limit', 50), 100);
            $offset = (int) $request->getQuery('offset', 0);
            $status = $request->getQuery('status');
            $severity = $request->getQuery('severity');

            $risks = $this->riskService->listAll($limit, $offset, $status, $severity);
            return (new Response(200))->success(array_map(fn($r) => $r->toArray(), $risks));
        } catch (\Exception $e) {
            $this->logger->error('Architecture risk list error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * PUT /risks/:id
     */
    public function update(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!$user || $user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can update architecture risks');
            }

            $data = $request->getAll();
            ArchitectureRiskValidator::validateUpdate($data);

            $updated = $this->riskService->updateRisk($id, $data, $user->sub);
            return (new Response(200))->success($updated->toArray(), 'Architecture risk updated');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Architecture risk update error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }
}
