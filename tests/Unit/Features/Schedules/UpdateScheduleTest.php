<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Schedules;

use App\Features\Schedules\Application\UpdateSchedule;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Schedules\Domain\UpdateScheduleData;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRole;
use PHPUnit\Framework\TestCase;

final class UpdateScheduleTest extends TestCase
{
    public function test_company_admin_updates_a_schedule(): void
    {
        $companyId = new CompanyId(4);
        $schedules = new UpdatingSchedules;
        $schedules->saved[] = Schedule::open(
            $companyId,
            'Old',
            '09:00',
            '18:00',
            10,
            60,
            ['monday'],
            true,
        )->withId(3);
        $action = new UpdateSchedule($schedules, $this->access());

        $schedule = $action->handle(new UpdateScheduleData(
            UserRole::CompanyAdmin,
            $companyId,
            $companyId,
            3,
            'Desk',
            '10:00',
            '19:00',
            5,
            45,
            ['tuesday', 'wednesday'],
            true,
        ));

        $this->assertSame('Desk', $schedule->name());
        $this->assertSame(10, $schedule->clockIn()->hour());
        $this->assertSame(['tuesday', 'wednesday'], $schedule->workingDays()->values());
    }

    private function access(): AuthorizeActor
    {
        return new AuthorizeActor(new RolePermissions);
    }
}

final class UpdatingSchedules implements ScheduleRepository
{
    /** @var list<Schedule> */
    public array $saved = [];

    public function save(Schedule $schedule): Schedule
    {
        foreach ($this->saved as $index => $row) {
            if ($row->id() === $schedule->id()) {
                $this->saved[$index] = $schedule;

                return $schedule;
            }
        }

        $this->saved[] = $schedule;

        return $schedule;
    }

    public function find(CompanyId $companyId, int $scheduleId): ?Schedule
    {
        foreach ($this->saved as $schedule) {
            if ($schedule->id() === $scheduleId && $schedule->belongsTo($companyId)) {
                return $schedule;
            }
        }

        return null;
    }

    public function search(SearchSchedulesCriteria $criteria): array
    {
        return $this->saved;
    }
}
