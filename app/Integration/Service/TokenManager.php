<?php

declare(strict_types=1);

namespace App\Integration\Service;

use App\Core\Database\DatabaseManager;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class TokenManager
{
    private Connection $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getConnection();
    }

    public function storeToken(string $provider, string $accessToken, ?string $refreshToken, int $expiresIn): void
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

        // Check if exists
        $existing = $this->db->fetchAssociative("SELECT id FROM oauth_tokens WHERE provider = ?", [$provider]);

        if ($existing) {
            $this->db->update('oauth_tokens', [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $existing['id']]);
        } else {
            $this->db->insert('oauth_tokens', [
                'id' => Uuid::uuid4()->toString(),
                'provider' => $provider,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function getAccessToken(string $provider): ?string
    {
        $token = $this->getTokenData($provider);
        if (!$token) {
            return null;
        }

        // Check expiration (buffer 60s)
        if (strtotime($token['expires_at']) < time() + 60) {
            return null; // Expired
        }

        return $token['access_token'];
    }

    public function getRefreshToken(string $provider): ?string
    {
        $token = $this->getTokenData($provider);
        if (!$token) {
            return null;
        }
        return $token['refresh_token'];
    }

    private function getTokenData(string $provider): ?array
    {
        $result = $this->db->fetchAssociative("SELECT * FROM oauth_tokens WHERE provider = ?", [$provider]);
        return $result ?: null;
    }
}
