<?php

declare(strict_types=1);

use App\Core\Database\Seeder\SeederInterface;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class SampleDataSeeder implements SeederInterface
{
    public function run(Connection $connection): void
    {
        // 1. Create Users
        $users = [
            ['email' => 'admin@cbt.local', 'role' => 'admin', 'first' => 'Admin', 'last' => 'User'],
            ['email' => 'teacher@cbt.local', 'role' => 'teacher', 'first' => 'Teacher', 'last' => 'One'],
            ['email' => 'student@cbt.local', 'role' => 'student', 'first' => 'Student', 'last' => 'One'],
        ];

        foreach ($users as $user) {
            $roleId = $connection->fetchOne('SELECT id FROM roles WHERE slug = ?', [$user['role']]);

            $exists = $connection->fetchOne('SELECT id FROM users WHERE email = ?', [$user['email']]);
            if (!$exists && $roleId) {
                $connection->insert('users', [
                    'id' => Uuid::uuid4()->toString(),
                    'email' => $user['email'],
                    'password' => password_hash('password', PASSWORD_BCRYPT),
                    'first_name' => $user['first'],
                    'last_name' => $user['last'],
                    'role_id' => $roleId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 2. Create Sample Questions (Math)
        $teacherId = $connection->fetchOne('SELECT id FROM users WHERE email = ?', ['teacher@cbt.local']);

        if ($teacherId) {
            $questions = [
                [
                    'type' => 'mcq',
                    'content' => json_encode([
                        'prompt' => 'What is 2 + 2?',
                        'options' => [
                            ['id' => 'a', 'text' => '3'],
                            ['id' => 'b', 'text' => '4'],
                            ['id' => 'c', 'text' => '5'],
                        ],
                        'answer' => 'b'
                    ]),
                    'metadata' => json_encode(['subject' => 'Math', 'difficulty' => 'easy']),
                ],
                [
                    'type' => 'essay',
                    'content' => json_encode([
                        'prompt' => 'Explain the theory of relativity.',
                    ]),
                    'metadata' => json_encode(['subject' => 'Physics', 'difficulty' => 'hard']),
                ]
            ];

            foreach ($questions as $q) {
                $qId = Uuid::uuid4()->toString();
                $connection->insert('questions', [
                    'id' => $qId,
                    'type' => $q['type'],
                    'content' => $q['content'],
                    'metadata' => $q['metadata'],
                    'version' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_by' => $teacherId,
                ]);

                // Version history
                $connection->insert('question_versions', [
                    'id' => Uuid::uuid4()->toString(),
                    'question_id' => $qId,
                    'version' => 1,
                    'type' => $q['type'],
                    'content' => $q['content'],
                    'metadata' => $q['metadata'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $teacherId,
                ]);
            }

            // 3. Create Exam Template
            $examId = Uuid::uuid4()->toString();
            $connection->insert('exam_templates', [
                'id' => $examId,
                'title' => 'Midterm Exam',
                'description' => 'Math and Physics',
                'duration_minutes' => 60,
                'passing_score' => 50,
                'total_score' => 100,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'created_by' => $teacherId,
            ]);

            // Section
            $sectionId = Uuid::uuid4()->toString();
            $connection->insert('exam_sections', [
                'id' => $sectionId,
                'exam_template_id' => $examId,
                'title' => 'General Knowledge',
                'section_order' => 1,
            ]);

            // Add questions to section
            // Need to fetch question IDs
            $qIds = $connection->fetchFirstColumn('SELECT id FROM questions WHERE created_by = ?', [$teacherId]);
            foreach ($qIds as $index => $qid) {
                $connection->insert('exam_questions', [
                    'id' => Uuid::uuid4()->toString(),
                    'exam_section_id' => $sectionId,
                    'question_id' => $qid,
                    'question_order' => $index + 1,
                    'marks' => 10,
                ]);
            }
        }
    }
}
