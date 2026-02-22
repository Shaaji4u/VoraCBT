<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Domain\Proctoring\Service\ProctoringService;
use App\Core\Service\AuditLogService;
use App\Domain\Proctoring\Service\DeviceFingerprintService;
use Exception;

class ProctoringController
{
    private ProctoringService $proctoringService;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->auditLog = new AuditLogService();
        $fingerprint = new DeviceFingerprintService();
        $this->proctoringService = new ProctoringService($this->auditLog, $fingerprint);
    }

    public function logEvent(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $token = $input['token'] ?? null;
            $eventType = $input['event_type'] ?? null;
            $payload = $input['payload'] ?? [];
            $severity = $input['severity'] ?? 'info';

            if (!$token || !$eventType) {
                $response = ApiResponse::error('Missing token or event_type', 400);
                $response->send();
                return;
            }

            $sessionId = $this->proctoringService->getSessionIdByToken($token);
            if (!$sessionId) {
                $response = ApiResponse::error('Invalid session token', 403);
                $response->send();
                return;
            }

            $this->proctoringService->logEvent($sessionId, $eventType, $payload, $severity);

            $response = ApiResponse::json(['status' => 'logged']);
            $response->send();
        } catch (Exception $e) {
            $response = ApiResponse::error($e->getMessage(), 500);
            $response->send();
        }
    }

    public function heartbeat(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $token = $input['token'] ?? null;

            if (!$token) {
                 $response = ApiResponse::error('Missing token', 400);
                 $response->send();
                 return;
            }

            $sessionId = $this->proctoringService->getSessionIdByToken($token);
            if (!$sessionId) {
                 $response = ApiResponse::error('Invalid session token', 403);
                 $response->send();
                 return;
            }

            // Log heartbeat to keep session active
            $this->proctoringService->logEvent($sessionId, 'heartbeat', [], 'info');

            $response = ApiResponse::json(['status' => 'alive']);
            $response->send();
        } catch (Exception $e) {
            $response = ApiResponse::error($e->getMessage(), 500);
            $response->send();
        }
    }
}
