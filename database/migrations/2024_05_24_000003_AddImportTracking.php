<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class AddImportTracking extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // 1. Add import_log_id to users
        $users = $schema->getTable('users');
        if (!$users->hasColumn('import_log_id')) {
            $users->addColumn('import_log_id', Types::GUID)->setNotnull(false);
            $users->addForeignKeyConstraint('student_import_logs', ['import_log_id'], ['id'], ['onDelete' => 'SET NULL']);
            $users->addIndex(['import_log_id']);
        }

        // 2. Create credential_export_logs
        $exportLogs = $schema->createTable('credential_export_logs');
        $exportLogs->addColumn('id', Types::GUID);
        $exportLogs->addColumn('admin_id', Types::GUID)->setNotnull(false);
        $exportLogs->addColumn('ip_address', Types::STRING)->setLength(45)->setNotnull(false);
        $exportLogs->addColumn('user_agent', Types::STRING)->setLength(255)->setNotnull(false);
        $exportLogs->addColumn('filters', Types::JSON)->setNotnull(false);
        $exportLogs->addColumn('created_at', Types::DATETIME_MUTABLE);
        $exportLogs->setPrimaryKey(['id']);
        $exportLogs->addIndex(['admin_id']);
        $exportLogs->addIndex(['created_at']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('credential_export_logs');

        $users = $schema->getTable('users');
        if ($users->hasColumn('import_log_id')) {
            $users->dropColumn('import_log_id');
        }
    }
}
