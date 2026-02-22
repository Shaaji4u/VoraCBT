<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateIntegrationLogsTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Drop existing table if it exists (from previous migration) to ensure correct schema
        if ($schema->hasTable('integration_logs')) {
            $schema->dropTable('integration_logs');
        }

        $table = $schema->createTable('integration_logs');
        $table->addColumn('id', Types::GUID);
        $table->addColumn('request_payload', Types::JSON)->setNotnull(false);
        $table->addColumn('response_payload', Types::JSON)->setNotnull(false);
        $table->addColumn('status', Types::STRING)->setLength(50);
        $table->addColumn('retry_count', Types::INTEGER)->setDefault(0);
        $table->addColumn('last_attempt_at', Types::DATETIME_MUTABLE)->setNotnull(false);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $table->addColumn('idempotency_key', Types::GUID);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['idempotency_key']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('integration_logs');
    }
}
