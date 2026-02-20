<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\HttpError;
use App\Request;
use App\Response;
use PDO;

final class AuthController
{
    public function __construct(private readonly PDO $pdo) {}

    public function login(Request $req): Response
    {
        $body = $req->json ?? [];
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new HttpError(422, 'VALIDATION_ERROR', ['fields' => ['email', 'password']]);
        }

        $st = $this->pdo->prepare('SELECT id, password_hash FROM users WHERE email = :email');
        $st->execute(['email' => $email]);
        $row = $st->fetch();
        if (!is_array($row) || !password_verify($password, (string)$row['password_hash'])) {
            throw new HttpError(401, 'INVALID_CREDENTIALS');
        }

        $_SESSION['user_id'] = (int)$row['id'];

        $user = Auth::getUser($this->pdo, (int)$row['id']);
        return Response::json(['user' => $user]);
    }

    public function logout(Request $req): Response
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        return Response::empty(204);
    }

    public function me(Request $req): Response
    {
        $userId = Auth::requireUserId();
        $user = Auth::getUser($this->pdo, $userId);
        return Response::json(['user' => $user]);
    }
}

