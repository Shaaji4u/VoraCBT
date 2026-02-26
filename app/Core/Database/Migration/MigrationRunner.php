<?php

declare(strict_types=1);

namespace App\Core\Database\Migration;

use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Exception;
use RuntimeException;

class MigrationRunner
{
    private $connection;
    private $migrationsPath;

    public function __construct()
    {
        $this->connection = DatabaseManager::getConnection();
        // Assuming database/migrations is at root
        $this->migrationsPath = __DIR__ . '/../../../../database/migrations';
    }

    public function migrate(): void
    {
        $this->ensureMigrationsTable();

        $executedMigrations = $this->getExecutedMigrations();
        $migrationFiles = $this->getMigrationFiles();

        $batch = $this->getNextBatchNumber();

        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.php');

            if (in_array($migrationName, $executedMigrations)) {
                continue;
            }

            echo "Migrating: $migrationName\n";

            require_once $file;

            // Extract class name from file name (assuming convention: YYYY_MM_DD_HHMMSS_ClassName.php)
            // Or just parsing the file content to find the class?
            // Simpler: require the file, and assume the class name matches the suffix or something.
            // Let's assume the file returns an anonymous class or the class name is predictable.
            // Convention: 2023_01_01_000000_CreateUsersTable.php -> class CreateUsersTable

            $className = $this->getClassNameFromFile($file);

            if (!class_exists($className)) {
                throw new RuntimeException("Migration class $className not found in $file");
            }

            $migration = new $className();
            if (!$migration instanceof MigrationInterface) {
                throw new RuntimeException("Migration $className must implement MigrationInterface");
            }

            $this->runMigration($migration, $migrationName, $batch);
        }
    }

    private function ensureMigrationsTable(): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['migrations'])) {
            $schema = new Schema();
            $table = $schema->createTable('migrations');
            $table->addColumn('id', Types::INTEGER)->setAutoincrement(true);
            $table->addColumn('migration', Types::STRING);
            $table->addColumn('batch', Types::INTEGER);
            $table->addColumn('executed_at', Types::DATETIME_MUTABLE);
            $table->setPrimaryKey(['id']);

            $platform = $this->connection->getDatabasePlatform();
            $queries = $schema->toSql($platform);

            foreach ($queries as $query) {
                $this->connection->executeStatement($query);
            }
        }
    }

    private function getExecutedMigrations(): array
    {
        return $this->connection->fetchFirstColumn("SELECT migration FROM migrations");
    }

    private function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        sort($files); // Sort by name (timestamp)
        return $files;
    }

    private function getNextBatchNumber(): int
    {
        $batch = $this->connection->fetchOne("SELECT MAX(batch) FROM migrations");
        return ((int) $batch) + 1;
    }

    private function getClassNameFromFile(string $filepath): string
    {
        // Primitive parsing:
        // 2023_..._CreateUsersTable.php -> CreateUsersTable
        $filename = basename($filepath, '.php');
        // Remove timestamp (first 17 chars usually: YYYY_MM_DD_HHMMSS_)
        // But let's trigger a regex to be safe.
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $filename, $matches)) {
            return $matches[1];
        }
        // Fallback or error?
        return $filename;
    }

    private function runMigration(MigrationInterface $migration, string $migrationName, int $batch): void
    {
        $this->connection->beginTransaction();

        try {
            $currentSchema = $this->getCurrentSchema();
            $toSchema = clone $currentSchema;

            $migration->up($toSchema);

            // Calculate diff
            $platform = $this->connection->getDatabasePlatform();
            $sqls = $currentSchema->getMigrateToSql($toSchema, $platform);

            foreach ($sqls as $sql) {
                $this->connection->executeStatement($sql);
            }

            $this->connection->insert('migrations', [
                'migration' => $migrationName,
                'batch' => $batch,
                'executed_at' => date('Y-m-d H:i:s')
            ]);

            if ($this->connection->isTransactionActive()) {
                $this->connection->commit();
            }
            echo "Migrated:  $migrationName\n";
        } catch (Exception $e) {
            try {
                $this->connection->rollBack();
            } catch (Exception $rollbackEx) {
                // Ignore rollback failures (e.g., "no active transaction" from implicit DDL commits)
                // so the original schema error can bubble up
            }
            throw new RuntimeException("Migration failed: " . $e->getMessage(), 0, $e);
        }
    }

    private function getCurrentSchema(): Schema
    {
        $sm = $this->connection->createSchemaManager();
        return $sm->introspectSchema();
    }

    public function rollback(): void
    {
        $this->ensureMigrationsTable();

        // get last batch
        $lastBatch = $this->connection->fetchOne("SELECT MAX(batch) FROM migrations");
        if (!$lastBatch) {
            echo "Nothing to rollback.\n";
            return;
        }

        $migrations = $this->connection->fetchAllAssociative("SELECT * FROM migrations WHERE batch = ? ORDER BY id DESC", [$lastBatch]);

        foreach ($migrations as $record) {
            $migrationName = $record['migration'];
            echo "Rolling back: $migrationName\n";

            // Find file
            // We assume file exists. If not, we can't rollback code logic, but maybe we can just delete record?
            // Usually we need the down() method.

            $file = $this->findMigrationFile($migrationName);
            if (!$file) {
                throw new RuntimeException("Migration file for $migrationName not found");
            }

            require_once $file;
            $className = $this->getClassNameFromFile($file);

            if (!class_exists($className)) {
                throw new RuntimeException("Migration class $className not found");
            }

            $migration = new $className();

            $this->runRollback($migration, $migrationName);
        }
    }

    private function findMigrationFile(string $migrationName): ?string
    {
        $files = glob($this->migrationsPath . '/' . $migrationName . '.php');
        return $files[0] ?? null;
    }

    private function runRollback(MigrationInterface $migration, string $migrationName): void
    {
        $this->connection->beginTransaction();

        try {
            $currentSchema = $this->getCurrentSchema();
            $toSchema = clone $currentSchema;

            $migration->down($toSchema);

            $platform = $this->connection->getDatabasePlatform();
            $sqls = $currentSchema->getMigrateToSql($toSchema, $platform);

            foreach ($sqls as $sql) {
                $this->connection->executeStatement($sql);
            }

            $this->connection->delete('migrations', ['migration' => $migrationName]);

            $this->connection->commit();
            echo "Rolled back:  $migrationName\n";
        } catch (Exception $e) {
            try {
                $this->connection->rollBack();
            } catch (Exception $rollbackEx) {
                // Ignore rollback failures
            }
            throw new RuntimeException("Rollback failed: " . $e->getMessage(), 0, $e);
        }
    }
}
