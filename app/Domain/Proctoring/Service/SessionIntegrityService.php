<?php

declare(strict_types=1);

namespace App\Domain\Proctoring\Service;

use App\Core\Service\BaseService;
use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use Exception;
use DateTime;

class SessionIntegrityService extends BaseService
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getConnection();
    }

    public function lockSession(string $examSessionId): void
    {
        $now = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->update('exam_sessions', [
            'status' => 'submitted', // Or locked state? Status is usually sufficient
            'locked_at' => $now,
            'updated_at' => $now,
        ], ['id' => $examSessionId]);
    }

    public function generateIntegrityHash(string $examSessionId): string
    {
        // Hash all answers for this session
        $answers = $this->db->fetchAllAssociative(
            'SELECT question_id, answer_payload FROM exam_session_answers WHERE exam_session_id = ? ORDER BY question_id ASC',
            [$examSessionId]
        );

        $payload = json_encode($answers, JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $payload);

        $this->db->update('exam_sessions', [
            'integrity_hash' => $hash,
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ], ['id' => $examSessionId]);

        return $hash;
    }

    public function validateSessionToken(string $examSessionId, string $token): bool
    {
        // Get the latest proctoring session for this exam session
        $session = $this->db->fetchAssociative(
            'SELECT session_token FROM proctoring_sessions WHERE exam_session_id = ? ORDER BY start_time DESC LIMIT 1',
            [$examSessionId]
        );

        if (!$session) {
            return false; // No session started? Or invalid?
        }

        return hash_equals($session['session_token'], $token);
    }
}
