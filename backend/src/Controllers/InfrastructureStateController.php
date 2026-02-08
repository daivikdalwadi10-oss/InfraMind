<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Services\InfrastructureStateService;
use InfraMind\Validators\InfrastructureStateValidator;
use InfraMind\Exceptions\ValidationException;
use InfraMind\Core\Logger;

/**
 * Infrastructure state controller.
 */
class InfrastructureStateController
{
    private InfrastructureStateService $stateService;
    private Logger $logger;

    public function __construct()
    {
        $this->stateService = new InfrastructureStateService();
        $this->logger = Logger::getInstance();
    }

    /**
     * POST /infrastructure
     */
    public function create(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if (!$user || $user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can record infrastructure states');
            }

            $data = $request->getAll();
            InfrastructureStateValidator::validateCreate($data);

            $state = $this->stateService->createState(
                $user->sub,
                $data['component'],
                $data['status'] ?? 'HEALTHY',
                $data['summary'] ?? null,
                $data['observedAt'] ?? null,
            );

            return (new Response(201))->success($state->toArray(), 'Infrastructure state created');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Infrastructure state creation error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /infrastructure/:id
     */
    public function get(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!$user) {
                return (new Response(401))->error('Not authenticated');
            }

            $state = $this->stateService->getState($id);
            return (new Response(200))->success($state->toArray());
        } catch (\Exception $e) {
            return (new Response(404))->error($e->getMessage());
        }
    }

    /**
     * GET /infrastructure
     */
    public function list(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if (!$user) {
                return (new Response(401))->error('Not authenticated');
            }

            $limit = min((int) $request->getQuery('limit', 50), 100);
            $offset = (int) $request->getQuery('offset', 0);
            $status = $request->getQuery('status');

            $states = $this->stateService->listAll($limit, $offset, $status);
            return (new Response(200))->success(array_map(fn($s) => $s->toArray(), $states));
        } catch (\Exception $e) {
            $this->logger->error('Infrastructure state list error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * PUT /infrastructure/:id
     */
    public function update(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!$user || $user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can update infrastructure states');
            }

            $data = $request->getAll();
            InfrastructureStateValidator::validateUpdate($data);

            $updated = $this->stateService->updateState($id, $data, $user->sub);
            return (new Response(200))->success($updated->toArray(), 'Infrastructure state updated');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Infrastructure state update error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }
}
