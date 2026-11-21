<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Attendance;

use App\Features\Attendance\Domain\AttendanceLog;
use App\Features\Attendance\Domain\AttendanceStatus;
use App\Features\Attendance\Domain\DailyAttendance;
use App\Features\Attendance\Domain\PunchSource;
use App\Features\Attendance\Domain\PunchType;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Shared\Domain\CompanyId;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class DailyAttendanceTest extends TestCase
{
    public function test_a_day_without_punches_is_absent(): void
    {
        $summary = $this->calculate([]);

        $this->assertSame(AttendanceStatus::Absent, $summary->status());
        $this->assertSame(0, $summary->lateMinutes());
        $this->assertSame(0, $summary->workedMinutes());
    }

    public function test_arrival_inside_tolerance_stays_incomplete_until_the_cycle_ends(): void
    {
        $summary = $this->calculate([
            $this->log(PunchType::ClockIn, '09:05'),
        ]);

        $this->assertSame(0, $summary->lateMinutes());
        $this->assertSame(AttendanceStatus::Incomplete, $summary->status());
        $this->assertSame(0, $summary->workedMinutes());
    }

    public function test_arrival_after_tolerance_is_late_even_when_the_day_is_open(): void
    {
        $summary = $this->calculate([
            $this->log(PunchType::ClockIn, '09:11'),
        ]);

        $this->assertSame(1, $summary->lateMinutes());
        $this->assertSame(AttendanceStatus::Late, $summary->status());
    }

    public function test_a_finished_day_counts_lunch_and_worked_minutes(): void
    {
        $summary = $this->calculate([
            $this->log(PunchType::ClockIn, '09:00'),
            $this->log(PunchType::LunchOut, '13:00'),
            $this->log(PunchType::LunchIn, '14:10'),
            $this->log(PunchType::ClockOut, '18:00'),
        ]);

        $this->assertSame(0, $summary->lateMinutes());
        $this->assertSame(70, $summary->lunchMinutes());
        $this->assertSame(10, $summary->excessLunchMinutes());
        $this->assertSame(470, $summary->workedMinutes());
        $this->assertSame(AttendanceStatus::OnTime, $summary->status());
    }

    /**
     * @param  list<AttendanceLog>  $logs
     */
    private function calculate(array $logs): DailyAttendance
    {
        return DailyAttendance::calculate(
            new CompanyId(1),
            10,
            3,
            8,
            new WorkDate('2026-11-20'),
            $logs,
            9,
            0,
            10,
            60,
            new DateTimeZone('UTC'),
        );
    }

    private function log(PunchType $type, string $time): AttendanceLog
    {
        return new AttendanceLog(
            null,
            new CompanyId(1),
            10,
            3,
            new WorkDate('2026-11-20'),
            $type,
            new DateTimeImmutable('2026-11-20 '.$time.':00', new DateTimeZone('UTC')),
            null,
            null,
            PunchSource::MobileApp,
            null,
            null,
        );
    }
}
