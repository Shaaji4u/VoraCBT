<?php

declare(strict_types=1);

namespace App\Domain\Exam\Service;

use App\Core\Service\BaseService;
use App\Core\Database\DatabaseManager;
use App\Infrastructure\Queue\QueueInterface;
use App\Domain\Proctoring\Service\SessionIntegrityService;
use App\Core\Service\RateLimitService;
use Doctrine\DBAL\Connection;
use DateTime;
use Exception;

class SubmissionService extends BaseService
{
    private Connection $db;
    private TimerService $timerService;
    private QueueInterface $queue;
    private SessionIntegrityService $integrityService;
    private RateLimitService $rateLimitService;

    public function __construct(
        TimerService $timerService,
        QueueInterface $queue,
        SessionIntegrityService $integrityService,
        RateLimitService $rateLimitService
    ) {
        $this->db = DatabaseManager::getConnection();
        $this->timerService = $timerService;
        $this->queue = $queue;
        $this->integrityService = $integrityService;
        $this->rateLimitService = $rateLimitService;
    }

    public function submitSession(string $sessionId): void
    {
        // Rate Limit: 1 submission every 5 seconds per session (to prevent double clicks)
        $rateKey = "exam_submit:$sessionId";
        if (!$this->rateLimitService->check($rateKey, 1, 5)) {
             throw new Exception('Too many submission attempts. Please wait.');
        }

        $this->db->beginTransaction();

        try {
            $session = $this->db->fetchAssociative(
                'SELECT * FROM exam_sessions WHERE id = ?',
                [$sessionId]
            );

            if (!$session) {
                throw new Exception('Session not found.');
            }

            if ($session['status'] === 'submitted' || $session['status'] === 'graded') {
                 $this->db->commit();
                 return;
            }

            // Check time
            $remaining = $this->timerService->getRemainingTime($sessionId);

            // Allow a small buffer (e.g. 30 seconds) for latency
            if ($remaining < -30) {
                 throw new Exception('Time expired.');
            }

            // Lock session and generate hash
            $this->integrityService->lockSession($sessionId);
            $this->integrityService->generateIntegrityHash($sessionId);

            $now = (new DateTime())->format('Y-m-d H:i:s');

            // Set end time if not set by lockSession (lockSession sets status and locked_at)
            $this->db->update('exam_sessions', [
                'end_time' => $now,
                'updated_at' => $now,
            ], ['id' => $sessionId]);

            // Trigger grading job via Queue
            $this->queue->push('App\Domain\Grading\Job\GradingJob', ['exam_session_id' => $sessionId]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
