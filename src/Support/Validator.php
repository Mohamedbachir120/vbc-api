<?php

declare(strict_types=1);

namespace App\Support;

class Validator
{
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        if (!isset($this->data[$field]) || $this->data[$field] === '') {
            $this->errors[$field] = "$label is required";
        }

        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        if (isset($this->data[$field]) && is_string($this->data[$field]) && mb_strlen($this->data[$field]) < $min) {
            $this->errors[$field] = "$label must be at least $min characters";
        }

        return $this;
    }

    public function email(string $field, string $label = 'Email'): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$label must be a valid email address";
        }

        return $this;
    }

    public function url(string $field, string $label): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field] = "$label must be a valid URL";
        }

        return $this;
    }

    public function hexColor(string $field, string $label): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $this->data[$field])) {
            $this->errors[$field] = "$label must be a hex color like #6366f1";
        }

        return $this;
    }

    public function password(string $field, string $label = 'Password'): self
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && (
            mb_strlen($value) < 8
            || !preg_match('/[A-Z]/', $value)
            || !preg_match('/[0-9]/', $value)
        )) {
            $this->errors[$field] = "$label must be at least 8 characters and include 1 uppercase letter and 1 number";
        }

        return $this;
    }

    public function in(string $field, array $allowed, string $label): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field] = "$label must be one of: " . implode(', ', $allowed);
        }

        return $this;
    }

    public function integer(string $field, string $label): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "$label must be a number";
        }

        return $this;
    }

    public function dateTime(string $field, string $label): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            try {
                new \DateTimeImmutable((string) $this->data[$field]);
            } catch (\Exception) {
                $this->errors[$field] = "$label must be a valid date/time";
            }
        }

        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function throwIfFails(string $message = 'Validation failed'): void
    {
        if ($this->fails()) {
            throw ApiException::validation($message, $this->errors());
        }
    }
}
