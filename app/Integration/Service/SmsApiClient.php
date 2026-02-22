<?php

declare(strict_types=1);

namespace App\Integration\Service;

use App\Core\Config\Environment;
use App\Core\Http\HttpClientInterface;
use App\Core\Http\CurlHttpClient;
use Exception;

class SmsApiClient
{
    private HttpClientInterface $http;
    private OAuthService $auth;
    private Environment $env;
    private IntegrationLogService $logger;

    public function __construct(
        HttpClientInterface $http = null,
        OAuthService $auth = null,
        IntegrationLogService $logger = null
    ) {
        $this->http = $http ?? new CurlHttpClient();
        $this->auth = $auth ?? new OAuthService($this->http);
        $this->logger = $logger ?? new IntegrationLogService();
        $this->env = Environment::getInstance();
    }

    public function request(string $method, string $endpoint, array $options = [], string $idempotencyKey = null): array
    {
        if (!$this->env->isConnectedMode()) {
            throw new Exception("Cannot make API request in standalone mode");
        }

        $baseUrl = $this->env->get('SMS_API_BASE_URL');
        if (!$baseUrl) {
             throw new Exception('SMS_API_BASE_URL not configured');
        }
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

        // Add Authorization header
        $token = $this->auth->getAccessToken();
        $headers = $options['headers'] ?? [];
        $headers['Authorization'] = 'Bearer ' . $token;
        $headers['Accept'] = 'application/json';

        $logId = null;
        if ($idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
            $logId = $this->logger->logRequest($idempotencyKey, $options['body'] ?? null);
        }

        $options['headers'] = $headers;

        try {
            $response = $this->http->request($method, $url, $options);

            if ($response['status'] === 401) {
                $newToken = $this->auth->forceRefresh();
                if ($newToken) {
                    $options['headers']['Authorization'] = 'Bearer ' . $newToken;
                    $response = $this->http->request($method, $url, $options);
                }
            }

            if ($logId) {
                // Parse body if JSON
                $responseBody = json_decode($response['body'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                     // If not JSON, store as string wrapper or raw?
                     // Schema expects JSON. If not JSON, maybe store null or error?
                     // Or wrap: {"raw": "..."}
                     $responseBody = ['raw_response' => $response['body']];
                }
                $this->logger->logResponse($logId, (string)$response['status'], $responseBody);
            }

            return $response;

        } catch (Exception $e) {
            if ($logId) {
                 $this->logger->logResponse($logId, 'ERROR', ['error' => $e->getMessage()]);
            }
            throw $e;
        }
    }
}
