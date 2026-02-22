<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateExamSessionCheckpointsTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('exam_session_checkpoints')) {
            return;
        }

        $table = $schema->createTable('exam_session_checkpoints');
        $table->addColumn('id', Types::GUID);
        $table->addColumn('exam_session_id', Types::GUID);
        $table->addColumn('last_answered_question_id', Types::GUID)->setNotnull(false);
        $table->addColumn('draft_payload', Types::JSON)->setNotnull(false);
        $table->addColumn('client_timestamp', Types::DATETIME_MUTABLE)->setNotnull(false);
        $table->addColumn('server_timestamp', Types::DATETIME_MUTABLE);
        $table->addColumn('checksum', Types::STRING)->setLength(64);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['exam_session_id']);
        $table->addIndex(['server_timestamp']);
        $table->addForeignKeyConstraint('exam_sessions', ['exam_session_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('questions', ['last_answered_question_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('exam_session_checkpoints')) {
            $schema->dropTable('exam_session_checkpoints');
        }
    }
}
