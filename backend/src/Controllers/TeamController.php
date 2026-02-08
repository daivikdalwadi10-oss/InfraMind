<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Core\Logger;
use InfraMind\Services\TeamService;
use InfraMind\Validators\TeamValidator;
use InfraMind\Exceptions\ValidationException;

class TeamController
{
    private TeamService $teamService;
    private Logger $logger;

    public function __construct()
    {
        $this->teamService = new TeamService();
        $this->logger = Logger::getInstance();
    }

    /**
     * POST /teams
     */
    public function create(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can create teams');
            }

            $data = $request->getAll();
            TeamValidator::validateCreate($data);

            $team = $this->teamService->createTeam(
                $user->sub,
                $data['name'],
                $data['description'] ?? null,
            );

            return (new Response(201))->success($team->toArray(), 'Team created');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Team creation error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /teams
     */
    public function list(Request $request): Response
    {
        try {
            $user = $request->getUser();
            $limit = min((int) $request->getQuery('limit', 50), 100);
            $offset = (int) $request->getQuery('offset', 0);

            if (!in_array($user->role, ['MANAGER', 'OWNER'], true)) {
                return (new Response(403))->error('Insufficient permissions');
            }

            $teams = $this->teamService->listTeams($user->sub, $user->role, $limit, $offset);
            return (new Response(200))->success($teams);
        } catch (\Exception $e) {
            $this->logger->error('Team list error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /teams/:id/members
     */
    public function members(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!in_array($user->role, ['MANAGER', 'OWNER'], true)) {
                return (new Response(403))->error('Insufficient permissions');
            }
            $members = $this->teamService->listMembers($user->sub, $id, $user->role);
            return (new Response(200))->success($members);
        } catch (\Exception $e) {
            $this->logger->error('Team members error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * POST /teams/:id/members
     */
    public function addMember(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can manage teams');
            }

            $data = $request->getAll();
            TeamValidator::validateMember($data);

            $this->teamService->addMember($user->sub, $id, $data['userId']);
            return (new Response(200))->success(['teamId' => $id, 'userId' => $data['userId']], 'Member added');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Team add member error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * DELETE /teams/:id/members/:userId
     */
    public function removeMember(Request $request, string $id, string $userId): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can manage teams');
            }

            $this->teamService->removeMember($user->sub, $id, $userId);
            return (new Response(200))->success(['teamId' => $id, 'userId' => $userId], 'Member removed');
        } catch (\Exception $e) {
            $this->logger->error('Team remove member error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }
}
