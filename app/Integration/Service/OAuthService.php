<?php

declare(strict_types=1);

namespace App\Integration\Service;

use App\Core\Config\Environment;
use App\Core\Http\HttpClientInterface;
use App\Core\Http\CurlHttpClient;
use Exception;

class OAuthService
{
    private HttpClientInterface $http;
    private TokenManager $tokenManager;
    private Environment $env;

    public function __construct(HttpClientInterface $http = null, TokenManager $tokenManager = null)
    {
        $this->http = $http ?? new CurlHttpClient();
        $this->tokenManager = $tokenManager ?? new TokenManager();
        $this->env = Environment::getInstance();
    }

    public function getAccessToken(): ?string
    {
        if (!$this->env->isConnectedMode()) {
            return null;
        }

        $token = $this->tokenManager->getAccessToken('sms');
        if ($token) {
            return $token;
        }

        // Token expired or missing, try refresh
        $refreshToken = $this->tokenManager->getRefreshToken('sms');
        if ($refreshToken) {
            try {
                return $this->refreshAccessToken($refreshToken);
            } catch (Exception $e) {
                // Refresh failed, fall back to new login
            }
        }

        return $this->authenticate();
    }

    public function forceRefresh(): ?string
    {
        if (!$this->env->isConnectedMode()) {
            return null;
        }

        $refreshToken = $this->tokenManager->getRefreshToken('sms');
        if ($refreshToken) {
            try {
                return $this->refreshAccessToken($refreshToken);
            } catch (Exception $e) {
                // Refresh failed, fall back to new login
            }
        }
        return $this->authenticate();
    }

    private function authenticate(): string
    {
        $baseUrl = $this->env->get('SMS_API_BASE_URL');
        if (!$baseUrl) {
             throw new Exception('SMS_API_BASE_URL not configured');
        }
        $url = rtrim($baseUrl, '/') . '/oauth/token';
        $clientId = $this->env->get('SMS_CLIENT_ID');
        $clientSecret = $this->env->get('SMS_CLIENT_SECRET');

        $response = $this->http->request('POST', $url, [
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]
        ]);

        if ($response['status'] !== 200) {
            throw new Exception("OAuth authentication failed: " . $response['body']);
        }

        $data = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);

        $accessToken = $data['access_token'];
        $refreshToken = $data['refresh_token'] ?? null;
        $expiresIn = $data['expires_in'];

        $this->tokenManager->storeToken('sms', $accessToken, $refreshToken, $expiresIn);

        return $accessToken;
    }

    private function refreshAccessToken(string $refreshToken): string
    {
        $baseUrl = $this->env->get('SMS_API_BASE_URL');
        $url = rtrim($baseUrl, '/') . '/oauth/token';
        $clientId = $this->env->get('SMS_CLIENT_ID');
        $clientSecret = $this->env->get('SMS_CLIENT_SECRET');

        $response = $this->http->request('POST', $url, [
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]
        ]);

        if ($response['status'] !== 200) {
            throw new Exception("OAuth refresh failed: " . $response['body']);
        }

        $data = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);

        $accessToken = $data['access_token'];
        $newRefreshToken = $data['refresh_token'] ?? $refreshToken;
        $expiresIn = $data['expires_in'];

        $this->tokenManager->storeToken('sms', $accessToken, $newRefreshToken, $expiresIn);

        return $accessToken;
    }
}
