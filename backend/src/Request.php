<?php

declare(strict_types=1);

namespace App;

final class Request
{
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers,
        public readonly ?array $json,
    ) {}

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $headers = [];
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string)$_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = (string)$_SERVER['CONTENT_LENGTH'];
        }
        foreach ($_SERVER as $k => $v) {
            if (!str_starts_with($k, 'HTTP_')) {
                continue;
            }
            $name = str_replace('_', '-', strtolower(substr($k, 5)));
            $headers[$name] = (string)$v;
        }

        $json = null;
        $contentType = $headers['content-type'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $json = $decoded;
                }
            }
        }

        return new self(
            method: $method,
            path: $path,
            query: $_GET ?? [],
            headers: $headers,
            json: $json
        );
    }

    public function bearerToken(): ?string
    {
        $auth = $this->headers['authorization'] ?? '';
        if (!str_starts_with(strtolower($auth), 'bearer ')) {
            return null;
        }
        return trim(substr($auth, 7)) ?: null;
    }
}

