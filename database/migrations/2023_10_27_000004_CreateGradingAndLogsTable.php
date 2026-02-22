<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateGradingAndLogsTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Grading Results (Separate from sessions if multiple attempts or detailed breakdown needed)
        // Or can be part of sessions. But prompt says "Grading & Analytics".
        // Let's create 'exam_results' table.
        $results = $schema->createTable('exam_results');
        $results->addColumn('id', Types::GUID);
        $results->addColumn('exam_session_id', Types::GUID);
        $results->addColumn('total_score', Types::FLOAT);
        $results->addColumn('max_score', Types::FLOAT);
        $results->addColumn('percentage', Types::FLOAT);
        $results->addColumn('grade', Types::STRING)->setLength(10)->setNotnull(false);
        $results->addColumn('is_passed', Types::BOOLEAN);
        $results->addColumn('graded_at', Types::DATETIME_MUTABLE);
        $results->addColumn('graded_by', Types::GUID)->setNotnull(false); // If manual grading
        $results->setPrimaryKey(['id']);
        $results->addUniqueIndex(['exam_session_id']); // One result per session
        $results->addForeignKeyConstraint('exam_sessions', ['exam_session_id'], ['id'], ['onDelete' => 'CASCADE']);
        $results->addForeignKeyConstraint('users', ['graded_by'], ['id'], ['onDelete' => 'SET NULL']);

        // Analytics (Aggregated data)
        $analytics = $schema->createTable('exam_analytics');
        $analytics->addColumn('id', Types::GUID);
        $analytics->addColumn('exam_template_id', Types::GUID);
        $analytics->addColumn('metrics', Types::JSON); // {"avg_score": 75, "pass_rate": 80, ...}
        $analytics->addColumn('calculated_at', Types::DATETIME_MUTABLE);
        $analytics->setPrimaryKey(['id']);
        $analytics->addIndex(['exam_template_id']);
        $analytics->addForeignKeyConstraint('exam_templates', ['exam_template_id'], ['id'], ['onDelete' => 'CASCADE']);

        // Integration Logs
        $logs = $schema->createTable('integration_logs');
        $logs->addColumn('id', Types::GUID);
        $logs->addColumn('service_name', Types::STRING)->setLength(100);
        $logs->addColumn('action', Types::STRING)->setLength(100);
        $logs->addColumn('request_payload', Types::JSON)->setNotnull(false);
        $logs->addColumn('response_payload', Types::JSON)->setNotnull(false);
        $logs->addColumn('status_code', Types::INTEGER)->setNotnull(false);
        $logs->addColumn('error_message', Types::TEXT)->setNotnull(false);
        $logs->addColumn('created_at', Types::DATETIME_MUTABLE);
        $logs->addColumn('idempotency_key', Types::STRING)->setLength(100)->setNotnull(false);
        $logs->setPrimaryKey(['id']);
        $logs->addIndex(['created_at']);
        $logs->addUniqueIndex(['idempotency_key']); // Ensure idempotency

        // Proctoring Sessions
        $proctoring = $schema->createTable('proctoring_sessions');
        $proctoring->addColumn('id', Types::GUID);
        $proctoring->addColumn('exam_session_id', Types::GUID);
        $proctoring->addColumn('events', Types::JSON)->setNotnull(false); // [{"timestamp": "...", "type": "focus_lost"}, ...]
        $proctoring->addColumn('risk_score', Types::FLOAT)->setDefault(0.0);
        $proctoring->addColumn('report_url', Types::STRING)->setLength(255)->setNotnull(false);
        $proctoring->addColumn('created_at', Types::DATETIME_MUTABLE);
        $proctoring->setPrimaryKey(['id']);
        $proctoring->addUniqueIndex(['exam_session_id']);
        $proctoring->addForeignKeyConstraint('exam_sessions', ['exam_session_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('proctoring_sessions');
        $schema->dropTable('integration_logs');
        $schema->dropTable('exam_analytics');
        $schema->dropTable('exam_results');
    }
}
