<?php

declare(strict_types=1);

namespace App\Core\Http;

class Response
{
    public function __construct(
        public mixed $content,
        public int $status = 200,
        public array $headers = []
    ) {}

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        if (is_array($this->content)) {
            header('Content-Type: application/json');
            echo json_encode($this->content, JSON_THROW_ON_ERROR);
        } else {
            echo $this->content;
        }
    }
}
