<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Attendance;

use App\Features\Attendance\Domain\AttendanceDay;
use App\Features\Attendance\Domain\AttendanceLog;
use App\Features\Attendance\Domain\Exceptions\AttendanceDayAlreadyClosed;
use App\Features\Attendance\Domain\Exceptions\DuplicatePunch;
use App\Features\Attendance\Domain\Exceptions\InconsistentAttendanceDay;
use App\Features\Attendance\Domain\Exceptions\InvalidPunchSequence;
use App\Features\Attendance\Domain\PunchSource;
use App\Features\Attendance\Domain\PunchType;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Shared\Domain\CompanyId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AttendanceDayTest extends TestCase
{
    public function test_the_cycle_accepts_punches_in_order(): void
    {
        $day = AttendanceDay::open($this->company(), 10, $this->date(), []);
        $clockIn = $day->register(PunchType::ClockIn, 3, $this->at('09:00'), null, null, PunchSource::MobileApp, null, null);

        $day = AttendanceDay::open($this->company(), 10, $this->date(), [$clockIn]);

        $this->assertSame(PunchType::LunchOut, $day->nextAllowed());
        $this->assertSame(PunchType::LunchOut, $day->register(
            PunchType::LunchOut,
            3,
            $this->at('14:00'),
            null,
            null,
            PunchSource::MobileApp,
            null,
            null,
        )->punchType());
    }

    public function test_a_repeated_punch_is_rejected(): void
    {
        $clockIn = $this->log(PunchType::ClockIn, '09:00');
        $day = AttendanceDay::open($this->company(), 10, $this->date(), [$clockIn]);

        $this->expectException(DuplicatePunch::class);

        $day->register(PunchType::ClockIn, 3, $this->at('09:05'), null, null, PunchSource::MobileApp, null, null);
    }

    public function test_a_skipped_punch_is_rejected(): void
    {
        $day = AttendanceDay::open($this->company(), 10, $this->date(), []);

        $this->expectException(InvalidPunchSequence::class);

        $day->register(PunchType::LunchOut, 3, $this->at('14:00'), null, null, PunchSource::MobileWeb, null, null);
    }

    public function test_a_closed_day_rejects_another_punch(): void
    {
        $logs = [
            $this->log(PunchType::ClockIn, '09:00'),
            $this->log(PunchType::LunchOut, '14:00'),
            $this->log(PunchType::LunchIn, '15:00'),
            $this->log(PunchType::ClockOut, '18:00'),
        ];
        $day = AttendanceDay::open($this->company(), 10, $this->date(), $logs);

        $this->assertNull($day->nextAllowed());
        $this->expectException(AttendanceDayAlreadyClosed::class);

        $day->register(PunchType::ClockIn, 3, $this->at('19:00'), null, null, PunchSource::MobileWeb, null, null);
    }

    public function test_a_gap_in_stored_punches_is_rejected(): void
    {
        $this->expectException(InconsistentAttendanceDay::class);

        AttendanceDay::open($this->company(), 10, $this->date(), [
            $this->log(PunchType::ClockIn, '09:00'),
            $this->log(PunchType::LunchIn, '15:00'),
        ]);
    }

    private function log(PunchType $type, string $time): AttendanceLog
    {
        return new AttendanceLog(
            null,
            $this->company(),
            10,
            3,
            $this->date(),
            $type,
            $this->at($time),
            null,
            null,
            PunchSource::MobileApp,
            null,
            null,
        );
    }

    private function company(): CompanyId
    {
        return new CompanyId(1);
    }

    private function date(): WorkDate
    {
        return new WorkDate('2026-11-20');
    }

    private function at(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-20 '.$time.':00', new \DateTimeZone('UTC'));
    }
}
