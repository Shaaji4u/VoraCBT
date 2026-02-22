<?php

declare(strict_types=1);

namespace App\Domain\Exam\Service;

use DateTime;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class SessionRecoveryService
{
    public function __construct(
        private Connection $db,
        private TimerService $timerService
    ) {}

    public function autosave(
        string $sessionId,
        ?string $lastQuestionId,
        array $draftPayload,
        ?string $clientTimestamp = null
    ): array {
        $session = $this->db->fetchAssociative('SELECT status FROM exam_sessions WHERE id = ?', [$sessionId]);
        if (!$session || $session['status'] !== 'in_progress') {
            throw new RuntimeException('Session is not active.');
        }

        if ($this->timerService->getRemainingTime($sessionId) < -10) {
            throw new RuntimeException('Session expired.');
        }

        $serverTs = (new DateTime())->format('Y-m-d H:i:s');
        $checksum = hash('sha256', json_encode($draftPayload, JSON_THROW_ON_ERROR));

        $existingId = $this->db->fetchOne('SELECT id FROM exam_session_checkpoints WHERE exam_session_id = ?', [$sessionId]);

        $row = [
            'exam_session_id' => $sessionId,
            'last_answered_question_id' => $lastQuestionId,
            'draft_payload' => json_encode($draftPayload, JSON_THROW_ON_ERROR),
            'client_timestamp' => $clientTimestamp,
            'server_timestamp' => $serverTs,
            'checksum' => $checksum,
        ];

        if ($existingId) {
            $this->db->update('exam_session_checkpoints', $row, ['id' => $existingId]);
        } else {
            $row['id'] = Uuid::uuid4()->toString();
            $this->db->insert('exam_session_checkpoints', $row);
        }

        return [
            'session_id' => $sessionId,
            'server_timestamp' => $serverTs,
            'checksum' => $checksum,
            'remaining_seconds' => $this->timerService->getRemainingTime($sessionId),
        ];
    }

    public function resumeState(string $sessionId): array
    {
        $session = $this->db->fetchAssociative('SELECT id, status, start_time FROM exam_sessions WHERE id = ?', [$sessionId]);
        if (!$session) {
            throw new RuntimeException('Session not found.');
        }

        $checkpoint = $this->db->fetchAssociative(
            'SELECT last_answered_question_id, draft_payload, client_timestamp, server_timestamp, checksum FROM exam_session_checkpoints WHERE exam_session_id = ?',
            [$sessionId]
        );

        return [
            'session' => $session,
            'remaining_seconds' => $this->timerService->getRemainingTime($sessionId),
            'checkpoint' => $checkpoint ?: null,
        ];
    }
}
