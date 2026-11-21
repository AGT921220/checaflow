<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Employees\Domain\Exceptions\EmployeeCodeRequired;
use App\Features\Employees\Domain\Exceptions\EmployeeCodeTooLong;
use App\Features\Employees\Domain\Exceptions\EmployeeIsNotPersisted;
use App\Features\Employees\Domain\Exceptions\InvalidEmployeeReference;
use App\Features\Employees\Domain\Exceptions\PositionTitleTooLong;
use App\Features\Shared\Domain\CompanyId;
use DateTimeImmutable;

final readonly class Employee
{
    public function __construct(
        private ?int $id,
        private CompanyId $companyId,
        private int $userId,
        private int $branchId,
        private int $scheduleId,
        private string $employeeCode,
        private ?string $positionTitle,
        private ?DateTimeImmutable $hireDate,
        private bool $isActive,
    ) {
        if ($this->id !== null && $this->id < 1) {
            throw new EmployeeIsNotPersisted;
        }

        if ($this->userId < 1 || $this->branchId < 1 || $this->scheduleId < 1) {
            throw new InvalidEmployeeReference;
        }

        if ($this->employeeCode === '') {
            throw new EmployeeCodeRequired;
        }

        if (mb_strlen($this->employeeCode) > 255) {
            throw new EmployeeCodeTooLong;
        }

        if ($this->positionTitle !== null && mb_strlen($this->positionTitle) > 255) {
            throw new PositionTitleTooLong;
        }
    }

    public static function hire(
        CompanyId $companyId,
        int $userId,
        int $branchId,
        int $scheduleId,
        string $employeeCode,
        ?string $positionTitle,
        ?DateTimeImmutable $hireDate,
        bool $isActive = true,
    ): self {
        return new self(
            null,
            $companyId,
            $userId,
            $branchId,
            $scheduleId,
            trim($employeeCode),
            self::blankToNull($positionTitle),
            self::dateOnly($hireDate),
            $isActive,
        );
    }

    public function id(): int
    {
        if ($this->id === null) {
            throw new EmployeeIsNotPersisted;
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

    public function userId(): int
    {
        return $this->userId;
    }

    public function branchId(): int
    {
        return $this->branchId;
    }

    public function scheduleId(): int
    {
        return $this->scheduleId;
    }

    public function employeeCode(): string
    {
        return $this->employeeCode;
    }

    public function positionTitle(): ?string
    {
        return $this->positionTitle;
    }

    public function hireDate(): ?DateTimeImmutable
    {
        return $this->hireDate;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function assignSchedule(int $scheduleId): self
    {
        return new self(
            $this->id,
            $this->companyId,
            $this->userId,
            $this->branchId,
            $scheduleId,
            $this->employeeCode,
            $this->positionTitle,
            $this->hireDate,
            $this->isActive,
        );
    }

    public function revise(
        int $branchId,
        int $scheduleId,
        string $employeeCode,
        ?string $positionTitle,
        ?DateTimeImmutable $hireDate,
        bool $isActive,
    ): self {
        return new self(
            $this->id,
            $this->companyId,
            $this->userId,
            $branchId,
            $scheduleId,
            trim($employeeCode),
            self::blankToNull($positionTitle),
            self::dateOnly($hireDate),
            $isActive,
        );
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->companyId,
            $this->userId,
            $this->branchId,
            $this->scheduleId,
            $this->employeeCode,
            $this->positionTitle,
            $this->hireDate,
            $this->isActive,
        );
    }

    private static function blankToNull(?string $positionTitle): ?string
    {
        if ($positionTitle === null) {
            return null;
        }

        $positionTitle = trim($positionTitle);

        if ($positionTitle === '') {
            return null;
        }

        return $positionTitle;
    }

    private static function dateOnly(?DateTimeImmutable $hireDate): ?DateTimeImmutable
    {
        if ($hireDate === null) {
            return null;
        }

        return $hireDate->setTime(0, 0);
    }
}
