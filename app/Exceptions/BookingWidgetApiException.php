<?php

namespace App\Exceptions;

use RuntimeException;

class BookingWidgetApiException extends RuntimeException
{
    public function __construct(
        string $message,
        protected array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function context(): array
    {
        return $this->context;
    }
}
