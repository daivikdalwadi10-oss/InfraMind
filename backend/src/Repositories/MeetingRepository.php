<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Models\Meeting;
use InfraMind\Models\MeetingStatus;

/**
 * Meeting repository for database operations.
 */
class MeetingRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new meeting.
     */
    public function create(Meeting $meeting): Meeting
    {
        $sql = 'INSERT INTO meetings (
                    id, title, agenda, status, scheduled_at, duration_minutes,
                    organizer_id, analysis_id, incident_id, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $meeting->id,
            $meeting->title,
            $meeting->agenda,
            $meeting->status->value,
            $meeting->scheduledAt,
            $meeting->durationMinutes,
            $meeting->organizerId,
            $meeting->analysisId,
            $meeting->incidentId,
            $meeting->createdAt,
            $meeting->updatedAt,
        ]);

        return $meeting;
    }

    /**
     * Find meeting by ID.
     */
    public function findById(string $id): ?Meeting
    {
        $sql = 'SELECT * FROM meetings WHERE id = ?';
        $row = $this->db->fetchOne($sql, [$id]);

        return $row ? $this->mapRowToMeeting($row) : null;
    }

    /**
     * List meetings.
     */
    public function listAll(int $limit = 50, int $offset = 0, ?string $status = null): array
    {
        $sql = 'SELECT * FROM meetings WHERE 1=1';
        $params = [];

        if ($status) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY scheduled_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->db->fetchAll($sql, $params);
        return array_map([$this, 'mapRowToMeeting'], $rows);
    }

    /**
     * Update meeting.
     */
    public function update(Meeting $meeting): Meeting
    {
        $sql = 'UPDATE meetings SET
                    title = ?, agenda = ?, status = ?, scheduled_at = ?, duration_minutes = ?,
                    organizer_id = ?, analysis_id = ?, incident_id = ?, updated_at = ?
                WHERE id = ?';

        $this->db->execute($sql, [
            $meeting->title,
            $meeting->agenda,
            $meeting->status->value,
            $meeting->scheduledAt,
            $meeting->durationMinutes,
            $meeting->organizerId,
            $meeting->analysisId,
            $meeting->incidentId,
            $meeting->updatedAt,
            $meeting->id,
        ]);

        return $meeting;
    }

    /**
     * Map database row to Meeting model.
     */
    private function mapRowToMeeting(array $row): Meeting
    {
        return new Meeting(
            $row['id'],
            $row['title'],
            $row['agenda'] ?? null,
            MeetingStatus::from($row['status']),
            $row['scheduled_at'],
            (int) $row['duration_minutes'],
            $row['organizer_id'],
            $row['analysis_id'] ?? null,
            $row['incident_id'] ?? null,
            $row['created_at'],
            $row['updated_at'],
        );
    }
}
