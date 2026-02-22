<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateExamSessionSectionsTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Add lock_sections to exam_templates
        if ($schema->hasTable('exam_templates')) {
            $table = $schema->getTable('exam_templates');
            if (!$table->hasColumn('lock_sections')) {
                // SQLite ALTER TABLE restrictions might apply via DBAL, but adding column usually works.
                $schema->getTable('exam_templates')->addColumn('lock_sections', Types::BOOLEAN)->setDefault(false);
            }
        }

        // Create exam_session_sections
        if (!$schema->hasTable('exam_session_sections')) {
            $table = $schema->createTable('exam_session_sections');
            $table->addColumn('id', Types::GUID);
            $table->addColumn('exam_session_id', Types::GUID);
            $table->addColumn('exam_section_id', Types::GUID);
            $table->addColumn('status', Types::STRING)->setDefault('pending'); // pending, in_progress, completed
            $table->addColumn('started_at', Types::DATETIME_MUTABLE)->setNotnull(false);
            $table->addColumn('completed_at', Types::DATETIME_MUTABLE)->setNotnull(false);
            $table->addColumn('created_at', Types::DATETIME_MUTABLE);
            $table->addColumn('updated_at', Types::DATETIME_MUTABLE);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['exam_session_id', 'exam_section_id']); // Unique composite? A session can attempt a section only once? Assuming yes for now.
            // Unique constraint to enforce one record per section per session
            $table->addUniqueIndex(['exam_session_id', 'exam_section_id']);

            $table->addForeignKeyConstraint('exam_sessions', ['exam_session_id'], ['id'], ['onDelete' => 'CASCADE']);
            $table->addForeignKeyConstraint('exam_sections', ['exam_section_id'], ['id'], ['onDelete' => 'CASCADE']);
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('exam_session_sections');

        if ($schema->hasTable('exam_templates')) {
            $schema->getTable('exam_templates')->dropColumn('lock_sections');
        }
    }
}
