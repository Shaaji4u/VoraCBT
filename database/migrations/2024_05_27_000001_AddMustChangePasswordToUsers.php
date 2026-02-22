<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class AddMustChangePasswordToUsers extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $users = $schema->getTable('users');

        if (!$users->hasColumn('must_change_password')) {
            $users->addColumn('must_change_password', Types::BOOLEAN)->setDefault(true);
            $users->addIndex(['must_change_password']);
        }
    }

    public function down(Schema $schema): void
    {
        $users = $schema->getTable('users');

        if ($users->hasColumn('must_change_password')) {
            $users->dropColumn('must_change_password');
        }
    }
}
