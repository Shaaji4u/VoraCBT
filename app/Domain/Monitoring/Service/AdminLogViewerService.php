<?php

declare(strict_types=1);

namespace App\Domain\Monitoring\Service;

use Doctrine\DBAL\Connection;

class AdminLogViewerService
{
    public function __construct(
        private Connection $db
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listLogs(array $filters = []): array
    {
        $limit = isset($filters['limit']) ? max(1, min(500, (int) $filters['limit'])) : 100;

        $params = [
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'user_id' => $filters['user_id'] ?? null,
            'exam_template_id' => $filters['exam_template_id'] ?? null,
            'event_source' => $filters['event_source'] ?? null,
        ];

        $sql = "
            SELECT * FROM (
                SELECT
                    sl.id,
                    'security' AS event_source,
                    sl.event_type AS event_name,
                    sl.user_id,
                    es.exam_template_id,
                    sl.severity,
                    sl.description AS summary,
                    NULL AS details,
                    sl.created_at AS occurred_at
                FROM security_logs sl
                LEFT JOIN exam_sessions es ON es.user_id = sl.user_id

                UNION ALL

                SELECT
                    il.id,
                    'identity' AS event_source,
                    il.action AS event_name,
                    il.performed_by AS user_id,
                    NULL AS exam_template_id,
                    'info' AS severity,
                    il.action AS summary,
                    il.details,
                    il.timestamp AS occurred_at
                FROM identity_logs il

                UNION ALL

                SELECT
                    pl.id,
                    'proctoring' AS event_source,
                    pl.event_type AS event_name,
                    ps.student_id AS user_id,
                    es.exam_template_id,
                    pl.severity,
                    pl.event_type AS summary,
                    pl.payload AS details,
                    pl.created_at AS occurred_at
                FROM proctoring_logs pl
                INNER JOIN proctoring_sessions ps ON ps.id = pl.proctoring_session_id
                INNER JOIN exam_sessions es ON es.id = ps.exam_session_id

                UNION ALL

                SELECT
                    il2.id,
                    'integration' AS event_source,
                    il2.status AS event_name,
                    NULL AS user_id,
                    NULL AS exam_template_id,
                    CASE WHEN il2.status IN ('500', 'FAILED', 'ERROR') THEN 'critical' ELSE 'info' END AS severity,
                    il2.status AS summary,
                    il2.request_payload AS details,
                    il2.created_at AS occurred_at
                FROM integration_logs il2

                UNION ALL

                SELECT
                    rlal.id,
                    'result_lock' AS event_source,
                    rlal.action AS event_name,
                    rlal.actor_user_id AS user_id,
                    es.exam_template_id,
                    'warning' AS severity,
                    rlal.action AS summary,
                    rlal.notes AS details,
                    rlal.created_at AS occurred_at
                FROM result_lock_audit_logs rlal
                INNER JOIN exam_results er ON er.id = rlal.exam_result_id
                INNER JOIN exam_sessions es ON es.id = er.exam_session_id

                UNION ALL

                SELECT
                    ell.id,
                    'exam_lifecycle' AS event_source,
                    CONCAT(ell.old_state, '->', ell.new_state) AS event_name,
                    ell.changed_by AS user_id,
                    ell.exam_template_id,
                    'info' AS severity,
                    CONCAT('Exam lifecycle changed from ', ell.old_state, ' to ', ell.new_state) AS summary,
                    ell.reason AS details,
                    ell.created_at AS occurred_at
                FROM exam_lifecycle_logs ell
            ) logs
            WHERE (:from IS NULL OR logs.occurred_at >= :from)
              AND (:to IS NULL OR logs.occurred_at <= :to)
              AND (:user_id IS NULL OR logs.user_id = :user_id)
              AND (:exam_template_id IS NULL OR logs.exam_template_id = :exam_template_id)
              AND (:event_source IS NULL OR logs.event_source = :event_source)
            ORDER BY logs.occurred_at DESC
            LIMIT $limit
        ";

        return $this->db->fetchAllAssociative($sql, $params);
    }
}
