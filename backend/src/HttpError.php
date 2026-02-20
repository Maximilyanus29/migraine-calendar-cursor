<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class HttpError extends RuntimeException
{
    public readonly int $status;
    public readonly array $details;

    public function __construct(int $status, string $code, array $details = [])
    {
        $this->status = $status;
        $this->details = $details;
        parent::__construct($code, $status);
    }
}

