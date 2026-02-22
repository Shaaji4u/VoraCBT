<?php

declare(strict_types=1);

namespace App\Domain\Exam\Service;

use App\Core\Service\BaseService;
use App\Core\Database\DatabaseManager;
use App\Infrastructure\Queue\QueueInterface;
use App\Domain\Proctoring\Service\ProctoringService;
use App\Domain\Proctoring\Service\SessionIntegrityService;
use App\Core\Service\RateLimitService;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use DateTime;
use Exception;

class ExamSessionService extends BaseService
{
    private Connection $db;
    private RandomizationService $randomizationService;
    private TimerService $timerService;
    private QueueInterface $queue;
    private ProctoringService $proctoringService;
    private SessionIntegrityService $integrityService;
    private RateLimitService $rateLimitService;

    public function __construct(
        RandomizationService $randomizationService,
        TimerService $timerService,
        QueueInterface $queue,
        ProctoringService $proctoringService,
        SessionIntegrityService $integrityService,
        RateLimitService $rateLimitService
    ) {
        $this->db = DatabaseManager::getConnection();
        $this->randomizationService = $randomizationService;
        $this->timerService = $timerService;
        $this->queue = $queue;
        $this->proctoringService = $proctoringService;
        $this->integrityService = $integrityService;
        $this->rateLimitService = $rateLimitService;
    }

    public function startSession(string $examTemplateId, string $userId, array $metadata = []): array
    {
        // Rate Limiting: 1 start attempt per minute per user
        $rateKey = "exam_start:$userId:$examTemplateId";
        if (!$this->rateLimitService->check($rateKey, 1, 60)) {
            throw new Exception('Too many start attempts. Please wait.');
        }

        // Check if session already exists
        $existing = $this->db->fetchAssociative(
            'SELECT * FROM exam_sessions WHERE exam_template_id = ? AND user_id = ? AND status IN (?, ?, ?)',
            [$examTemplateId, $userId, 'started', 'in_progress', 'submitted']
        );

        if ($existing) {
            if ($existing['status'] === 'submitted' || $existing['status'] === 'graded') {
                throw new Exception('Exam already submitted.');
            }
            // Resume existing session
            // Log resume event/update session metadata (e.g. new IP)
            // Ideally we start a new proctoring "segment" or update the existing one if we just want to track current IP.
            // For now, let's start a new proctoring session to track the resume as a distinct activity period or just log it?
            // "Repeated IP change" detection suggests we track IP changes.
            // Let's create a NEW proctoring session for the resume to ensure we capture the new metadata (IP, Device).
            // This also issues a NEW session token, invalidating the old one (multi-tab prevention).

            $token = $this->proctoringService->startProctoringSession($existing['id'], $userId, $metadata);

            return ['id' => $existing['id'], 'token' => $token];
        }

        $this->db->beginTransaction();

        try {
            $id = Uuid::uuid4()->toString();
            $seed = mt_rand();

            $now = (new DateTime())->format('Y-m-d H:i:s');

            $this->db->insert('exam_sessions', [
                'id' => $id,
                'exam_template_id' => $examTemplateId,
                'user_id' => $userId,
                'status' => 'in_progress',
                'start_time' => $now,
                'created_at' => $now,
                'updated_at' => $now,
                'seed' => (string)$seed,
            ]);

            $this->randomizationService->generateQuestions($examTemplateId, $id, $seed);

            // Start Proctoring Session
            $token = $this->proctoringService->startProctoringSession($id, $userId, $metadata);

            $this->db->commit();

            return ['id' => $id, 'token' => $token];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // submitSession moved to SubmissionService

    public function saveAnswer(string $sessionId, string $questionId, array $payload, string $token): void
    {
        // Validate Session Token (Multi-tab Prevention)
        if (!$this->integrityService->validateSessionToken($sessionId, $token)) {
             throw new Exception('Session invalidated. Multiple tabs or devices detected.');
        }

        // Optimistic check without transaction for speed, or light transaction?
        // Prompt says "Transaction-safe writes".

        // Check status
        $session = $this->db->fetchAssociative('SELECT status FROM exam_sessions WHERE id = ?', [$sessionId]);
        if (!$session || $session['status'] !== 'in_progress') {
             throw new Exception('Session is not active.');
        }

        // Check time
        if ($this->timerService->getRemainingTime($sessionId) < -10) {
             throw new Exception('Time expired.');
        }

        $this->db->beginTransaction();
        try {
            // Upsert Answer
            // DBAL doesn't have `upsert` method directly across all platforms.
            // Check if exists
            $existing = $this->db->fetchOne('SELECT id FROM exam_session_answers WHERE exam_session_id = ? AND question_id = ?', [$sessionId, $questionId]);

            $now = (new DateTime())->format('Y-m-d H:i:s');

            if ($existing) {
                $this->db->update('exam_session_answers', [
                    'answer_payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                    'updated_at' => $now,
                ], ['id' => $existing]);
            } else {
                $this->db->insert('exam_session_answers', [
                    'id' => Uuid::uuid4()->toString(),
                    'exam_session_id' => $sessionId,
                    'question_id' => $questionId,
                    'answer_payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Update exam_session_questions status
            $this->db->update('exam_session_questions', [
                'status' => 'answered',
                'updated_at' => $now,
            ], ['exam_session_id' => $sessionId, 'question_id' => $questionId]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        // Push analytics update job (async, so outside transaction effectively, or within if queue logic requires)
        // Usually safe to push after commit or before.
        $this->queue->push('App\Domain\Analytics\Job\UpdateAnalyticsJob', ['exam_session_id' => $sessionId]);
    }

    public function startSection(string $sessionId, string $sectionId): void
    {
        $session = $this->db->fetchAssociative('SELECT * FROM exam_sessions WHERE id = ?', [$sessionId]);
        if (!$session || $session['status'] !== 'in_progress') {
            throw new Exception('Session not active.');
        }

        // Check if section belongs to exam
        $section = $this->db->fetchAssociative('SELECT * FROM exam_sections WHERE id = ? AND exam_template_id = ?', [$sectionId, $session['exam_template_id']]);
        if (!$section) {
            throw new Exception('Invalid section.');
        }

        // Check locks if configured
        $template = $this->db->fetchAssociative('SELECT lock_sections FROM exam_templates WHERE id = ?', [$session['exam_template_id']]);
        if ($template && $template['lock_sections']) {
            // Check if any previous section (lower order) is completed? Or if trying to access a completed section?
            // "Prevent backward access if locked". Usually means "once you leave a section or submit it, you can't go back".

            // Check if target section is already completed
            $sectionStatus = $this->db->fetchAssociative(
                'SELECT status FROM exam_session_sections WHERE exam_session_id = ? AND exam_section_id = ?',
                [$sessionId, $sectionId]
            );

            if ($sectionStatus && $sectionStatus['status'] === 'completed') {
                throw new Exception('Section is locked.');
            }

            // If switching FROM a section, mark it completed?
            if ($session['current_section_id'] && $session['current_section_id'] !== $sectionId) {
                // Mark previous as completed if switching away?
                // Or does "submit section" handle that?
                // "Section auto-submit when time expires" implies manual submit too.
                // Assuming implicit submit on switch if strictly sequential?
                // Let's just enforce: Cannot go back to a section that is "completed".
                // Logic for "marking completed" is separate (e.g. user action).
                // But for now, if just switching, we don't auto-complete unless forced.
            }
        }

        $now = (new DateTime())->format('Y-m-d H:i:s');

        // Upsert section status
        // Check if exists
        $sessionSection = $this->db->fetchAssociative(
            'SELECT * FROM exam_session_sections WHERE exam_session_id = ? AND exam_section_id = ?',
            [$sessionId, $sectionId]
        );

        if (!$sessionSection) {
            $this->db->insert('exam_session_sections', [
                'id' => Uuid::uuid4()->toString(),
                'exam_session_id' => $sessionId,
                'exam_section_id' => $sectionId,
                'status' => 'in_progress',
                'started_at' => $now,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        } else {
            // Already started. Do not update started_at.
            // Just update updated_at
             $this->db->update('exam_session_sections', [
                'updated_at' => $now
            ], ['id' => $sessionSection['id']]);
        }

        // Update current section pointer
        $this->db->update('exam_sessions', [
            'current_section_id' => $sectionId,
            // section_started_at in exam_sessions is now redundant or just "current active pointer time".
            // But we should rely on exam_session_sections for authoritative timing.
            // Keeping it for backward compat or easy lookup of "when did I enter THIS specific instance of access"?
            // But prompt says "Section timer must be server-authoritative".
            // If I re-enter, do I get full time? No.
            // So TimerService must use the FIRST `started_at` from `exam_session_sections`.
            'section_started_at' => $sessionSection ? $sessionSection['started_at'] : $now,
            'updated_at' => $now,
        ], ['id' => $sessionId]);
    }

    public function completeSection(string $sessionId, string $sectionId): void
    {
        $session = $this->db->fetchAssociative('SELECT * FROM exam_sessions WHERE id = ?', [$sessionId]);
        if (!$session || $session['status'] !== 'in_progress') {
            throw new Exception('Session not active.');
        }

        // Verify section belongs to exam
        $section = $this->db->fetchAssociative('SELECT * FROM exam_sections WHERE id = ? AND exam_template_id = ?', [$sectionId, $session['exam_template_id']]);
        if (!$section) {
            throw new Exception('Invalid section.');
        }

        // Verify session section record exists (must be started first)
        $sessionSection = $this->db->fetchAssociative(
            'SELECT * FROM exam_session_sections WHERE exam_session_id = ? AND exam_section_id = ?',
            [$sessionId, $sectionId]
        );

        if (!$sessionSection) {
            throw new Exception('Section not started.');
        }

        if ($sessionSection['status'] === 'completed') {
            return; // Idempotent
        }

        $now = (new DateTime())->format('Y-m-d H:i:s');

        $this->db->update('exam_session_sections', [
            'status' => 'completed',
            'completed_at' => $now,
            'updated_at' => $now
        ], ['id' => $sessionSection['id']]);
    }
}
