<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class UpdateUsersForIdentity extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $users = $schema->getTable('users');

        if (!$users->hasColumn('staff_id')) {
            $users->addColumn('staff_id', Types::STRING)->setLength(50)->setNotnull(false);
            $users->addUniqueIndex(['staff_id']);
        }

        if (!$users->hasColumn('sms_oauth_id')) {
            $users->addColumn('sms_oauth_id', Types::STRING)->setLength(100)->setNotnull(false);
            $users->addUniqueIndex(['sms_oauth_id']);
        }

        if (!$users->hasColumn('academic_session')) {
            $users->addColumn('academic_session', Types::STRING)->setLength(20)->setNotnull(false);
        }

        if (!$users->hasColumn('term')) {
            $users->addColumn('term', Types::STRING)->setLength(20)->setNotnull(false);
        }

        if (!$users->hasColumn('metadata')) {
            $users->addColumn('metadata', Types::JSON)->setNotnull(false);
        }
    }

    public function down(Schema $schema): void
    {
        $users = $schema->getTable('users');

        if ($users->hasColumn('metadata')) {
            $users->dropColumn('metadata');
        }

        if ($users->hasColumn('term')) {
            $users->dropColumn('term');
        }

        if ($users->hasColumn('academic_session')) {
            $users->dropColumn('academic_session');
        }

        if ($users->hasColumn('sms_oauth_id')) {
            $users->dropColumn('sms_oauth_id');
        }

        if ($users->hasColumn('staff_id')) {
            $users->dropColumn('staff_id');
        }
    }
}
