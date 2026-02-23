<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Database\DatabaseManager;
use App\Core\Http\ApiResponse;
use Exception;

class StudentDashboardController
{
    public function overview(): void
    {
        try {
            $db = DatabaseManager::getConnection();

            $userId = $_GET['user_id'] ?? null;
            if (!$userId) {
                ApiResponse::error('Missing user_id', 400)->send();
                return;
            }

            $inProgress = [];
            $upcoming = [];
            $history = [];

            try {
                $inProgress = $db->fetchAllAssociative(
                    "SELECT s.id, s.status, s.start_time, e.title, e.subject, e.duration_minutes
                     FROM exam_sessions s
                     INNER JOIN exam_templates e ON e.id = s.exam_template_id
                     WHERE s.user_id = ? AND s.status IN ('started', 'in_progress')
                     ORDER BY s.updated_at DESC
                     LIMIT 5",
                    [$userId]
                );
            } catch (Exception $e) {
                $inProgress = [];
            }

            try {
                $upcoming = $db->fetchAllAssociative(
                    "SELECT id, title, subject, duration_minutes, total_marks, start_time, end_time
                     FROM exam_templates
                     ORDER BY start_time ASC
                     LIMIT 12"
                );
            } catch (Exception $e) {
                $upcoming = [];
            }

            try {
                $history = $db->fetchAllAssociative(
                    "SELECT s.id, s.score, s.end_time, e.title, e.subject
                     FROM exam_sessions s
                     INNER JOIN exam_templates e ON e.id = s.exam_template_id
                     WHERE s.user_id = ? AND s.status IN ('submitted', 'completed', 'graded')
                     ORDER BY s.end_time DESC
                     LIMIT 20",
                    [$userId]
                );
            } catch (Exception $e) {
                $history = [];
            }

            ApiResponse::json([
                'in_progress' => $inProgress,
                'upcoming' => $upcoming,
                'history' => $history,
            ])->send();
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500)->send();
        }
    }
}
