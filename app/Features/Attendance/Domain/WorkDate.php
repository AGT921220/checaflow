<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Attendance\Domain\Exceptions\InvalidWorkDate;
use DateTimeImmutable;
use DateTimeZone;

final readonly class WorkDate
{
    public function __construct(public string $value)
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $this->value);

        if ($parsed === false || self::hasErrors() || $parsed->format('Y-m-d') !== $this->value) {
            throw new InvalidWorkDate($this->value);
        }
    }

    public static function fromInstant(DateTimeImmutable $instant, DateTimeZone $zone): self
    {
        return new self($instant->setTimezone($zone)->format('Y-m-d'));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private static function hasErrors(): bool
    {
        $errors = DateTimeImmutable::getLastErrors();

        if ($errors === false) {
            return false;
        }

        return $errors['warning_count'] > 0 || $errors['error_count'] > 0;
    }
}
