<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateOAuthTokensTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('oauth_tokens');
        $table->addColumn('id', Types::GUID);
        $table->addColumn('provider', Types::STRING)->setLength(50);
        $table->addColumn('access_token', Types::TEXT);
        $table->addColumn('refresh_token', Types::TEXT)->setNotnull(false);
        $table->addColumn('expires_at', Types::DATETIME_MUTABLE);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['provider']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('oauth_tokens');
    }
}
