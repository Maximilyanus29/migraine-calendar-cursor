<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Db
{
    public static function pdo(array $env): PDO
    {
        $dsn = (string)($env['DB_DSN'] ?? '');
        if ($dsn === '') {
            $driver = (string)($env['DB_DRIVER'] ?? 'mysql');
            if ($driver === 'sqlite') {
                $path = (string)($env['DB_SQLITE_PATH'] ?? '');
                if ($path === '') {
                    $path = ':memory:';
                }
                $dsn = 'sqlite:' . $path;
            } else {
                if (!in_array('pdo_mysql', \PDO::getAvailableDrivers(), true)) {
                    throw new HttpError(500, 'DB_DRIVER_NOT_AVAILABLE', [
                        'hint' => 'В PHP нет pdo_mysql. Либо установите расширение, либо используйте DB_DRIVER=sqlite для dev.',
                    ]);
                }
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $env['DB_HOST'],
                    $env['DB_PORT'],
                    $env['DB_NAME']
                );
            }
        }

        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;
    }
}

