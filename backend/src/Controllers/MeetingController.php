<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Services\MeetingService;
use InfraMind\Validators\MeetingValidator;
use InfraMind\Exceptions\ValidationException;
use InfraMind\Core\Logger;

/**
 * Meeting controller.
 */
class MeetingController
{
    private MeetingService $meetingService;
    private Logger $logger;

    public function __construct()
    {
        $this->meetingService = new MeetingService();
        $this->logger = Logger::getInstance();
    }

    /**
     * POST /meetings
     */
    public function create(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if (!$user || $user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can create meetings');
            }

            $data = $request->getAll();
            MeetingValidator::validateCreate($data);

            $meeting = $this->meetingService->createMeeting(
                $user->sub,
                $data['title'],
                $data['agenda'] ?? null,
                $data['status'] ?? 'SCHEDULED',
                $data['scheduledAt'],
                (int) ($data['durationMinutes'] ?? 30),
                $data['analysisId'] ?? null,
                $data['incidentId'] ?? null,
            );

            return (new Response(201))->success($meeting->toArray(), 'Meeting created');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Meeting creation error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /meetings/:id
     */
    public function get(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!$user) {
                return (new Response(401))->error('Not authenticated');
            }

            $meeting = $this->meetingService->getMeeting($id);
            return (new Response(200))->success($meeting->toArray());
        } catch (\Exception $e) {
            return (new Response(404))->error($e->getMessage());
        }
    }

    /**
     * GET /meetings
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

            $meetings = $this->meetingService->listAll($limit, $offset, $status);
            return (new Response(200))->success(array_map(fn($m) => $m->toArray(), $meetings));
        } catch (\Exception $e) {
            $this->logger->error('Meeting list error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * PUT /meetings/:id
     */
    public function update(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!$user || $user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can update meetings');
            }

            $data = $request->getAll();
            MeetingValidator::validateUpdate($data);

            $updated = $this->meetingService->updateMeeting($id, $data, $user->sub);
            return (new Response(200))->success($updated->toArray(), 'Meeting updated');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Meeting update error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }
}
