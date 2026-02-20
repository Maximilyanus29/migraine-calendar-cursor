<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Router
{
    private readonly PDO $pdo;

    public function __construct(private readonly array $env)
    {
        $this->pdo = Db::pdo($env);
    }

    public function dispatch(Request $req): Response
    {
        try {
            // CORS + preflight for API
            if (str_starts_with($req->path, '/api/')) {
                $corsHeaders = $this->corsHeaders($req);

                if ($req->method === 'OPTIONS') {
                    return Response::empty(204, $corsHeaders);
                }

                $resp = $this->dispatchApi($req);
                foreach ($corsHeaders as $k => $v) {
                    $resp = $resp->withHeader($k, $v);
                }
                return $resp;
            }

            // В dev фронтенд обычно отдаёт vite, поэтому тут только API.
            return Response::json(['error' => 'NOT_FOUND'], 404);
        } catch (HttpError $e) {
            return Response::json(['error' => $e->getMessage(), 'details' => $e->details], $e->status);
        } catch (\Throwable $e) {
            $isDev = ($this->env['APP_ENV'] ?? 'dev') === 'dev';
            return Response::json([
                'error' => 'INTERNAL_ERROR',
                'message' => $isDev ? $e->getMessage() : 'Internal error',
            ], 500);
        }
    }

    private function dispatchApi(Request $req): Response
    {
        // Auth
        if ($req->method === 'POST' && $req->path === '/api/login') {
            return (new Controllers\AuthController($this->pdo))->login($req);
        }
        if ($req->method === 'POST' && $req->path === '/api/logout') {
            return (new Controllers\AuthController($this->pdo))->logout($req);
        }
        if ($req->method === 'GET' && $req->path === '/api/me') {
            return (new Controllers\AuthController($this->pdo))->me($req);
        }

        // Attacks
        if ($req->method === 'GET' && $req->path === '/api/attacks') {
            return (new Controllers\AttacksController($this->pdo))->listMonth($req);
        }
        if ($req->method === 'GET' && $req->path === '/api/attacks/template') {
            return (new Controllers\AttacksController($this->pdo))->template($req);
        }
        if (preg_match('#^/api/attacks/(\d{4}-\d{2}-\d{2})$#', $req->path, $m)) {
            $date = $m[1];
            $ctrl = new Controllers\AttacksController($this->pdo);
            return match ($req->method) {
                'GET' => $ctrl->getByDate($req, $date),
                'PUT' => $ctrl->upsert($req, $date),
                'DELETE' => $ctrl->delete($req, $date),
                default => Response::json(['error' => 'METHOD_NOT_ALLOWED'], 405),
            };
        }

        return Response::json(['error' => 'NOT_FOUND'], 404);
    }

    private function corsHeaders(Request $req): array
    {
        $origin = $req->headers['origin'] ?? '';
        $allowedOrigin = $this->env['APP_CORS_ORIGIN'] ?? '';

        $headers = [
            'access-control-allow-methods' => 'GET,POST,PUT,DELETE,OPTIONS',
            'access-control-allow-headers' => 'content-type',
            'access-control-allow-credentials' => 'true',
        ];

        if ($allowedOrigin !== '' && $origin === $allowedOrigin) {
            $headers['access-control-allow-origin'] = $allowedOrigin;
            $headers['vary'] = 'Origin';
        }

        return $headers;
    }
}

