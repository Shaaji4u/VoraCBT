<?php

declare(strict_types=1);

namespace App\Domain\Proctoring\Service;

use App\Core\Service\BaseService;
use App\Core\Database\DatabaseManager;
use App\Core\Service\AuditLogService;
use App\Domain\Proctoring\Service\DeviceFingerprintService;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;
use Exception;

class ProctoringService extends BaseService
{
    private Connection $db;
    private AuditLogService $auditLog;
    private DeviceFingerprintService $fingerprintService;

    public function __construct(
        AuditLogService $auditLog,
        DeviceFingerprintService $fingerprintService
    ) {
        $this->db = DatabaseManager::getConnection();
        $this->auditLog = $auditLog;
        $this->fingerprintService = $fingerprintService;
    }

    public function startProctoringSession(
        string $examSessionId,
        string $studentId,
        array $metadata
    ): string {
        $id = Uuid::uuid4()->toString();
        $now = (new DateTime())->format('Y-m-d H:i:s');

        $ip = $metadata['ip'] ?? '0.0.0.0';
        $userAgent = $metadata['user_agent'] ?? '';
        $deviceHash = $metadata['device_hash'] ?? $this->fingerprintService->generate($metadata['headers'] ?? [], $ip);

        // Generate a new session token to prevent multiple tabs
        $token = bin2hex(random_bytes(32));

        $this->db->insert('proctoring_sessions', [
            'id' => $id,
            'exam_session_id' => $examSessionId,
            'student_id' => $studentId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_hash' => $deviceHash,
            'session_token' => $token,
            'start_time' => $now,
            'suspicious_events_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->auditLog->log('exam_start', "Exam started for session $examSessionId", $studentId, [
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_hash' => $deviceHash
        ]);

        return $token;
    }

    public function logEvent(string $proctoringSessionId, string $eventType, array $payload = [], string $severity = 'info'): void
    {
        $id = Uuid::uuid4()->toString();
        $now = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->insert('proctoring_logs', [
            'id' => $id,
            'proctoring_session_id' => $proctoringSessionId,
            'event_type' => $eventType,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'severity' => $severity,
            'created_at' => $now,
        ]);

        if ($severity === 'warning' || $severity === 'critical') {
            // Increment suspicious counter
            $this->db->executeQuery(
                'UPDATE proctoring_sessions SET suspicious_events_count = suspicious_events_count + 1, updated_at = ? WHERE id = ?',
                [$now, $proctoringSessionId]
            );

            // Check thresholds and flag session if needed
            $this->checkThresholds($proctoringSessionId);
        }
    }

    private function checkThresholds(string $sessionId): void
    {
        // Simple threshold check
        $session = $this->db->fetchAssociative('SELECT * FROM proctoring_sessions WHERE id = ?', [$sessionId]);
        if (!$session) return;

        // Get configured thresholds from exam template
        $exam = $this->db->fetchAssociative(
            'SELECT t.proctoring_config
             FROM exam_templates t
             JOIN exam_sessions s ON s.exam_template_id = t.id
             WHERE s.id = ?',
            [$session['exam_session_id']]
        );

        // If fetch fails, default to empty
        if (!$exam) {
            return;
        }

        $config = isset($exam['proctoring_config']) ? json_decode($exam['proctoring_config'], true) : [];
        $limit = $config['suspicious_limit'] ?? 5;

        if ($session['suspicious_events_count'] >= $limit) {
            // Flag the session
            // We store flags in 'flags' JSON column
            $flags = $session['flags'] ? json_decode($session['flags'], true) : [];
            if (!in_array('HIGH_SUSPICION', $flags)) {
                $flags[] = 'HIGH_SUSPICION';
                $this->db->update('proctoring_sessions', [
                    'flags' => json_encode($flags),
                    'updated_at' => (new DateTime())->format('Y-m-d H:i:s')
                ], ['id' => $sessionId]);

                $this->auditLog->log('suspicious_flag', "Session flagged for high suspicious activity", $session['student_id'], [], 'warning');
            }
        }
    }

    public function getSessionToken(string $examSessionId): ?string
    {
        $session = $this->db->fetchAssociative(
            'SELECT session_token FROM proctoring_sessions WHERE exam_session_id = ? ORDER BY start_time DESC LIMIT 1',
            [$examSessionId]
        );
        return $session['session_token'] ?? null;
    }

    public function getSessionIdByToken(string $token): ?string
    {
        $session = $this->db->fetchAssociative(
            'SELECT id FROM proctoring_sessions WHERE session_token = ?',
            [$token]
        );
        return $session['id'] ?? null;
    }
}
