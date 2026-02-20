<?php

declare(strict_types=1);

namespace App;

final class App
{
    private function __construct(
        private readonly string $basePath,
        private readonly array $env,
    ) {}

    public static function bootstrap(string $basePath): self
    {
        $rootPath = dirname($basePath); // project root
        Env::load($rootPath);

        $env = [
            'APP_ENV' => $_ENV['APP_ENV'] ?? 'dev',
            'APP_SESSION_NAME' => $_ENV['APP_SESSION_NAME'] ?? 'migraine_calendar',
            'APP_CORS_ORIGIN' => $_ENV['APP_CORS_ORIGIN'] ?? '',
            'DB_DRIVER' => $_ENV['DB_DRIVER'] ?? 'mysql',
            'DB_DSN' => $_ENV['DB_DSN'] ?? '',
            'DB_SQLITE_PATH' => $_ENV['DB_SQLITE_PATH'] ?? ($rootPath . '/db/app.sqlite'),
            'DB_HOST' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'DB_PORT' => (int)($_ENV['DB_PORT'] ?? 3306),
            'DB_NAME' => $_ENV['DB_NAME'] ?? 'migraine_calendar',
            'DB_USER' => $_ENV['DB_USER'] ?? 'root',
            'DB_PASS' => $_ENV['DB_PASS'] ?? '',
        ];

        return new self($basePath, $env);
    }

    public function handle(): void
    {
        $this->bootSession();

        $request = Request::fromGlobals();
        $response = (new Router($this->env))->dispatch($request);
        $response->send();
    }

    private function bootSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($this->env['APP_SESSION_NAME']);
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => false,
            'path' => '/',
        ]);
        session_start();
    }
}

