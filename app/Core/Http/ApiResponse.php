<?php

declare(strict_types=1);

namespace App\Core\Http;

class ApiResponse
{
    public static function json(mixed $data, int $status = 200, array $headers = []): Response
    {
        return new Response(['data' => $data, 'status' => $status], $status, $headers);
    }

    public static function error(string $message, int $code = 400, array $errors = []): Response
    {
        return self::json(['message' => $message, 'errors' => $errors], $code);
    }
}
