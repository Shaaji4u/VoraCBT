<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateExamSnapshotBackupsTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('exam_snapshot_backups')) {
            return;
        }

        $table = $schema->createTable('exam_snapshot_backups');
        $table->addColumn('id', Types::GUID);
        $table->addColumn('exam_template_id', Types::GUID);
        $table->addColumn('snapshot_path', Types::STRING)->setLength(255);
        $table->addColumn('snapshot_hash', Types::STRING)->setLength(64);
        $table->addColumn('created_by', Types::GUID)->setNotnull(false);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['exam_template_id', 'created_at']);
        $table->addForeignKeyConstraint('exam_templates', ['exam_template_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('users', ['created_by'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('exam_snapshot_backups')) {
            $schema->dropTable('exam_snapshot_backups');
        }
    }
}
