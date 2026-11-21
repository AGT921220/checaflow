<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Attendance\Domain\Exceptions\AttendanceLogBelongsToAnotherCompany;
use App\Features\Attendance\Domain\Exceptions\AttendanceLogBelongsToAnotherDay;
use App\Features\Attendance\Domain\Exceptions\AttendanceLogBelongsToAnotherEmployee;
use App\Features\Attendance\Domain\Exceptions\AttendanceMinutesOverflow;
use App\Features\Attendance\Domain\Exceptions\LunchIntervalIsInvalid;
use App\Features\Attendance\Domain\Exceptions\WorkIntervalIsInvalid;
use App\Features\Shared\Domain\CompanyId;
use DateTimeImmutable;
use DateTimeZone;

final readonly class DailyAttendance
{
    /**
     * @param  list<AttendanceLog>  $logs
     */
    public static function calculate(
        CompanyId $companyId,
        int $employeeProfileId,
        int $branchId,
        int $scheduleId,
        WorkDate $workDate,
        array $logs,
        int $clockInHour,
        int $clockInMinute,
        int $lateToleranceMinutes,
        int $allowedLunchMinutes,
        DateTimeZone $timezone,
        ?int $existingId = null,
    ): self {
        self::assertLogs($companyId, $employeeProfileId, $workDate, $logs);

        $clockIn = self::find($logs, PunchType::ClockIn);
        $lunchOut = self::find($logs, PunchType::LunchOut);
        $lunchIn = self::find($logs, PunchType::LunchIn);
        $clockOut = self::find($logs, PunchType::ClockOut);
        $lateMinutes = self::minutesLate($clockIn, $workDate, $clockInHour, $clockInMinute, $lateToleranceMinutes, $timezone);
        $lunchMinutes = self::minutesAtLunch($lunchOut, $lunchIn);
        $excessLunchMinutes = self::minutesOverLunch($lunchMinutes, $allowedLunchMinutes);
        $workedMinutes = self::minutesWorked($clockIn, $lunchOut, $clockOut, $lunchMinutes);

        return new self(
            $existingId,
            $companyId,
            $employeeProfileId,
            $branchId,
            $scheduleId,
            $workDate,
            $clockIn?->punchedAt(),
            $lunchOut?->punchedAt(),
            $lunchIn?->punchedAt(),
            $clockOut?->punchedAt(),
            $lateMinutes,
            $lunchMinutes,
            $excessLunchMinutes,
            $workedMinutes,
            self::resolveStatus($logs, $lateMinutes),
        );
    }

    public function __construct(
        private ?int $id,
        private CompanyId $companyId,
        private int $employeeProfileId,
        private int $branchId,
        private int $scheduleId,
        private WorkDate $workDate,
        private ?DateTimeImmutable $clockInAt,
        private ?DateTimeImmutable $lunchOutAt,
        private ?DateTimeImmutable $lunchInAt,
        private ?DateTimeImmutable $clockOutAt,
        private int $lateMinutes,
        private int $lunchMinutes,
        private int $excessLunchMinutes,
        private int $workedMinutes,
        private AttendanceStatus $status,
    ) {
        self::fit($this->lateMinutes);
        self::fit($this->lunchMinutes);
        self::fit($this->excessLunchMinutes);
        self::fit($this->workedMinutes);
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

    public function scheduleId(): int
    {
        return $this->scheduleId;
    }

    public function workDate(): WorkDate
    {
        return $this->workDate;
    }

    public function clockInAt(): ?DateTimeImmutable
    {
        return $this->clockInAt;
    }

    public function lunchOutAt(): ?DateTimeImmutable
    {
        return $this->lunchOutAt;
    }

    public function lunchInAt(): ?DateTimeImmutable
    {
        return $this->lunchInAt;
    }

    public function clockOutAt(): ?DateTimeImmutable
    {
        return $this->clockOutAt;
    }

    public function lateMinutes(): int
    {
        return $this->lateMinutes;
    }

    public function lunchMinutes(): int
    {
        return $this->lunchMinutes;
    }

    public function excessLunchMinutes(): int
    {
        return $this->excessLunchMinutes;
    }

    public function workedMinutes(): int
    {
        return $this->workedMinutes;
    }

    public function status(): AttendanceStatus
    {
        return $this->status;
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->companyId,
            $this->employeeProfileId,
            $this->branchId,
            $this->scheduleId,
            $this->workDate,
            $this->clockInAt,
            $this->lunchOutAt,
            $this->lunchInAt,
            $this->clockOutAt,
            $this->lateMinutes,
            $this->lunchMinutes,
            $this->excessLunchMinutes,
            $this->workedMinutes,
            $this->status,
        );
    }

    /**
     * @param  list<AttendanceLog>  $logs
     */
    private static function assertLogs(CompanyId $companyId, int $employeeProfileId, WorkDate $workDate, array $logs): void
    {
        foreach ($logs as $log) {
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
    }

    /**
     * @param  list<AttendanceLog>  $logs
     */
    private static function find(array $logs, PunchType $type): ?AttendanceLog
    {
        foreach ($logs as $log) {
            if ($log->punchType() === $type) {
                return $log;
            }
        }

        return null;
    }

    private static function minutesLate(
        ?AttendanceLog $clockIn,
        WorkDate $workDate,
        int $hour,
        int $minute,
        int $tolerance,
        DateTimeZone $timezone,
    ): int {
        if ($clockIn === null) {
            return 0;
        }

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $tolerance < 0) {
            throw new AttendanceMinutesOverflow;
        }

        $local = $clockIn->punchedAt()->setTimezone($timezone);
        $deadline = DateTimeImmutable::createFromFormat('!Y-m-d', $workDate->value, $timezone);

        if ($deadline === false) {
            throw new AttendanceMinutesOverflow;
        }

        $deadline = $deadline->setTime($hour, $minute)->modify("+{$tolerance} minutes");

        if ($local <= $deadline) {
            return 0;
        }

        return self::fit((int) floor(($local->getTimestamp() - $deadline->getTimestamp()) / 60));
    }

    private static function minutesAtLunch(?AttendanceLog $lunchOut, ?AttendanceLog $lunchIn): int
    {
        if ($lunchOut === null || $lunchIn === null) {
            return 0;
        }

        $seconds = $lunchIn->punchedAt()->getTimestamp() - $lunchOut->punchedAt()->getTimestamp();

        if ($seconds < 0) {
            throw new LunchIntervalIsInvalid;
        }

        return self::fit((int) floor($seconds / 60));
    }

    private static function minutesOverLunch(int $lunchMinutes, int $allowedLunchMinutes): int
    {
        if ($allowedLunchMinutes < 0) {
            throw new AttendanceMinutesOverflow;
        }

        if ($lunchMinutes <= $allowedLunchMinutes) {
            return 0;
        }

        return self::fit($lunchMinutes - $allowedLunchMinutes);
    }

    private static function minutesWorked(
        ?AttendanceLog $clockIn,
        ?AttendanceLog $lunchOut,
        ?AttendanceLog $clockOut,
        int $lunchMinutes,
    ): int {
        if ($clockIn === null) {
            return 0;
        }

        $end = $clockOut ?? $lunchOut;

        if ($end === null) {
            return 0;
        }

        $seconds = $end->punchedAt()->getTimestamp() - $clockIn->punchedAt()->getTimestamp();

        if ($seconds < 0) {
            throw new WorkIntervalIsInvalid;
        }

        $minutes = (int) floor($seconds / 60);

        if ($clockOut === null) {
            return self::fit($minutes);
        }

        if ($minutes < $lunchMinutes) {
            return 0;
        }

        return self::fit($minutes - $lunchMinutes);
    }

    /**
     * @param  list<AttendanceLog>  $logs
     */
    private static function resolveStatus(array $logs, int $lateMinutes): AttendanceStatus
    {
        if ($logs === []) {
            return AttendanceStatus::Absent;
        }

        if ($lateMinutes > 0) {
            return AttendanceStatus::Late;
        }

        if (! self::hasFullCycle($logs)) {
            return AttendanceStatus::Incomplete;
        }

        return AttendanceStatus::OnTime;
    }

    /**
     * @param  list<AttendanceLog>  $logs
     */
    private static function hasFullCycle(array $logs): bool
    {
        foreach (PunchType::cases() as $type) {
            if (self::find($logs, $type) === null) {
                return false;
            }
        }

        return true;
    }

    private static function fit(int $minutes): int
    {
        if ($minutes < 0 || $minutes > 65535) {
            throw new AttendanceMinutesOverflow;
        }

        return $minutes;
    }
}
