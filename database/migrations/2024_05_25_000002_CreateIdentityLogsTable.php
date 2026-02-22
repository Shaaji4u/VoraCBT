<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateIdentityLogsTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $logs = $schema->createTable('identity_logs');
        $logs->addColumn('id', Types::GUID);
        $logs->addColumn('user_id', Types::GUID)->setNotnull(false);
        $logs->addColumn('action', Types::STRING)->setLength(100);
        $logs->addColumn('performed_by', Types::GUID)->setNotnull(false);
        $logs->addColumn('timestamp', Types::DATETIME_MUTABLE);
        $logs->addColumn('tenant_id', Types::INTEGER)->setDefault(1);
        $logs->addColumn('details', Types::JSON)->setNotnull(false);

        $logs->setPrimaryKey(['id']);
        $logs->addIndex(['user_id']);
        $logs->addIndex(['action']);
        $logs->addIndex(['tenant_id']);
        $logs->addIndex(['timestamp']);

        // Optional: FK to users if user_id is always a valid user, but sometimes it might be deleted user or external.
        // Assuming user_id refers to the affected user.
        $logs->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'SET NULL']);
        $logs->addForeignKeyConstraint('users', ['performed_by'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('identity_logs');
    }
}
