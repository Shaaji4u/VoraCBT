<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Core\Database\DatabaseManager;

class AuthController
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getConnection();
    }

    public function login(): void
    {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true) ?? [];

        $identity = $data['identity'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($identity) || empty($password)) {
            ApiResponse::error('Email/username and password are required', 400)->send();
            return;
        }

        // 1. Find user by email or username
        $user = $this->db->fetchAssociative(
            "SELECT * FROM users WHERE email = :identity OR username = :identity",
            ['identity' => $identity]
        );

        if (!$user) {
            ApiResponse::error('Invalid credentials', 401)->send();
            return;
        }

        // 2. Verify Password
        if (!password_verify($password, $user['password_hash'])) {
            ApiResponse::error('Invalid credentials', 401)->send();
            return;
        }

        // 3. Create Session (Simple PHP Session for MVP, or JWT if system supports it)
        // Check if system relies on standard PHP sessions
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Determine redirect based on role
        $redirect = '/';
        if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
            $redirect = '/admin/dashboard';
        } elseif ($user['role'] === 'student') {
            $redirect = '/student/dashboard';
        }

        ApiResponse::json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ],
            'redirect' => $redirect
        ])->send();
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();

        ApiResponse::json(['message' => 'Logged out successfully'])->send();
    }
}
