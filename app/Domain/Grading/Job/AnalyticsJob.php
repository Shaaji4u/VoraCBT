<?php

declare(strict_types=1);

namespace App\Domain\Grading\Job;

use App\Core\Container\Container;
use App\Domain\Grading\Service\AnalyticsService;
use App\Infrastructure\Queue\JobInterface;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;

class AnalyticsJob implements JobInterface
{
    private string $sessionId;

    public function __construct(array $payload)
    {
        if (!isset($payload['session_id'])) {
            throw new InvalidArgumentException("AnalyticsJob requires 'session_id' in payload.");
        }
        $this->sessionId = $payload['session_id'];
    }

    public function handle(): void
    {
        $container = Container::getInstance();
        /** @var Connection $db */
        $db = $container->get(Connection::class);

        $examId = $db->fetchOne("SELECT exam_template_id FROM exam_sessions WHERE id = ?", [$this->sessionId]);

        if (!$examId) {
            return;
        }

        /** @var AnalyticsService $service */
        $service = $container->get(AnalyticsService::class);
        $service->calculateExamAnalytics($examId);
    }
}
