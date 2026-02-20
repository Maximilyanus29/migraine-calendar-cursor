<?php

declare(strict_types=1);

namespace App;

final class Response
{
    private function __construct(
        private readonly int $status,
        private readonly array $headers,
        private readonly string $body,
    ) {}

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        $headers = array_merge([
            'content-type' => 'application/json; charset=utf-8',
        ], $headers);

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            $encoded = '{"error":"JSON_ENCODE_ERROR"}';
        }
        return new self($status, $headers, $encoded);
    }

    public static function empty(int $status = 204, array $headers = []): self
    {
        return new self($status, $headers, '');
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[strtolower($name)] = $value;
        return new self($this->status, $headers, $this->body);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $k => $v) {
            header($k . ': ' . $v);
        }
        echo $this->body;
    }
}

