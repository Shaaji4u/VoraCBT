<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class AddExamLifecycleAndResultLocking extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $templates = $schema->getTable('exam_templates');
        if (!$templates->hasColumn('lifecycle_state')) {
            $templates->addColumn('lifecycle_state', Types::STRING)->setLength(30)->setDefault('draft');
        }

        if (!$templates->hasColumn('state_changed_at')) {
            $templates->addColumn('state_changed_at', Types::DATETIME_MUTABLE)->setNotnull(false);
        }

        if (!$templates->hasColumn('state_changed_by')) {
            $templates->addColumn('state_changed_by', Types::GUID)->setNotnull(false);
            $templates->addForeignKeyConstraint('users', ['state_changed_by'], ['id'], ['onDelete' => 'SET NULL']);
        }

        $results = $schema->getTable('exam_results');
        if (!$results->hasColumn('is_locked')) {
            $results->addColumn('is_locked', Types::BOOLEAN)->setDefault(false);
        }

        if (!$results->hasColumn('locked_at')) {
            $results->addColumn('locked_at', Types::DATETIME_MUTABLE)->setNotnull(false);
        }

        if (!$results->hasColumn('locked_by')) {
            $results->addColumn('locked_by', Types::GUID)->setNotnull(false);
            $results->addForeignKeyConstraint('users', ['locked_by'], ['id'], ['onDelete' => 'SET NULL']);
        }

        if (!$results->hasColumn('lock_reason')) {
            $results->addColumn('lock_reason', Types::TEXT)->setNotnull(false);
        }

        if (!$schema->hasTable('exam_lifecycle_logs')) {
            $logs = $schema->createTable('exam_lifecycle_logs');
            $logs->addColumn('id', Types::GUID);
            $logs->addColumn('exam_template_id', Types::GUID);
            $logs->addColumn('old_state', Types::STRING)->setLength(30);
            $logs->addColumn('new_state', Types::STRING)->setLength(30);
            $logs->addColumn('changed_by', Types::GUID)->setNotnull(false);
            $logs->addColumn('reason', Types::TEXT)->setNotnull(false);
            $logs->addColumn('created_at', Types::DATETIME_MUTABLE);
            $logs->setPrimaryKey(['id']);
            $logs->addIndex(['exam_template_id', 'created_at']);
            $logs->addForeignKeyConstraint('exam_templates', ['exam_template_id'], ['id'], ['onDelete' => 'CASCADE']);
            $logs->addForeignKeyConstraint('users', ['changed_by'], ['id'], ['onDelete' => 'SET NULL']);
        }

        if (!$schema->hasTable('result_lock_audit_logs')) {
            $logs = $schema->createTable('result_lock_audit_logs');
            $logs->addColumn('id', Types::GUID);
            $logs->addColumn('exam_result_id', Types::GUID);
            $logs->addColumn('actor_user_id', Types::GUID)->setNotnull(false);
            $logs->addColumn('action', Types::STRING)->setLength(50);
            $logs->addColumn('notes', Types::TEXT)->setNotnull(false);
            $logs->addColumn('created_at', Types::DATETIME_MUTABLE);
            $logs->setPrimaryKey(['id']);
            $logs->addIndex(['exam_result_id', 'created_at']);
            $logs->addForeignKeyConstraint('exam_results', ['exam_result_id'], ['id'], ['onDelete' => 'CASCADE']);
            $logs->addForeignKeyConstraint('users', ['actor_user_id'], ['id'], ['onDelete' => 'SET NULL']);
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('result_lock_audit_logs')) {
            $schema->dropTable('result_lock_audit_logs');
        }

        if ($schema->hasTable('exam_lifecycle_logs')) {
            $schema->dropTable('exam_lifecycle_logs');
        }

        $results = $schema->getTable('exam_results');
        foreach (['lock_reason', 'locked_by', 'locked_at', 'is_locked'] as $column) {
            if ($results->hasColumn($column)) {
                $results->dropColumn($column);
            }
        }

        $templates = $schema->getTable('exam_templates');
        foreach (['state_changed_by', 'state_changed_at', 'lifecycle_state'] as $column) {
            if ($templates->hasColumn($column)) {
                $templates->dropColumn($column);
            }
        }
    }
}
