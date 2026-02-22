<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class AddMonitoringIndexes extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $security = $schema->getTable('security_logs');
        if (!$security->hasIndex('idx_security_logs_created_at')) {
            $security->addIndex(['created_at'], 'idx_security_logs_created_at');
        }

        $identity = $schema->getTable('identity_logs');
        if (!$identity->hasIndex('idx_identity_logs_performed_by')) {
            $identity->addIndex(['performed_by'], 'idx_identity_logs_performed_by');
        }

        $proctor = $schema->getTable('proctoring_logs');
        if (!$proctor->hasIndex('idx_proctoring_logs_created_at')) {
            $proctor->addIndex(['created_at'], 'idx_proctoring_logs_created_at');
        }

        $integration = $schema->getTable('integration_logs');
        if (!$integration->hasIndex('idx_integration_logs_created_at')) {
            $integration->addIndex(['created_at'], 'idx_integration_logs_created_at');
        }

        $sessions = $schema->getTable('exam_sessions');
        if (!$sessions->hasIndex('idx_exam_sessions_exam_template_id')) {
            $sessions->addIndex(['exam_template_id'], 'idx_exam_sessions_exam_template_id');
        }
    }

    public function down(Schema $schema): void
    {
        $security = $schema->getTable('security_logs');
        if ($security->hasIndex('idx_security_logs_created_at')) {
            $security->dropIndex('idx_security_logs_created_at');
        }

        $identity = $schema->getTable('identity_logs');
        if ($identity->hasIndex('idx_identity_logs_performed_by')) {
            $identity->dropIndex('idx_identity_logs_performed_by');
        }

        $proctor = $schema->getTable('proctoring_logs');
        if ($proctor->hasIndex('idx_proctoring_logs_created_at')) {
            $proctor->dropIndex('idx_proctoring_logs_created_at');
        }

        $integration = $schema->getTable('integration_logs');
        if ($integration->hasIndex('idx_integration_logs_created_at')) {
            $integration->dropIndex('idx_integration_logs_created_at');
        }

        $sessions = $schema->getTable('exam_sessions');
        if ($sessions->hasIndex('idx_exam_sessions_exam_template_id')) {
            $sessions->dropIndex('idx_exam_sessions_exam_template_id');
        }
    }
}
