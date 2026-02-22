<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateProctoringSchema extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // 1. Proctoring Sessions
        // Drop old table if exists (from previous migration)
        if ($schema->hasTable('proctoring_sessions')) {
            $schema->dropTable('proctoring_sessions');
        }

        $sessions = $schema->createTable('proctoring_sessions');
        $sessions->addColumn('id', Types::GUID);
        $sessions->addColumn('exam_session_id', Types::GUID);
        $sessions->addColumn('student_id', Types::GUID);
        $sessions->addColumn('ip_address', Types::STRING)->setLength(45); // IPv6
        $sessions->addColumn('user_agent', Types::TEXT)->setNotnull(false);
        $sessions->addColumn('device_hash', Types::STRING)->setLength(64)->setNotnull(false);
        $sessions->addColumn('session_token', Types::STRING)->setLength(64)->setNotnull(false);
        $sessions->addColumn('start_time', Types::DATETIME_MUTABLE);
        $sessions->addColumn('end_time', Types::DATETIME_MUTABLE)->setNotnull(false);
        $sessions->addColumn('suspicious_events_count', Types::INTEGER)->setDefault(0);
        $sessions->addColumn('flags', Types::JSON)->setNotnull(false);
        $sessions->addColumn('created_at', Types::DATETIME_MUTABLE);
        $sessions->addColumn('updated_at', Types::DATETIME_MUTABLE);

        $sessions->setPrimaryKey(['id']);
        $sessions->addIndex(['exam_session_id']);
        $sessions->addForeignKeyConstraint('exam_sessions', ['exam_session_id'], ['id'], ['onDelete' => 'CASCADE']);
        $sessions->addForeignKeyConstraint('users', ['student_id'], ['id'], ['onDelete' => 'CASCADE']);

        // 2. Proctoring Logs
        $pLogs = $schema->createTable('proctoring_logs');
        $pLogs->addColumn('id', Types::GUID);
        $pLogs->addColumn('proctoring_session_id', Types::GUID);
        $pLogs->addColumn('event_type', Types::STRING)->setLength(50);
        $pLogs->addColumn('payload', Types::JSON)->setNotnull(false);
        $pLogs->addColumn('severity', Types::STRING)->setLength(20)->setDefault('info'); // info, warning, critical
        $pLogs->addColumn('created_at', Types::DATETIME_MUTABLE);

        $pLogs->setPrimaryKey(['id']);
        $pLogs->addIndex(['proctoring_session_id']);
        $pLogs->addForeignKeyConstraint('proctoring_sessions', ['proctoring_session_id'], ['id'], ['onDelete' => 'CASCADE']);

        // 3. Security Logs (Audit)
        $sLogs = $schema->createTable('security_logs');
        $sLogs->addColumn('id', Types::GUID);
        $sLogs->addColumn('user_id', Types::GUID)->setNotnull(false);
        $sLogs->addColumn('event_type', Types::STRING)->setLength(50);
        $sLogs->addColumn('ip_address', Types::STRING)->setLength(45)->setNotnull(false);
        $sLogs->addColumn('user_agent', Types::TEXT)->setNotnull(false);
        $sLogs->addColumn('device_hash', Types::STRING)->setLength(64)->setNotnull(false);
        $sLogs->addColumn('description', Types::TEXT)->setNotnull(false);
        $sLogs->addColumn('severity', Types::STRING)->setLength(20)->setDefault('info');
        $sLogs->addColumn('created_at', Types::DATETIME_MUTABLE);

        $sLogs->setPrimaryKey(['id']);
        $sLogs->addIndex(['user_id']);
        $sLogs->addIndex(['event_type']);
        $sLogs->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'SET NULL']);

        // 4. Update Exam Templates
        $exams = $schema->getTable('exam_templates');
        if (!$exams->hasColumn('proctoring_mode')) {
            $exams->addColumn('proctoring_mode', Types::STRING)->setDefault('DISABLED');
        }
        if (!$exams->hasColumn('proctoring_config')) {
            $exams->addColumn('proctoring_config', Types::JSON)->setNotnull(false);
        }

        // 5. Update Exam Sessions
        $examSessions = $schema->getTable('exam_sessions');
        if (!$examSessions->hasColumn('integrity_hash')) {
            $examSessions->addColumn('integrity_hash', Types::STRING)->setLength(64)->setNotnull(false);
        }
        if (!$examSessions->hasColumn('locked_at')) {
            $examSessions->addColumn('locked_at', Types::DATETIME_MUTABLE)->setNotnull(false);
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('security_logs');
        $schema->dropTable('proctoring_logs');
        $schema->dropTable('proctoring_sessions');

        $exams = $schema->getTable('exam_templates');
        $exams->dropColumn('proctoring_mode');
        $exams->dropColumn('proctoring_config');

        $examSessions = $schema->getTable('exam_sessions');
        $examSessions->dropColumn('integrity_hash');
        $examSessions->dropColumn('locked_at');
    }
}
