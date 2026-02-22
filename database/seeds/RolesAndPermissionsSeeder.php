<?php

declare(strict_types=1);

use App\Core\Database\Seeder\SeederInterface;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class RolesAndPermissionsSeeder implements SeederInterface
{
    public function run(Connection $connection): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'System administrator'],
            ['name' => 'Teacher', 'slug' => 'teacher', 'description' => 'Course instructor'],
            ['name' => 'Student', 'slug' => 'student', 'description' => 'Enrolled student'],
        ];

        foreach ($roles as $role) {
            // Check if exists
            $exists = $connection->fetchOne('SELECT id FROM roles WHERE slug = ?', [$role['slug']]);
            if (!$exists) {
                $connection->insert('roles', [
                    'id' => Uuid::uuid4()->toString(),
                    'name' => $role['name'],
                    'slug' => $role['slug'],
                    'description' => $role['description'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $permissions = [
            ['name' => 'Manage Users', 'slug' => 'users.manage'],
            ['name' => 'View Users', 'slug' => 'users.view'],
            ['name' => 'Create Exam', 'slug' => 'exams.create'],
            ['name' => 'Take Exam', 'slug' => 'exams.take'],
            ['name' => 'Grade Exam', 'slug' => 'exams.grade'],
        ];

        foreach ($permissions as $permission) {
             $exists = $connection->fetchOne('SELECT id FROM permissions WHERE slug = ?', [$permission['slug']]);
             if (!$exists) {
                 $connection->insert('permissions', [
                     'id' => Uuid::uuid4()->toString(),
                     'name' => $permission['name'],
                     'slug' => $permission['slug'],
                     'created_at' => date('Y-m-d H:i:s'),
                     'updated_at' => date('Y-m-d H:i:s'),
                 ]);
             }
        }

        // Assign permissions to roles
        $adminRole = $connection->fetchAssociative('SELECT * FROM roles WHERE slug = ?', ['admin']);
        $teacherRole = $connection->fetchAssociative('SELECT * FROM roles WHERE slug = ?', ['teacher']);
        $studentRole = $connection->fetchAssociative('SELECT * FROM roles WHERE slug = ?', ['student']);

        $allPermissions = $connection->fetchAllAssociative('SELECT * FROM permissions');

        foreach ($allPermissions as $perm) {
            if ($adminRole) {
                $this->assignPermission($connection, $adminRole['id'], $perm['id']);
            }
            if ($teacherRole && in_array($perm['slug'], ['exams.create', 'exams.grade', 'users.view'])) {
                $this->assignPermission($connection, $teacherRole['id'], $perm['id']);
            }
            if ($studentRole && in_array($perm['slug'], ['exams.take'])) {
                $this->assignPermission($connection, $studentRole['id'], $perm['id']);
            }
        }
    }

    private function assignPermission(Connection $conn, string $roleId, string $permId): void
    {
        $exists = $conn->fetchOne('SELECT 1 FROM role_permissions WHERE role_id = ? AND permission_id = ?', [$roleId, $permId]);
        if (!$exists) {
            $conn->insert('role_permissions', [
                'role_id' => $roleId,
                'permission_id' => $permId
            ]);
        }
    }
}
