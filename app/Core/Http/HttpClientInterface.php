<?php

declare(strict_types=1);

namespace App\Core\Http;

interface HttpClientInterface
{
    /**
     * @param string $method GET, POST, PUT, DELETE, etc.
     * @param string $url
     * @param array $options ['headers' => [], 'body' => mixed, 'timeout' => int]
     * @return array ['status' => int, 'headers' => array, 'body' => string]
     */
    public function request(string $method, string $url, array $options = []): array;
}
