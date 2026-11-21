<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Attendance\Domain\Exceptions\AttendanceDayAlreadyClosed;
use App\Features\Attendance\Domain\Exceptions\AttendanceLogBelongsToAnotherCompany;
use App\Features\Attendance\Domain\Exceptions\AttendanceLogBelongsToAnotherDay;
use App\Features\Attendance\Domain\Exceptions\AttendanceLogBelongsToAnotherEmployee;
use App\Features\Attendance\Domain\Exceptions\DuplicatePunch;
use App\Features\Attendance\Domain\Exceptions\InconsistentAttendanceDay;
use App\Features\Attendance\Domain\Exceptions\InvalidPunchSequence;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\GeoPoint;
use DateTimeImmutable;

final readonly class AttendanceDay
{
    /**
     * @param  array<string, AttendanceLog>  $logs
     */
    private function __construct(
        private CompanyId $companyId,
        private int $employeeProfileId,
        private WorkDate $workDate,
        private array $logs,
    ) {}

    /**
     * @param  list<AttendanceLog>  $logs
     */
    public static function open(
        CompanyId $companyId,
        int $employeeProfileId,
        WorkDate $workDate,
        array $logs,
    ): self {
        $indexed = [];

        foreach ($logs as $log) {
            self::assertSameContext($companyId, $employeeProfileId, $workDate, $log);
            $key = $log->punchType()->value;

            if (isset($indexed[$key])) {
                throw new DuplicatePunch($log->punchType());
            }

            $indexed[$key] = $log;
        }

        $day = new self($companyId, $employeeProfileId, $workDate, $indexed);
        $day->assertPrefix();

        return $day;
    }

    public function nextAllowed(): ?PunchType
    {
        $last = $this->last();

        if ($last === null) {
            return PunchType::ClockIn;
        }

        return $last->next();
    }

    public function register(
        PunchType $type,
        int $branchId,
        DateTimeImmutable $punchedAt,
        ?GeoPoint $location,
        ?bool $isWithinGeofence,
        PunchSource $source,
        ?string $ipAddress,
        ?string $notes,
    ): AttendanceLog {
        $next = $this->nextAllowed();

        if ($next === null) {
            throw new AttendanceDayAlreadyClosed;
        }

        if ($this->has($type)) {
            throw new DuplicatePunch($type);
        }

        if ($next !== $type) {
            throw new InvalidPunchSequence($next, $type);
        }

        return new AttendanceLog(
            null,
            $this->companyId,
            $this->employeeProfileId,
            $branchId,
            $this->workDate,
            $type,
            $punchedAt,
            $location,
            $isWithinGeofence,
            $source,
            $ipAddress,
            self::blankToNull($notes),
        );
    }

    /**
     * @return list<AttendanceLog>
     */
    public function punches(): array
    {
        $ordered = [];

        foreach (PunchType::cases() as $type) {
            $log = $this->logs[$type->value] ?? null;

            if ($log === null) {
                continue;
            }

            $ordered[] = $log;
        }

        return $ordered;
    }

    private function has(PunchType $type): bool
    {
        return isset($this->logs[$type->value]);
    }

    private function last(): ?PunchType
    {
        $last = null;

        foreach (PunchType::cases() as $type) {
            if (! $this->has($type)) {
                continue;
            }

            $last = $type;
        }

        return $last;
    }

    private function assertPrefix(): void
    {
        $missing = false;

        foreach (PunchType::cases() as $type) {
            if ($this->has($type)) {
                if ($missing) {
                    throw new InconsistentAttendanceDay;
                }

                continue;
            }

            $missing = true;
        }
    }

    private static function assertSameContext(
        CompanyId $companyId,
        int $employeeProfileId,
        WorkDate $workDate,
        AttendanceLog $log,
    ): void {
        if (! $log->companyId()->equals($companyId)) {
            throw new AttendanceLogBelongsToAnotherCompany;
        }

        if ($log->employeeProfileId() !== $employeeProfileId) {
            throw new AttendanceLogBelongsToAnotherEmployee;
        }

        if (! $log->workDate()->equals($workDate)) {
            throw new AttendanceLogBelongsToAnotherDay;
        }
    }

    private static function blankToNull(?string $notes): ?string
    {
        if ($notes === null) {
            return null;
        }

        $notes = trim($notes);

        if ($notes === '') {
            return null;
        }

        return $notes;
    }
}
