<?php

declare(strict_types=1);

namespace App\Core\Database\Seeder;

use Doctrine\DBAL\Connection;

interface SeederInterface
{
    public function run(Connection $connection): void;
}
