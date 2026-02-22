<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

class FileCache implements CacheInterface
{
    private string $storagePath;

    public function __construct(string $storagePath = 'storage/cache')
    {
        $this->storagePath = $storagePath;
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->storagePath . '/' . md5($key);
        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data['expires_at'] < time()) {
            unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $file = $this->storagePath . '/' . md5($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];
        return (bool) file_put_contents($file, serialize($data));
    }

    public function delete(string $key): bool
    {
        $file = $this->storagePath . '/' . md5($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->storagePath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
}
