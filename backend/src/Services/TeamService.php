<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Core\Database;
use InfraMind\Core\Logger;
use InfraMind\Exceptions\AuthorizationException;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Models\Team;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Repositories\TeamRepository;
use InfraMind\Repositories\UserRepository;
use InfraMind\Utils\Utils;

/**
 * Team service for manager administration.
 */
class TeamService
{
    private TeamRepository $teamRepository;
    private UserRepository $userRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;
    private Database $db;

    public function __construct()
    {
        $this->teamRepository = new TeamRepository();
        $this->userRepository = new UserRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
        $this->db = Database::getInstance();
    }

    public function createTeam(string $managerId, string $name, ?string $description = null): Team
    {
        $teamId = Utils::generateUuid();
        $now = Utils::now();

        $team = new Team($teamId, $name, $description, $managerId, $now, $now);

        $this->teamRepository->create($team);
        $this->auditRepository->log('Team', $teamId, 'CREATED', $managerId, [
            'name' => $name,
        ]);
        $this->logger->info("Team created: $teamId by manager: $managerId");

        return $team;
    }

    public function listTeams(string $userId, string $role, int $limit = 50, int $offset = 0): array
    {
        if ($role === 'OWNER') {
            return array_map(fn (Team $team) => $team->toArray(), $this->teamRepository->listAll($limit, $offset));
        }

        return array_map(fn (Team $team) => $team->toArray(), $this->teamRepository->listForManager($userId, $limit, $offset));
    }

    public function addMember(string $managerId, string $teamId, string $userId): void
    {
        $team = $this->teamRepository->findById($teamId);
        if (!$team) {
            throw new NotFoundException('Team not found');
        }
        if ($team->managerId !== $managerId) {
            throw new AuthorizationException('You can only manage your own teams');
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        $this->db->beginTransaction();
        try {
            $this->teamRepository->addMember($teamId, $userId);
            $this->auditRepository->log('Team', $teamId, 'MEMBER_ADDED', $managerId, [
                'userId' => $userId,
            ]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function removeMember(string $managerId, string $teamId, string $userId): void
    {
        $team = $this->teamRepository->findById($teamId);
        if (!$team) {
            throw new NotFoundException('Team not found');
        }
        if ($team->managerId !== $managerId) {
            throw new AuthorizationException('You can only manage your own teams');
        }

        $this->db->beginTransaction();
        try {
            $this->teamRepository->removeMember($teamId, $userId);
            $this->auditRepository->log('Team', $teamId, 'MEMBER_REMOVED', $managerId, [
                'userId' => $userId,
            ]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function listMembers(string $managerId, string $teamId, string $role): array
    {
        $team = $this->teamRepository->findById($teamId);
        if (!$team) {
            throw new NotFoundException('Team not found');
        }
        if ($role !== 'OWNER' && $team->managerId !== $managerId) {
            throw new AuthorizationException('You can only view members of your own teams');
        }

        return $this->teamRepository->listMembers($teamId);
    }
}
