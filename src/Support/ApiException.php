<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        private int $status,
        private string $errorCode,
        string $message,
        private ?array $details = null
    ) {
        parent::__construct($message);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self(404, 'NOT_FOUND', $message);
    }

    public static function unauthorized(string $message = 'Invalid or expired token'): self
    {
        return new self(401, 'UNAUTHORIZED', $message);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self(403, 'FORBIDDEN', $message);
    }

    public static function conflict(string $message): self
    {
        return new self(409, 'CONFLICT', $message);
    }

    public static function validation(string $message, array $details): self
    {
        return new self(422, 'VALIDATION_ERROR', $message, $details);
    }

    public static function badRequest(string $message): self
    {
        return new self(400, 'BAD_REQUEST', $message);
    }
}
