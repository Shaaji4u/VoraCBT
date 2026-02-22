<?php

declare(strict_types=1);

namespace App\Core\Http;

interface MiddlewareInterface
{
    public function handle(array $request, callable $next): mixed;
}
