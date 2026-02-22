<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateImportAuditSchema extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Student Import Logs
        $logs = $schema->createTable('student_import_logs');
        $logs->addColumn('id', Types::GUID);
        $logs->addColumn('admin_id', Types::GUID)->setNotnull(false);
        $logs->addColumn('file_name', Types::STRING)->setLength(255);
        $logs->addColumn('total_rows', Types::INTEGER)->setDefault(0);
        $logs->addColumn('successful_rows', Types::INTEGER)->setDefault(0);
        $logs->addColumn('failed_rows', Types::INTEGER)->setDefault(0);
        $logs->addColumn('metadata', Types::JSON)->setNotnull(false);
        $logs->addColumn('created_at', Types::DATETIME_MUTABLE);
        $logs->setPrimaryKey(['id']);
        $logs->addForeignKeyConstraint('users', ['admin_id'], ['id'], ['onDelete' => 'SET NULL']);
        $logs->addIndex(['created_at']);

        // User Credentials Buffer (Temporary storage for printing)
        $buffer = $schema->createTable('user_credentials_buffer');
        $buffer->addColumn('user_id', Types::GUID);
        $buffer->addColumn('password_plaintext', Types::STRING)->setLength(255);
        $buffer->addColumn('created_at', Types::DATETIME_MUTABLE);
        $buffer->addColumn('expires_at', Types::DATETIME_MUTABLE);
        $buffer->addColumn('is_exported', Types::BOOLEAN)->setDefault(false);
        $buffer->setPrimaryKey(['user_id']); // One buffer entry per user at a time
        $buffer->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $buffer->addIndex(['expires_at']);
        $buffer->addIndex(['is_exported']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('user_credentials_buffer');
        $schema->dropTable('student_import_logs');
    }
}
