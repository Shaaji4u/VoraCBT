<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Http\ApiResponse;
use App\Domain\Identity\StudentImportService;
use App\Domain\Identity\StaffImportService;
use App\Domain\Identity\CredentialExportService;
use App\Domain\Identity\OAuthLinker;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class IdentityController
{
    private StudentImportService $studentImportService;
    private StaffImportService $staffImportService;
    private CredentialExportService $exportService;
    private OAuthLinker $oauthLinker;

    public function __construct()
    {
        $this->studentImportService = new StudentImportService();
        $this->staffImportService = new StaffImportService();
        $this->exportService = new CredentialExportService();
        $this->oauthLinker = new OAuthLinker();
    }

    private function getAdminId(): ?string
    {
        $jwtSecret = $_ENV['JWT_SECRET'] ?? '';
        if ($jwtSecret === '') {
            return null;
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }

        $jwt = $matches[1];
        try {
            $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
        } catch (Exception $e) {
            return null;
        }

        $adminId = $decoded->sub ?? $decoded->id ?? null;
        if (!$adminId) {
            return null;
        }

        $db = \App\Core\Database\DatabaseManager::getConnection();
        $role = $db->fetchOne(
            "SELECT r.slug FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?",
            [$adminId]
        );

        return $role === 'admin' ? $adminId : null;
    }

    private function getTenantId(string $adminId): int
    {
        $db = \App\Core\Database\DatabaseManager::getConnection();
        $tenantId = $db->fetchOne("SELECT tenant_id FROM users WHERE id = ?", [$adminId]);
        return $tenantId ? (int)$tenantId : 1;
    }

    public function preview(): void
    {
        try {
            $adminId = $this->getAdminId();
            if (!$adminId) {
                $response = ApiResponse::error('Unauthorized', 401);
                $response->send();
                return;
            }

            if (!isset($_FILES['file'])) {
                $response = ApiResponse::error('No file uploaded', 400);
                $response->send();
                return;
            }

            $file = $_FILES['file'];

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB Limit
                $response = ApiResponse::error('File exceeds 5MB limit', 400);
                $response->send();
                return;
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                 $response = ApiResponse::error('Upload error', 400);
                 $response->send();
                 return;
            }

            $type = $_POST['type'] ?? 'student';
            $service = ($type === 'staff') ? $this->staffImportService : $this->studentImportService;

            $tenantId = $this->getTenantId($adminId);
            $service->setTenantId($tenantId);

            $report = $service->preview($file['tmp_name']);
            $response = ApiResponse::json($report);
            $response->send();
        } catch (Exception $e) {
            $response = ApiResponse::error($e->getMessage(), 500);
            $response->send();
        }
    }

    public function commit(): void
    {
        try {
            $adminId = $this->getAdminId();
            if (!$adminId) {
                $response = ApiResponse::error('Unauthorized', 401);
                $response->send();
                return;
            }

            if (!isset($_FILES['file'])) {
                 $response = ApiResponse::error('No file uploaded', 400);
                 $response->send();
                 return;
            }

            $file = $_FILES['file'];

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB Limit
                $response = ApiResponse::error('File exceeds 5MB limit', 400);
                $response->send();
                return;
            }

             if ($file['error'] !== UPLOAD_ERR_OK) {
                 $response = ApiResponse::error('Upload error', 400);
                 $response->send();
                 return;
            }

            $type = $_POST['type'] ?? 'student';
            $service = ($type === 'staff') ? $this->staffImportService : $this->studentImportService;

            $tenantId = $this->getTenantId($adminId);
            $service->setTenantId($tenantId);

            $result = $service->commit($file['tmp_name'], $adminId);
            $response = ApiResponse::json($result);
            $response->send();
        } catch (Exception $e) {
            $response = ApiResponse::error($e->getMessage(), 500);
            $response->send();
        }
    }

    public function export(): void
    {
        try {
            $filters = [
                'type' => $_GET['type'] ?? 'student',
                'class_id' => $_GET['class_id'] ?? null,
                'import_id' => $_GET['import_id'] ?? null,
                'student_ids' => isset($_GET['student_ids']) ? explode(',', $_GET['student_ids']) : [],
                'role' => $_GET['role'] ?? null,
                'department' => $_GET['department'] ?? null,
            ];

            $format = $_GET['format'] ?? 'csv';
            $regenerate = filter_var($_GET['regenerate'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $adminId = $this->getAdminId();
            if (!$adminId) {
                $response = ApiResponse::error('Unauthorized', 401);
                $response->send();
                return;
            }

            $content = $this->exportService->export($filters, $format, $regenerate, $adminId);

            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="credentials.csv"');
                echo $content;
            } else {
                echo $content;
            }
        } catch (Exception $e) {
             $response = ApiResponse::error($e->getMessage(), 500);
             $response->send();
        }
    }

    public function linkOAuth(): void
    {
        try {
            $adminId = $this->getAdminId();
            if (!$adminId) {
                $response = ApiResponse::error('Unauthorized', 401);
                $response->send();
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['user_id']) || empty($data['oauth_id'])) {
                $response = ApiResponse::error('Missing user_id or oauth_id', 400);
                $response->send();
                return;
            }

            $this->oauthLinker->linkAccount(
                $data['user_id'],
                $data['oauth_id'],
                $adminId,
                $data['context'] ?? []
            );

            $response = ApiResponse::json(['message' => 'Linked successfully']);
            $response->send();
        } catch (Exception $e) {
            $response = ApiResponse::error($e->getMessage(), 500);
            $response->send();
        }
    }

    public function unlinkOAuth(): void
    {
        try {
            $adminId = $this->getAdminId();
            if (!$adminId) {
                $response = ApiResponse::error('Unauthorized', 401);
                $response->send();
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['user_id'])) {
                $response = ApiResponse::error('Missing user_id', 400);
                $response->send();
                return;
            }

            $this->oauthLinker->unlinkAccount($data['user_id'], $adminId);

            $response = ApiResponse::json(['message' => 'Unlinked successfully']);
            $response->send();
        } catch (Exception $e) {
            $response = ApiResponse::error($e->getMessage(), 500);
            $response->send();
        }
    }
}
