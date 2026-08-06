<?php

namespace App\Exceptions;

use RuntimeException;

class PlanLimitExceeded extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly ?int $limit,
        public readonly int $current,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : $errorCode);
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'error' => $this->errorCode,
            'message' => $this->getMessage(),
            'limit' => $this->limit,
            'current' => $this->current,
            'upgrade_required' => true,
        ];
    }
}
