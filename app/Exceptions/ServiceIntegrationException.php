<?php

namespace App\Exceptions;

use RuntimeException;

class ServiceIntegrationException extends RuntimeException
{
    public function __construct(
        string $message,
        protected int $status = 422,
        protected array $context = [],
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function context(): array
    {
        return $this->context;
    }
}
