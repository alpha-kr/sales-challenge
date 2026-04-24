<?php

namespace App\Exceptions;

use App\Domain\Shared\Enums\ApiErrorCode;
use RuntimeException;

class DomainException extends RuntimeException
{
    public function __construct(
        public readonly ApiErrorCode $errorCode,
        string $message = '',
        public readonly int $statusCode = 422,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }
}
