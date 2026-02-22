<?php

declare(strict_types=1);

use App\Core\Database\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class CreateUsersAndRolesTables extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Roles
        $roles = $schema->createTable('roles');
        $roles->addColumn('id', Types::GUID);
        $roles->addColumn('name', Types::STRING)->setLength(50);
        $roles->addColumn('slug', Types::STRING)->setLength(50);
        $roles->addColumn('description', Types::TEXT)->setNotnull(false);
        $roles->addColumn('created_at', Types::DATETIME_MUTABLE);
        $roles->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $roles->setPrimaryKey(['id']);
        $roles->addUniqueIndex(['slug']);

        // Permissions
        $permissions = $schema->createTable('permissions');
        $permissions->addColumn('id', Types::GUID);
        $permissions->addColumn('name', Types::STRING)->setLength(100);
        $permissions->addColumn('slug', Types::STRING)->setLength(100);
        $permissions->addColumn('created_at', Types::DATETIME_MUTABLE);
        $permissions->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $permissions->setPrimaryKey(['id']);
        $permissions->addUniqueIndex(['slug']);

        // Role Permissions
        $rolePermissions = $schema->createTable('role_permissions');
        $rolePermissions->addColumn('role_id', Types::GUID);
        $rolePermissions->addColumn('permission_id', Types::GUID);
        $rolePermissions->setPrimaryKey(['role_id', 'permission_id']);
        $rolePermissions->addForeignKeyConstraint('roles', ['role_id'], ['id'], ['onDelete' => 'CASCADE']);
        $rolePermissions->addForeignKeyConstraint('permissions', ['permission_id'], ['id'], ['onDelete' => 'CASCADE']);

        // Users
        $users = $schema->createTable('users');
        $users->addColumn('id', Types::GUID);
        $users->addColumn('email', Types::STRING)->setLength(255);
        $users->addColumn('password', Types::STRING)->setLength(255);
        $users->addColumn('first_name', Types::STRING)->setLength(100);
        $users->addColumn('last_name', Types::STRING)->setLength(100);
        $users->addColumn('role_id', Types::GUID)->setNotnull(false); // Nullable for now or default?
        $users->addColumn('created_at', Types::DATETIME_MUTABLE);
        $users->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $users->addColumn('created_by', Types::GUID)->setNotnull(false);
        $users->addColumn('updated_by', Types::GUID)->setNotnull(false);
        $users->addColumn('archived_at', Types::DATETIME_MUTABLE)->setNotnull(false);
        $users->setPrimaryKey(['id']);
        $users->addUniqueIndex(['email']);
        $users->addForeignKeyConstraint('roles', ['role_id'], ['id'], ['onDelete' => 'SET NULL']);

        // Audit logs for users creation?
        // created_by and updated_by are self-referencing FKs potentially?
        // For now just columns.
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('users');
        $schema->dropTable('role_permissions');
        $schema->dropTable('permissions');
        $schema->dropTable('roles');
    }
}
