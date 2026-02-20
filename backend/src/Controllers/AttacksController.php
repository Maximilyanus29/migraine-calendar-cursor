<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\HttpError;
use App\Request;
use App\Response;
use PDO;

final class AttacksController
{
    public function __construct(private readonly PDO $pdo) {}

    public function listMonth(Request $req): Response
    {
        $userId = Auth::requireUserId();

        $month = (string)($req->query['month'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new HttpError(422, 'VALIDATION_ERROR', ['fields' => ['month']]);
        }

        $start = $month . '-01';
        $end = date('Y-m-d', strtotime($start . ' +1 month'));

        $st = $this->pdo->prepare(
            'SELECT attack_date, pain_level
             FROM attacks
             WHERE user_id = :user_id AND attack_date >= :start AND attack_date < :end
             ORDER BY attack_date ASC'
        );
        $st->execute(['user_id' => $userId, 'start' => $start, 'end' => $end]);
        $rows = $st->fetchAll();

        return Response::json(['attacks' => $rows]);
    }

    public function getByDate(Request $req, string $date): Response
    {
        $userId = Auth::requireUserId();
        $this->assertDate($date);

        $attack = $this->fetchAttack($userId, $date);
        return Response::json(['attack' => $attack]);
    }

    public function template(Request $req): Response
    {
        $userId = Auth::requireUserId();
        $date = (string)($req->query['date'] ?? '');
        $this->assertDate($date);

        $st = $this->pdo->prepare(
            'SELECT start_time, end_time, pain_level, medications, notes
             FROM attacks
             WHERE user_id = :user_id AND attack_date < :date
             ORDER BY attack_date DESC
             LIMIT 1'
        );
        $st->execute(['user_id' => $userId, 'date' => $date]);
        $row = $st->fetch();

        $template = is_array($row) ? $row : [
            'start_time' => null,
            'end_time' => null,
            'pain_level' => null,
            'medications' => null,
            'notes' => null,
        ];

        return Response::json(['template' => $template]);
    }

    public function upsert(Request $req, string $date): Response
    {
        $userId = Auth::requireUserId();
        $this->assertDate($date);

        $body = $req->json ?? [];

        $startTime = $this->nullableTime($body['start_time'] ?? null);
        $endTime = $this->nullableTime($body['end_time'] ?? null);
        $painLevel = $this->nullableInt($body['pain_level'] ?? null);
        if ($painLevel !== null && ($painLevel < 0 || $painLevel > 10)) {
            throw new HttpError(422, 'VALIDATION_ERROR', ['fields' => ['pain_level']]);
        }

        $medications = $this->nullableString($body['medications'] ?? null);
        $notes = $this->nullableString($body['notes'] ?? null);

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = '
                INSERT INTO attacks (user_id, attack_date, start_time, end_time, pain_level, medications, notes)
                VALUES (:user_id, :attack_date, :start_time, :end_time, :pain_level, :medications, :notes)
                ON CONFLICT(user_id, attack_date) DO UPDATE SET
                  start_time = excluded.start_time,
                  end_time = excluded.end_time,
                  pain_level = excluded.pain_level,
                  medications = excluded.medications,
                  notes = excluded.notes
            ';
        } else {
            // MySQL upsert via ON DUPLICATE KEY
            $sql = '
                INSERT INTO attacks (user_id, attack_date, start_time, end_time, pain_level, medications, notes)
                VALUES (:user_id, :attack_date, :start_time, :end_time, :pain_level, :medications, :notes)
                ON DUPLICATE KEY UPDATE
                  start_time = VALUES(start_time),
                  end_time = VALUES(end_time),
                  pain_level = VALUES(pain_level),
                  medications = VALUES(medications),
                  notes = VALUES(notes)
            ';
        }

        $st = $this->pdo->prepare($sql);
        $st->execute([
            'user_id' => $userId,
            'attack_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'pain_level' => $painLevel,
            'medications' => $medications,
            'notes' => $notes,
        ]);

        $attack = $this->fetchAttack($userId, $date);
        return Response::json(['attack' => $attack], 200);
    }

    public function delete(Request $req, string $date): Response
    {
        $userId = Auth::requireUserId();
        $this->assertDate($date);

        $st = $this->pdo->prepare('DELETE FROM attacks WHERE user_id = :user_id AND attack_date = :attack_date');
        $st->execute(['user_id' => $userId, 'attack_date' => $date]);

        return Response::empty(204);
    }

    private function fetchAttack(int $userId, string $date): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT attack_date, start_time, end_time, pain_level, medications, notes
             FROM attacks
             WHERE user_id = :user_id AND attack_date = :attack_date
             LIMIT 1'
        );
        $st->execute(['user_id' => $userId, 'attack_date' => $date]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    private function assertDate(string $date): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new HttpError(422, 'VALIDATION_ERROR', ['fields' => ['date']]);
        }
        $ts = strtotime($date);
        if ($ts === false || date('Y-m-d', $ts) !== $date) {
            throw new HttpError(422, 'VALIDATION_ERROR', ['fields' => ['date']]);
        }
    }

    private function nullableString(mixed $v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : $s;
    }

    private function nullableInt(mixed $v): ?int
    {
        if ($v === null || $v === '') return null;
        if (is_int($v)) return $v;
        if (is_string($v) && preg_match('/^-?\d+$/', $v)) return (int)$v;
        throw new HttpError(422, 'VALIDATION_ERROR');
    }

    private function nullableTime(mixed $v): ?string
    {
        if ($v === null || $v === '') return null;
        $s = (string)$v;
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $s)) {
            throw new HttpError(422, 'VALIDATION_ERROR');
        }
        // normalize to HH:MM:SS for MySQL TIME
        if (strlen($s) === 5) {
            $s .= ':00';
        }
        return $s;
    }
}

