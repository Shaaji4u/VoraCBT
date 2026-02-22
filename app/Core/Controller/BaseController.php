<?php

declare(strict_types=1);

namespace App\Core\Controller;

use App\Core\Http\ApiResponse;
use App\Core\Http\Response;

abstract class BaseController
{
    protected function json(mixed $data, int $status = 200): Response
    {
        return ApiResponse::json($data, $status);
    }

    protected function error(string $message, int $status = 400): Response
    {
        return ApiResponse::error($message, $status);
    }
}
