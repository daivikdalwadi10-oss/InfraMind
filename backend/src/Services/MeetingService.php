<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Repositories\MeetingRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Models\Meeting;
use InfraMind\Models\MeetingStatus;
use InfraMind\Utils\Utils;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Core\Logger;

/**
 * Meeting service.
 */
class MeetingService
{
    private MeetingRepository $meetingRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->meetingRepository = new MeetingRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * Create meeting.
     */
    public function createMeeting(
        string $organizerId,
        string $title,
        ?string $agenda,
        string $status,
        string $scheduledAt,
        int $durationMinutes,
        ?string $analysisId,
        ?string $incidentId,
    ): Meeting {
        $meetingId = Utils::generateUuid();
        $now = Utils::now();

        $meeting = new Meeting(
            $meetingId,
            $title,
            $agenda,
            MeetingStatus::from($status),
            $scheduledAt,
            $durationMinutes,
            $organizerId,
            $analysisId,
            $incidentId,
            $now,
            $now,
        );

        $meeting = $this->meetingRepository->create($meeting);

        $this->auditRepository->log('Meeting', $meetingId, 'CREATED', $organizerId, [
            'status' => $status,
            'scheduledAt' => $scheduledAt,
        ]);

        $this->logger->info("Meeting created: $meetingId by: $organizerId");

        return $meeting;
    }

    /**
     * Get meeting by ID.
     */
    public function getMeeting(string $meetingId): Meeting
    {
        $meeting = $this->meetingRepository->findById($meetingId);
        if (!$meeting) {
            throw new NotFoundException('Meeting not found');
        }
        return $meeting;
    }

    /**
     * List meetings.
     */
    public function listAll(int $limit = 50, int $offset = 0, ?string $status = null): array
    {
        return $this->meetingRepository->listAll($limit, $offset, $status);
    }

    /**
     * Update meeting.
     */
    public function updateMeeting(string $meetingId, array $data, string $userId): Meeting
    {
        $meeting = $this->getMeeting($meetingId);
        $before = $meeting->toArray();

        if (isset($data['title'])) {
            $meeting->title = $data['title'];
        }

        if (array_key_exists('agenda', $data)) {
            $meeting->agenda = $data['agenda'];
        }

        if (isset($data['status'])) {
            $meeting->status = MeetingStatus::from($data['status']);
        }

        if (isset($data['scheduledAt'])) {
            $meeting->scheduledAt = $data['scheduledAt'];
        }

        if (isset($data['durationMinutes'])) {
            $meeting->durationMinutes = (int) $data['durationMinutes'];
        }

        if (array_key_exists('analysisId', $data)) {
            $meeting->analysisId = $data['analysisId'];
        }

        if (array_key_exists('incidentId', $data)) {
            $meeting->incidentId = $data['incidentId'];
        }

        $meeting->updatedAt = Utils::now();

        $meeting = $this->meetingRepository->update($meeting);

        $this->auditRepository->log('Meeting', $meetingId, 'UPDATED', $userId, [
            'before' => $before,
            'after' => $meeting->toArray(),
        ]);

        return $meeting;
    }
}
