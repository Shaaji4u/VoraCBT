<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Database\DatabaseManager;
use App\Core\Http\ApiResponse;
use App\Domain\Monitoring\Service\AdminLogViewerService;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class MonitoringController
{
    private AdminLogViewerService $logViewerService;

    public function __construct()
    {
        $this->logViewerService = new AdminLogViewerService(DatabaseManager::getConnection());
    }

    private function getAdminId(): ?string
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (strpos($name, 'HTTP_') === 0) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$key] = $value;
                }
            }
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }

        try {
            $jwtSecret = $_ENV['JWT_SECRET'] ?? '';
            if ($jwtSecret === '') {
                return null;
            }

            $decoded = JWT::decode($matches[1], new Key($jwtSecret, 'HS256'));
            $role = $decoded->role ?? null;
            if (!in_array($role, ['admin', 'super_admin'], true)) {
                return null;
            }

            return $decoded->sub ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function logs(): void
    {
        try {
            $adminId = $this->getAdminId();
            if (!$adminId) {
                ApiResponse::error('Unauthorized', 401)->send();
                return;
            }

            $filters = [
                'from' => $_GET['from'] ?? null,
                'to' => $_GET['to'] ?? null,
                'user_id' => $_GET['user_id'] ?? null,
                'exam_template_id' => $_GET['exam_template_id'] ?? null,
                'event_source' => $_GET['event_source'] ?? null,
                'limit' => $_GET['limit'] ?? 100,
            ];

            $logs = $this->logViewerService->listLogs($filters);
            ApiResponse::json([
                'data' => $logs,
                'count' => count($logs),
                'filters' => $filters,
            ])->send();
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500)->send();
        }
    }
}
