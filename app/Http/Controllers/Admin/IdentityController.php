<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Http\ApiResponse;
use App\Domain\Identity\StudentImportService;
use App\Domain\Identity\CredentialExportService;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class IdentityController
{
    private StudentImportService $importService;
    private CredentialExportService $exportService;

    public function __construct()
    {
        $this->importService = new StudentImportService();
        $this->exportService = new CredentialExportService();
    }

    private function getAdminId(): ?string
    {
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

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
            try {
                $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'] ?? 'secret', 'HS256'));
                return $decoded->sub ?? $decoded->id ?? null;
            } catch (Exception $e) {
                return null;
            }
        }
        return null;
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

            $report = $this->importService->preview($file['tmp_name']);
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

            $result = $this->importService->commit($file['tmp_name'], $adminId);
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
                'class_id' => $_GET['class_id'] ?? null,
                'import_id' => $_GET['import_id'] ?? null,
                'student_ids' => isset($_GET['student_ids']) ? explode(',', $_GET['student_ids']) : []
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
}
