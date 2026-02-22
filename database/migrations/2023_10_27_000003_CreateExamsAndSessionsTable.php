<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateExamsAndSessionsTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Exam Templates
        $exams = $schema->createTable('exam_templates');
        $exams->addColumn('id', Types::GUID);
        $exams->addColumn('title', Types::STRING)->setLength(255);
        $exams->addColumn('description', Types::TEXT)->setNotnull(false);
        $exams->addColumn('duration_minutes', Types::INTEGER)->setNotnull(false); // Null means unlimited?
        $exams->addColumn('start_window', Types::DATETIME_MUTABLE)->setNotnull(false);
        $exams->addColumn('end_window', Types::DATETIME_MUTABLE)->setNotnull(false);
        $exams->addColumn('passing_score', Types::FLOAT)->setNotnull(false);
        $exams->addColumn('total_score', Types::FLOAT)->setNotnull(false);
        $exams->addColumn('instructions', Types::TEXT)->setNotnull(false);
        $exams->addColumn('created_at', Types::DATETIME_MUTABLE);
        $exams->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $exams->addColumn('archived_at', Types::DATETIME_MUTABLE)->setNotnull(false);
        $exams->addColumn('created_by', Types::GUID)->setNotnull(false);
        $exams->addColumn('updated_by', Types::GUID)->setNotnull(false);
        $exams->setPrimaryKey(['id']);
        $exams->addForeignKeyConstraint('users', ['created_by'], ['id'], ['onDelete' => 'SET NULL']);
        $exams->addForeignKeyConstraint('users', ['updated_by'], ['id'], ['onDelete' => 'SET NULL']);

        // Exam Sections
        $sections = $schema->createTable('exam_sections');
        $sections->addColumn('id', Types::GUID);
        $sections->addColumn('exam_template_id', Types::GUID);
        $sections->addColumn('title', Types::STRING)->setLength(255);
        $sections->addColumn('section_order', Types::INTEGER)->setDefault(0); // 'order' is reserved keyword in some SQL
        $sections->addColumn('description', Types::TEXT)->setNotnull(false);
        $sections->setPrimaryKey(['id']);
        $sections->addForeignKeyConstraint('exam_templates', ['exam_template_id'], ['id'], ['onDelete' => 'CASCADE']);

        // Exam Questions (Linking table)
        $examQuestions = $schema->createTable('exam_questions');
        $examQuestions->addColumn('id', Types::GUID);
        $examQuestions->addColumn('exam_section_id', Types::GUID);
        $examQuestions->addColumn('question_id', Types::GUID);
        $examQuestions->addColumn('question_order', Types::INTEGER)->setDefault(0);
        $examQuestions->addColumn('marks', Types::FLOAT)->setDefault(1.0);
        $examQuestions->setPrimaryKey(['id']);
        $examQuestions->addForeignKeyConstraint('exam_sections', ['exam_section_id'], ['id'], ['onDelete' => 'CASCADE']);
        $examQuestions->addForeignKeyConstraint('questions', ['question_id'], ['id'], ['onDelete' => 'RESTRICT']); // Don't delete question if used in exam? Or CASCADE? Usually restrict to preserve history.

        // Exam Sessions
        $sessions = $schema->createTable('exam_sessions');
        $sessions->addColumn('id', Types::GUID);
        $sessions->addColumn('exam_template_id', Types::GUID);
        $sessions->addColumn('user_id', Types::GUID);
        $sessions->addColumn('status', Types::STRING)->setLength(50); // started, submitted, completed, graded
        $sessions->addColumn('start_time', Types::DATETIME_MUTABLE)->setNotnull(false);
        $sessions->addColumn('end_time', Types::DATETIME_MUTABLE)->setNotnull(false);
        $sessions->addColumn('score', Types::FLOAT)->setNotnull(false);
        $sessions->addColumn('created_at', Types::DATETIME_MUTABLE);
        $sessions->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $sessions->setPrimaryKey(['id']);
        // Indexes for performance
        $sessions->addIndex(['user_id', 'exam_template_id']); // Compound index for finding student's attempt
        $sessions->addForeignKeyConstraint('exam_templates', ['exam_template_id'], ['id'], ['onDelete' => 'CASCADE']);
        $sessions->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);

        // Answers
        $answers = $schema->createTable('answers');
        $answers->addColumn('id', Types::GUID);
        $answers->addColumn('exam_session_id', Types::GUID);
        $answers->addColumn('question_id', Types::GUID);
        $answers->addColumn('answer_payload', Types::JSON)->setNotnull(false); // The student's answer
        $answers->addColumn('is_correct', Types::BOOLEAN)->setNotnull(false);
        $answers->addColumn('marks_obtained', Types::FLOAT)->setNotnull(false);
        $answers->addColumn('comments', Types::TEXT)->setNotnull(false);
        $answers->addColumn('created_at', Types::DATETIME_MUTABLE);
        $answers->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $answers->setPrimaryKey(['id']);
        $answers->addIndex(['exam_session_id']); // Index for retrieving all answers for a session
        $answers->addForeignKeyConstraint('exam_sessions', ['exam_session_id'], ['id'], ['onDelete' => 'CASCADE']);
        $answers->addForeignKeyConstraint('questions', ['question_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('answers');
        $schema->dropTable('exam_sessions');
        $schema->dropTable('exam_questions');
        $schema->dropTable('exam_sections');
        $schema->dropTable('exam_templates');
    }
}
