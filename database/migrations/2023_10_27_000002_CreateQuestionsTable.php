<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateQuestionsTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Questions Table
        $questions = $schema->createTable('questions');
        $questions->addColumn('id', Types::GUID);
        $questions->addColumn('type', Types::STRING)->setLength(50); // mcq, essay, etc.
        $questions->addColumn('content', Types::JSON); // The payload (prompt, options, etc.)
        $questions->addColumn('metadata', Types::JSON)->setNotnull(false); // Tags, difficulty, etc.
        $questions->addColumn('version', Types::INTEGER)->setDefault(1);

        $questions->addColumn('created_at', Types::DATETIME_MUTABLE);
        $questions->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $questions->addColumn('archived_at', Types::DATETIME_MUTABLE)->setNotnull(false);

        $questions->addColumn('created_by', Types::GUID)->setNotnull(false);
        $questions->addColumn('updated_by', Types::GUID)->setNotnull(false);

        $questions->setPrimaryKey(['id']);
        $questions->addIndex(['type']);
        $questions->addForeignKeyConstraint('users', ['created_by'], ['id'], ['onDelete' => 'SET NULL']);
        $questions->addForeignKeyConstraint('users', ['updated_by'], ['id'], ['onDelete' => 'SET NULL']);

        // Question Versions Table
        $versions = $schema->createTable('question_versions');
        $versions->addColumn('id', Types::GUID);
        $versions->addColumn('question_id', Types::GUID);
        $versions->addColumn('version', Types::INTEGER);
        $versions->addColumn('type', Types::STRING)->setLength(50);
        $versions->addColumn('content', Types::JSON);
        $versions->addColumn('metadata', Types::JSON)->setNotnull(false);
        $versions->addColumn('created_at', Types::DATETIME_MUTABLE);
        $versions->addColumn('created_by', Types::GUID)->setNotnull(false);

        $versions->setPrimaryKey(['id']);
        $versions->addIndex(['question_id']);
        $versions->addUniqueIndex(['question_id', 'version']); // Composite unique constraint

        $versions->addForeignKeyConstraint('questions', ['question_id'], ['id'], ['onDelete' => 'CASCADE']);
        $versions->addForeignKeyConstraint('users', ['created_by'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('question_versions');
        $schema->dropTable('questions');
    }
}
