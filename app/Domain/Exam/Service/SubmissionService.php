<?php

declare(strict_types=1);

namespace App\Domain\Exam\Service;

use App\Core\Service\BaseService;
use App\Core\Database\DatabaseManager;
use App\Infrastructure\Queue\QueueInterface;
use Doctrine\DBAL\Connection;
use DateTime;
use Exception;

class SubmissionService extends BaseService
{
    private Connection $db;
    private TimerService $timerService;
    private QueueInterface $queue;

    public function __construct(
        TimerService $timerService,
        QueueInterface $queue
    ) {
        $this->db = DatabaseManager::getConnection();
        $this->timerService = $timerService;
        $this->queue = $queue;
    }

    public function submitSession(string $sessionId): void
    {
        $this->db->beginTransaction();

        try {
            // Lock session
            // Using FOR UPDATE if possible, or relying on transaction isolation.
            // SQLite might not support FOR UPDATE in this driver context effectively, but intent is clear.

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

            $now = (new DateTime())->format('Y-m-d H:i:s');

            $this->db->update('exam_sessions', [
                'status' => 'submitted',
                'end_time' => $now,
                'updated_at' => $now,
            ], ['id' => $sessionId]);

            // Trigger grading job via Queue
            // Assuming GradingJob class exists or will exist.
            $this->queue->push('App\Domain\Grading\Job\GradingJob', ['exam_session_id' => $sessionId]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
