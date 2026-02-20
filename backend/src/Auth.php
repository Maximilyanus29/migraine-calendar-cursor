<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Auth
{
    public static function userId(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;
        if (is_int($id)) {
            return $id;
        }
        if (is_string($id) && ctype_digit($id)) {
            return (int)$id;
        }
        return null;
    }

    public static function requireUserId(): int
    {
        $id = self::userId();
        if ($id === null) {
            throw new HttpError(401, 'UNAUTHORIZED');
        }
        return $id;
    }

    public static function getUser(PDO $pdo, int $userId): array
    {
        $st = $pdo->prepare('SELECT id, email FROM users WHERE id = :id');
        $st->execute(['id' => $userId]);
        $row = $st->fetch();
        if (!is_array($row)) {
            throw new HttpError(401, 'UNAUTHORIZED');
        }
        return $row;
    }
}

