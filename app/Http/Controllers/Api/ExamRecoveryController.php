<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Database\DatabaseManager;
use App\Core\Http\ApiResponse;
use App\Domain\Exam\Service\SessionRecoveryService;
use App\Domain\Exam\Service\TimerService;
use App\Domain\Proctoring\Service\SessionIntegrityService;
use Exception;

class ExamRecoveryController
{
    private SessionRecoveryService $service;
    private SessionIntegrityService $integrity;

    public function __construct()
    {
        $this->service = new SessionRecoveryService(DatabaseManager::getConnection(), new TimerService());
        $this->integrity = new SessionIntegrityService();
    }

    public function autosave(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $sessionId = $input['session_id'] ?? null;
            $token = $input['token'] ?? null;

            if (!$sessionId || !$token) {
                ApiResponse::error('Missing session_id or token', 400)->send();
                return;
            }

            if (!$this->integrity->validateSessionToken($sessionId, $token)) {
                ApiResponse::error('Invalid session token', 403)->send();
                return;
            }

            $result = $this->service->autosave(
                $sessionId,
                $input['last_question_id'] ?? null,
                $input['draft_payload'] ?? [],
                $input['client_timestamp'] ?? null
            );

            ApiResponse::json($result)->send();
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500)->send();
        }
    }

    public function resumeState(): void
    {
        try {
            $sessionId = $_GET['session_id'] ?? null;
            $token = $_GET['token'] ?? null;
            if (!$sessionId || !$token) {
                ApiResponse::error('Missing session_id or token', 400)->send();
                return;
            }

            if (!$this->integrity->validateSessionToken($sessionId, $token)) {
                ApiResponse::error('Invalid session token', 403)->send();
                return;
            }

            $state = $this->service->resumeState($sessionId);
            ApiResponse::json($state)->send();
        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), 500)->send();
        }
    }
}
