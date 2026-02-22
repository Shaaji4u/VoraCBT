<?php

declare(strict_types=1);

namespace App\Core\Http;

use RuntimeException;

class CurlHttpClient implements HttpClientInterface
{
    public function request(string $method, string $url, array $options = []): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL');
        }

        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? null;
        $timeout = $options['timeout'] ?? 30;

        // Convert associative headers to list if needed, or assume list if key is integer
        $formattedHeaders = [];
        foreach ($headers as $key => $value) {
            if (is_int($key)) {
                $formattedHeaders[] = $value;
            } else {
                $formattedHeaders[] = "$key: $value";
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, true); // To capture response headers

        // Handle body
        if ($body !== null) {
            if (is_array($body)) {
                $body = json_encode($body, JSON_THROW_ON_ERROR);
                // Check if Content-Type is already set
                $hasContentType = false;
                foreach ($formattedHeaders as $h) {
                    if (stripos($h, 'Content-Type:') === 0) {
                        $hasContentType = true;
                        break;
                    }
                }
                if (!$hasContentType) {
                    $formattedHeaders[] = 'Content-Type: application/json';
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("cURL error: $error");
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headerStr = substr($response, 0, $headerSize);
        $bodyStr = substr($response, $headerSize);

        // Parse headers
        $responseHeaders = [];
        foreach (explode("\r\n", $headerStr) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $responseHeaders[trim($key)] = trim($value);
            }
        }

        return [
            'status' => $statusCode,
            'headers' => $responseHeaders,
            'body' => $bodyStr,
        ];
    }
}
