<?php

declare(strict_types=1);

namespace App\Core\Database\Migration;

use Doctrine\DBAL\Schema\Schema;

abstract class AbstractMigration implements MigrationInterface
{
    // Common functionality can be added here if needed.
    // For now, it just enforces the interface.
}
