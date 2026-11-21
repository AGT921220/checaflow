<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Attendance\Domain\Exceptions\InvalidBranchId;
use App\Features\Attendance\Domain\Exceptions\InvalidEmployeeProfileId;
use App\Features\Attendance\Domain\Exceptions\InvalidIpAddress;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\GeoPoint;
use DateTimeImmutable;

final readonly class AttendanceLog
{
    public function __construct(
        private ?int $id,
        private CompanyId $companyId,
        private int $employeeProfileId,
        private int $branchId,
        private WorkDate $workDate,
        private PunchType $punchType,
        private DateTimeImmutable $punchedAt,
        private ?GeoPoint $location,
        private ?bool $isWithinGeofence,
        private PunchSource $source,
        private ?string $ipAddress,
        private ?string $notes,
    ) {
        if ($this->employeeProfileId < 1 || ($this->id !== null && $this->id < 1)) {
            throw new InvalidEmployeeProfileId;
        }

        if ($this->branchId < 1) {
            throw new InvalidBranchId;
        }

        $this->assertIp($this->ipAddress);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function employeeProfileId(): int
    {
        return $this->employeeProfileId;
    }

    public function branchId(): int
    {
        return $this->branchId;
    }

    public function workDate(): WorkDate
    {
        return $this->workDate;
    }

    public function punchType(): PunchType
    {
        return $this->punchType;
    }

    public function punchedAt(): DateTimeImmutable
    {
        return $this->punchedAt;
    }

    public function location(): ?GeoPoint
    {
        return $this->location;
    }

    public function isWithinGeofence(): ?bool
    {
        return $this->isWithinGeofence;
    }

    public function source(): PunchSource
    {
        return $this->source;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->companyId,
            $this->employeeProfileId,
            $this->branchId,
            $this->workDate,
            $this->punchType,
            $this->punchedAt,
            $this->location,
            $this->isWithinGeofence,
            $this->source,
            $this->ipAddress,
            $this->notes,
        );
    }

    private function assertIp(?string $ipAddress): void
    {
        if ($ipAddress === null) {
            return;
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            throw new InvalidIpAddress;
        }
    }
}
