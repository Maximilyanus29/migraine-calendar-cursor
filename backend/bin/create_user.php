<?php

declare(strict_types=1);

use App\Autoload;
use App\Db;
use App\Env;

require __DIR__ . '/../src/Autoload.php';
require __DIR__ . '/../src/Env.php';
require __DIR__ . '/../src/Db.php';
require __DIR__ . '/../src/HttpError.php';

$root = dirname(__DIR__, 2);
Autoload::register(__DIR__ . '/../src');
Env::load($root);

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!is_string($email) || !is_string($password) || trim($email) === '' || $password === '') {
    fwrite(STDERR, "Usage: php backend/bin/create_user.php <email> <password>\n");
    exit(2);
}

$env = [
    'DB_DRIVER' => $_ENV['DB_DRIVER'] ?? 'mysql',
    'DB_DSN' => $_ENV['DB_DSN'] ?? '',
    'DB_SQLITE_PATH' => $_ENV['DB_SQLITE_PATH'] ?? ($root . '/db/app.sqlite'),
    'DB_HOST' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'DB_PORT' => (int)($_ENV['DB_PORT'] ?? 3306),
    'DB_NAME' => $_ENV['DB_NAME'] ?? 'migraine_calendar',
    'DB_USER' => $_ENV['DB_USER'] ?? 'root',
    'DB_PASS' => $_ENV['DB_PASS'] ?? '',
];

$pdo = Db::pdo($env);

$hash = password_hash($password, PASSWORD_DEFAULT);
$st = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (:email, :hash)');

try {
    $st->execute(['email' => $email, 'hash' => $hash]);
    fwrite(STDOUT, "OK: user created: {$email}\n");
} catch (PDOException $e) {
    if ((int)($e->errorInfo[1] ?? 0) === 1062) {
        fwrite(STDERR, "ERROR: user already exists: {$email}\n");
        exit(3);
    }
    throw $e;
}

