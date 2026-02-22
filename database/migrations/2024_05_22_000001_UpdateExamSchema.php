<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class UpdateExamSchema extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Modify exam_templates
        $exams = $schema->getTable('exam_templates');
        if (!$exams->hasColumn('class_id')) {
            $exams->addColumn('class_id', Types::STRING)->setNotnull(false);
        }
        if (!$exams->hasColumn('subject_id')) {
            $exams->addColumn('subject_id', Types::STRING)->setNotnull(false);
        }
        if (!$exams->hasColumn('term')) {
            $exams->addColumn('term', Types::STRING)->setNotnull(false);
        }
        if (!$exams->hasColumn('academic_session')) {
            $exams->addColumn('academic_session', Types::STRING)->setNotnull(false);
        }
        if (!$exams->hasColumn('publish_strategy')) {
            $exams->addColumn('publish_strategy', Types::STRING)->setDefault('manual');
        }
        if (!$exams->hasColumn('proctoring_enabled')) {
            $exams->addColumn('proctoring_enabled', Types::BOOLEAN)->setDefault(false);
        }

        // Modify exam_sections
        $sections = $schema->getTable('exam_sections');
        if (!$sections->hasColumn('duration_minutes')) {
            $sections->addColumn('duration_minutes', Types::INTEGER)->setNotnull(false);
        }
        if (!$sections->hasColumn('weight')) {
            $sections->addColumn('weight', Types::FLOAT)->setDefault(1.0);
        }
        if (!$sections->hasColumn('question_selection_rules')) {
            $sections->addColumn('question_selection_rules', Types::JSON)->setNotnull(false);
        }

        // Modify exam_sessions
        $sessions = $schema->getTable('exam_sessions');
        if (!$sessions->hasColumn('current_section_id')) {
            $sessions->addColumn('current_section_id', Types::GUID)->setNotnull(false);
        }
        if (!$sessions->hasColumn('section_started_at')) {
            $sessions->addColumn('section_started_at', Types::DATETIME_MUTABLE)->setNotnull(false);
        }
        if (!$sessions->hasColumn('seed')) {
            $sessions->addColumn('seed', Types::STRING)->setNotnull(false);
        }

        // Create exam_session_questions
        if (!$schema->hasTable('exam_session_questions')) {
            $sessionQuestions = $schema->createTable('exam_session_questions');
            $sessionQuestions->addColumn('id', Types::GUID);
            $sessionQuestions->addColumn('exam_session_id', Types::GUID);
            $sessionQuestions->addColumn('question_id', Types::GUID);
            $sessionQuestions->addColumn('exam_section_id', Types::GUID)->setNotnull(false);
            $sessionQuestions->addColumn('question_order', Types::INTEGER)->setDefault(0);
            $sessionQuestions->addColumn('options_order', Types::JSON)->setNotnull(false);
            $sessionQuestions->addColumn('status', Types::STRING)->setDefault('unseen'); // unseen, seen, answered, flagged
            $sessionQuestions->addColumn('is_flagged', Types::BOOLEAN)->setDefault(false);
            $sessionQuestions->addColumn('created_at', Types::DATETIME_MUTABLE);
            $sessionQuestions->addColumn('updated_at', Types::DATETIME_MUTABLE);

            $sessionQuestions->setPrimaryKey(['id']);
            $sessionQuestions->addIndex(['exam_session_id']);
            $sessionQuestions->addForeignKeyConstraint('exam_sessions', ['exam_session_id'], ['id'], ['onDelete' => 'CASCADE']);
            $sessionQuestions->addForeignKeyConstraint('questions', ['question_id'], ['id'], ['onDelete' => 'RESTRICT']);
            // Assuming exam_sections exists from previous migration
            $sessionQuestions->addForeignKeyConstraint('exam_sections', ['exam_section_id'], ['id'], ['onDelete' => 'SET NULL']);
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('exam_session_questions');

        $exams = $schema->getTable('exam_templates');
        $exams->dropColumn('class_id');
        $exams->dropColumn('subject_id');
        $exams->dropColumn('term');
        $exams->dropColumn('academic_session');
        $exams->dropColumn('publish_strategy');
        $exams->dropColumn('proctoring_enabled');

        $sections = $schema->getTable('exam_sections');
        $sections->dropColumn('duration_minutes');
        $sections->dropColumn('weight');
        $sections->dropColumn('question_selection_rules');

        $sessions = $schema->getTable('exam_sessions');
        $sessions->dropColumn('current_section_id');
        $sessions->dropColumn('section_started_at');
        $sessions->dropColumn('seed');
    }
}
