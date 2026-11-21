<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Schedules;

use App\Features\Schedules\Domain\Exceptions\ClockOutMustBeAfterClockIn;
use App\Features\Schedules\Domain\Exceptions\InvalidWorkingDay;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\InvalidSortColumn;
use PHPUnit\Framework\TestCase;

final class ScheduleTest extends TestCase
{
    public function test_a_schedule_keeps_weekday_names_and_tolerance(): void
    {
        $schedule = Schedule::open(
            new CompanyId(4),
            'Office',
            '09:00',
            '18:00:00',
            10,
            60,
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        );

        $this->assertSame(9, $schedule->clockIn()->hour());
        $this->assertSame(18, $schedule->clockOut()->hour());
        $this->assertSame(
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            $schedule->workingDays()->values(),
        );
    }

    public function test_clock_out_cannot_be_before_clock_in(): void
    {
        $this->expectException(ClockOutMustBeAfterClockIn::class);

        Schedule::open(new CompanyId(4), 'Night', '18:00', '09:00', 10, 60, ['monday']);
    }

    public function test_working_days_must_be_english_weekdays(): void
    {
        $this->expectException(InvalidWorkingDay::class);

        Schedule::open(new CompanyId(4), 'Office', '09:00', '18:00', 10, 60, ['lunes']);
    }

    public function test_schedule_sort_rejects_unknown_columns(): void
    {
        $this->expectException(InvalidSortColumn::class);

        new SearchSchedulesCriteria(new CompanyId(4), sortColumn: 'name; drop table schedules');
    }
}
