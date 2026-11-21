<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain;

use App\Features\Schedules\Domain\Exceptions\ClockOutMustBeAfterClockIn;
use App\Features\Schedules\Domain\Exceptions\InvalidTolerance;
use App\Features\Schedules\Domain\Exceptions\ScheduleIsNotPersisted;
use App\Features\Schedules\Domain\Exceptions\ScheduleNameRequired;
use App\Features\Schedules\Domain\Exceptions\ScheduleNameTooLong;
use App\Features\Shared\Domain\CompanyId;

final readonly class Schedule
{
    public function __construct(
        private ?int $id,
        private CompanyId $companyId,
        private string $name,
        private TimeOfDay $clockIn,
        private TimeOfDay $clockOut,
        private int $lateToleranceMinutes,
        private int $allowedLunchMinutes,
        private WorkingDays $workingDays,
        private bool $isActive,
    ) {
        if ($this->id !== null && $this->id < 1) {
            throw new ScheduleIsNotPersisted;
        }

        if ($this->name === '') {
            throw new ScheduleNameRequired;
        }

        if (mb_strlen($this->name) > 255) {
            throw new ScheduleNameTooLong;
        }

        if ($this->lateToleranceMinutes < 0 || $this->lateToleranceMinutes > 65535) {
            throw new InvalidTolerance;
        }

        if ($this->allowedLunchMinutes < 0 || $this->allowedLunchMinutes > 65535) {
            throw new InvalidTolerance;
        }

        if (! $this->clockOut->isAfter($this->clockIn)) {
            throw new ClockOutMustBeAfterClockIn;
        }
    }

    /**
     * @param  list<string>  $workingDays
     */
    public static function open(
        CompanyId $companyId,
        string $name,
        string $clockInTime,
        string $clockOutTime,
        int $lateToleranceMinutes,
        int $allowedLunchMinutes,
        array $workingDays,
        bool $isActive = true,
    ): self {
        return new self(
            null,
            $companyId,
            trim($name),
            TimeOfDay::fromString($clockInTime),
            TimeOfDay::fromString($clockOutTime),
            $lateToleranceMinutes,
            $allowedLunchMinutes,
            WorkingDays::fromStrings($workingDays),
            $isActive,
        );
    }

    public function id(): int
    {
        if ($this->id === null) {
            throw new ScheduleIsNotPersisted;
        }

        return $this->id;
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function belongsTo(CompanyId $companyId): bool
    {
        return $this->companyId->equals($companyId);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function clockIn(): TimeOfDay
    {
        return $this->clockIn;
    }

    public function clockOut(): TimeOfDay
    {
        return $this->clockOut;
    }

    public function lateToleranceMinutes(): int
    {
        return $this->lateToleranceMinutes;
    }

    public function allowedLunchMinutes(): int
    {
        return $this->allowedLunchMinutes;
    }

    public function workingDays(): WorkingDays
    {
        return $this->workingDays;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->companyId,
            $this->name,
            $this->clockIn,
            $this->clockOut,
            $this->lateToleranceMinutes,
            $this->allowedLunchMinutes,
            $this->workingDays,
            $this->isActive,
        );
    }

    /**
     * @param  list<string>  $workingDays
     */
    public function revise(
        string $name,
        string $clockInTime,
        string $clockOutTime,
        int $lateToleranceMinutes,
        int $allowedLunchMinutes,
        array $workingDays,
        bool $isActive,
    ): self {
        return new self(
            $this->id,
            $this->companyId,
            trim($name),
            TimeOfDay::fromString($clockInTime),
            TimeOfDay::fromString($clockOutTime),
            $lateToleranceMinutes,
            $allowedLunchMinutes,
            WorkingDays::fromStrings($workingDays),
            $isActive,
        );
    }
}
