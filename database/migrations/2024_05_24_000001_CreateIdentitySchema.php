<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateIdentitySchema extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Classes Table
        $classes = $schema->createTable('classes');
        $classes->addColumn('id', Types::GUID);
        $classes->addColumn('name', Types::STRING)->setLength(255);
        $classes->addColumn('tenant_id', Types::INTEGER)->setDefault(1);
        $classes->addColumn('created_at', Types::DATETIME_MUTABLE);
        $classes->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $classes->setPrimaryKey(['id']);
        // Index on tenant_id for multi-tenancy support (future-proof)
        $classes->addIndex(['tenant_id']);

        // Alter Users Table
        $users = $schema->getTable('users');

        // Check if columns exist before adding (idempotency)
        if (!$users->hasColumn('admission_number')) {
            $users->addColumn('admission_number', Types::STRING)->setLength(50)->setNotnull(false);
            $users->addUniqueIndex(['admission_number']);
        }

        if (!$users->hasColumn('tenant_id')) {
            $users->addColumn('tenant_id', Types::INTEGER)->setDefault(1);
            $users->addIndex(['tenant_id']);
        }

        if (!$users->hasColumn('class_id')) {
            $users->addColumn('class_id', Types::GUID)->setNotnull(false);
            $users->addForeignKeyConstraint('classes', ['class_id'], ['id'], ['onDelete' => 'SET NULL']);
            $users->addIndex(['class_id']);
        }
    }

    public function down(Schema $schema): void
    {
        $users = $schema->getTable('users');

        if ($users->hasColumn('class_id')) {
            $users->dropColumn('class_id');
            // Constraints are usually dropped automatically or by name, but explicit drop is safer if we knew the name.
            // Doctrine usually handles this via schema diff, but manual down needs care.
        }

        if ($users->hasColumn('tenant_id')) {
            $users->dropColumn('tenant_id');
        }

        if ($users->hasColumn('admission_number')) {
            $users->dropColumn('admission_number');
        }

        $schema->dropTable('classes');
    }
}
