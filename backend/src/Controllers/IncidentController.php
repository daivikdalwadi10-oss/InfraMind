<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Services\IncidentService;
use InfraMind\Validators\IncidentValidator;
use InfraMind\Exceptions\ValidationException;
use InfraMind\Core\Logger;

/**
 * Incident controller.
 */
class IncidentController
{
    private IncidentService $incidentService;
    private Logger $logger;

    public function __construct()
    {
        $this->incidentService = new IncidentService();
        $this->logger = Logger::getInstance();
    }

    /**
     * POST /incidents
     */
    public function create(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if (!$user || !in_array($user->role ?? null, ['EMPLOYEE', 'MANAGER'], true)) {
                return (new Response(403))->error('Only employees or managers can create incidents');
            }

            $data = $request->getAll();
            IncidentValidator::validateCreate($data);

            $incident = $this->incidentService->createIncident(
                $user->sub,
                $data['title'],
                $data['description'] ?? null,
                $data['severity'] ?? 'MEDIUM',
                $data['assignedTo'] ?? null,
                $data['occurredAt'] ?? null,
            );

            return (new Response(201))->success($incident->toArray(), 'Incident created');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Incident creation error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /incidents/:id
     */
    public function get(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!$user) {
                return (new Response(401))->error('Not authenticated');
            }

            $incident = $this->incidentService->getIncident($id);
            if ($user->role === 'EMPLOYEE') {
                if ($incident->reportedBy !== $user->sub && $incident->assignedTo !== $user->sub) {
                    return (new Response(403))->error('You can only view assigned or reported incidents');
                }
            }

            return (new Response(200))->success($incident->toArray());
        } catch (\Exception $e) {
            return (new Response(404))->error($e->getMessage());
        }
    }

    /**
     * GET /incidents
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
            $severity = $request->getQuery('severity');

            if ($user->role === 'EMPLOYEE') {
                $incidents = $this->incidentService->listForEmployee($user->sub, $limit, $offset);
            } else {
                $incidents = $this->incidentService->listAll($limit, $offset, $status, $severity);
            }

            return (new Response(200))->success(array_map(fn($i) => $i->toArray(), $incidents));
        } catch (\Exception $e) {
            $this->logger->error('Incident list error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * PUT /incidents/:id
     */
    public function update(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!$user) {
                return (new Response(401))->error('Not authenticated');
            }

            $data = $request->getAll();
            IncidentValidator::validateUpdate($data);

            $incident = $this->incidentService->getIncident($id);
            if ($user->role === 'EMPLOYEE' && $incident->reportedBy !== $user->sub) {
                return (new Response(403))->error('You can only update incidents you reported');
            }

            if ($user->role === 'OWNER') {
                return (new Response(403))->error('Owners cannot modify incidents');
            }

            $updated = $this->incidentService->updateIncident($id, $data, $user->sub);
            return (new Response(200))->success($updated->toArray(), 'Incident updated');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Incident update error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }
}
