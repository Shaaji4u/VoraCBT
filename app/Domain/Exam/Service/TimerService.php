<?php

declare(strict_types=1);

namespace App\Domain\Exam\Service;

use App\Core\Service\BaseService;
use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use DateTime;

class TimerService extends BaseService
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getConnection();
    }

    public function getRemainingTime(string $sessionId): int
    {
        $session = $this->db->fetchAssociative(
            'SELECT es.start_time, es.current_section_id,
                    et.duration_minutes as exam_duration,
                    s.duration_minutes as section_duration
             FROM exam_sessions es
             JOIN exam_templates et ON es.exam_template_id = et.id
             LEFT JOIN exam_sections s ON es.current_section_id = s.id
             WHERE es.id = ?',
            [$sessionId]
        );

        if (!$session) {
            return 0;
        }

        $now = new DateTime();
        $remainingTimes = [];

        // 1. Check Exam Total Duration
        if ($session['exam_duration'] && $session['exam_duration'] > 0) {
            $examStartTime = new DateTime($session['start_time']);
            $examEndTime = (clone $examStartTime)->modify("+{$session['exam_duration']} minutes");
            $remainingTimes[] = $examEndTime->getTimestamp() - $now->getTimestamp();
        }

        // 2. Check Section Duration
        if ($session['current_section_id'] && $session['section_duration'] && $session['section_duration'] > 0) {
            // Retrieve authoritative start time from exam_session_sections
            $sectionStatus = $this->db->fetchAssociative(
                'SELECT started_at FROM exam_session_sections WHERE exam_session_id = ? AND exam_section_id = ?',
                [$sessionId, $session['current_section_id']]
            );

            if ($sectionStatus && $sectionStatus['started_at']) {
                $sectionStartTime = new DateTime($sectionStatus['started_at']);
                $sectionEndTime = (clone $sectionStartTime)->modify("+{$session['section_duration']} minutes");
                $remainingTimes[] = $sectionEndTime->getTimestamp() - $now->getTimestamp();
            }
        }

        if (empty($remainingTimes)) {
            return PHP_INT_MAX; // Unlimited
        }

        return min($remainingTimes);
    }
}
