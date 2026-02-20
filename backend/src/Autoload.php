<?php

declare(strict_types=1);

namespace App;

final class Autoload
{
    public static function register(string $srcPath): void
    {
        $baseDir = rtrim($srcPath, '/') . '/';
        spl_autoload_register(static function (string $class) use ($baseDir): void {
            $prefix = 'App\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $rel = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $rel) . '.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }
}

