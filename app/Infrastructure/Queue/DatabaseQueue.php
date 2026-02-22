<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue;

use Doctrine\DBAL\Connection;
use Exception;
use Ramsey\Uuid\Uuid;

class DatabaseQueue implements QueueInterface
{
    private Connection $db;
    private string $table = 'jobs';

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function push(string $jobClass, array $data = []): string
    {
        return $this->pushDelayed($jobClass, $data, 0);
    }

    public function pushDelayed(string $jobClass, array $data, int $delaySeconds): string
    {
        $id = Uuid::uuid4()->toString();
        $payload = json_encode($data, JSON_THROW_ON_ERROR);
        $availableAt = time() + $delaySeconds;

        $this->db->insert($this->table, [
            'id' => $id,
            'job_class' => $jobClass,
            'payload' => $payload,
            'available_at' => $availableAt,
            'created_at' => time(),
            'attempts' => 0,
        ]);

        return $id;
    }

    public function pop(): ?JobInterface
    {
        $this->db->beginTransaction();

        try {
            $qb = $this->db->createQueryBuilder();
            $qb->select('*')
               ->from($this->table)
               ->where('reserved_at IS NULL')
               ->andWhere('available_at <= :now')
               ->setParameter('now', time())
               ->orderBy('available_at', 'ASC')
               ->setMaxResults(1);

            // Locking strategy would be here

            $result = $qb->executeQuery();
            $jobData = $result->fetchAssociative();

            if (!$jobData) {
                $this->db->commit();
                return null;
            }

            // Mark as reserved (or delete immediately for at-most-once)
            // To be safer, let's reserve it. But without ack mechanism in interface, we can't delete it later easily.
            // So we'll delete it now.
            $this->db->delete($this->table, ['id' => $jobData['id']]);

            $this->db->commit();

            $jobClass = $jobData['job_class'];
            $payload = json_decode($jobData['payload'], true, 512, JSON_THROW_ON_ERROR);

            if (!class_exists($jobClass)) {
                // Log error?
                return null;
            }

            // Assume job constructor takes payload
            // Alternatively, use a setter if interface had one.
            // But since we control the job classes, we can enforce constructor usage.
            // Check if class implements JobInterface
            if (!in_array(JobInterface::class, class_implements($jobClass))) {
                return null;
            }

            return new $jobClass($payload);

        } catch (Exception $e) {
            $this->db->rollBack();
            // Log error
            return null;
        }
    }
}
