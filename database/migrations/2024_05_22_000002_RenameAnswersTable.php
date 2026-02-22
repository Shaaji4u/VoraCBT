<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

class RenameAnswersTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('answers')) {
            $table = $schema->getTable('answers');

            // Create new table with exact columns
            $newTable = $schema->createTable('exam_session_answers');
            foreach ($table->getColumns() as $column) {
                // Manually replicate column options, excluding unsupported ones for creation
                $options = [
                    'length' => $column->getLength(),
                    'precision' => $column->getPrecision(),
                    'scale' => $column->getScale(),
                    'unsigned' => $column->getUnsigned(),
                    'fixed' => $column->getFixed(),
                    'default' => $column->getDefault(),
                    'notnull' => $column->getNotnull(),
                    'autoincrement' => $column->getAutoincrement(),
                    'comment' => $column->getComment(),
                ];

                // Filter nulls or defaults
                $options = array_filter($options, function($v) { return !is_null($v); });

                $newTable->addColumn($column->getName(), $column->getType()->getName(), $options);
            }

            // Primary key
            if ($table->hasPrimaryKey()) {
                $newTable->setPrimaryKey($table->getPrimaryKey()->getColumns());
            }

            // Drop old table (data loss accepted for dev/test environment here, or would use INSERT INTO ... SELECT)
            $schema->dropTable('answers');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('exam_session_answers')) {
             $table = $schema->getTable('exam_session_answers');
             $schema->renameTable('exam_session_answers', 'answers');
        }
    }
}
