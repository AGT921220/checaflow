<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain;

use App\Features\Schedules\Domain\Exceptions\InvalidTimeOfDay;
use DateTimeImmutable;

final readonly class TimeOfDay
{
    public function __construct(
        private int $hour,
        private int $minute,
    ) {
        if ($this->hour < 0 || $this->hour > 23 || $this->minute < 0 || $this->minute > 59) {
            throw new InvalidTimeOfDay('fuera de rango');
        }
    }

    public static function fromString(string $value): self
    {
        $parsed = DateTimeImmutable::createFromFormat('!H:i:s', $value);

        if ($parsed === false) {
            $parsed = DateTimeImmutable::createFromFormat('!H:i', $value);
        }

        if ($parsed === false || self::hasDateErrors()) {
            throw new InvalidTimeOfDay($value);
        }

        return new self((int) $parsed->format('H'), (int) $parsed->format('i'));
    }

    public function hour(): int
    {
        return $this->hour;
    }

    public function minute(): int
    {
        return $this->minute;
    }

    public function isAfter(self $other): bool
    {
        if ($this->hour !== $other->hour) {
            return $this->hour > $other->hour;
        }

        return $this->minute > $other->minute;
    }

    private static function hasDateErrors(): bool
    {
        $errors = DateTimeImmutable::getLastErrors();

        if ($errors === false) {
            return false;
        }

        return $errors['warning_count'] > 0 || $errors['error_count'] > 0;
    }
}
